<?php

class ApiImport_ResponseAdapter_Omeka_ItemAdapter extends ApiImport_ResponseAdapter_AbstractRecordAdapter
{
    protected $recordType = 'Item';
    protected $service;

    public function import()
    {
        //grab the data needed for using update_item or insert_item
        $elementTexts = $this->elementTexts();
        $itemMetadata = $this->itemMetadata();
        //avoid accidental duplications
        if($this->record && $this->record->exists()) {
            $itemMetadata['overwriteElementTexts'] = true;
            update_item($this->record, $itemMetadata, $elementTexts);
            $this->updateItemOwner($this->record);
        } else {
            $this->record = insert_item($itemMetadata, $elementTexts);
            //dig up the correct owner information, importing the user if needed
            $this->updateItemOwner($this->record);
            $this->addOmekaApiImportRecordIdMap();
        }
        //import files after the item is there, so the file has an item id to use
        //we're also keeping track of the correspondences between local and remote
        //file ids, so we have to introduce this little inefficiency of not passing
        //the file data
        $this->importFiles($this->record);
    }

    public function externalId()
    {
        return $this->responseData['id'];
    }

    /**
     * Try to map a correspondence between the local and remote owners. Requires that a key with
     * sufficient permission to the API is given
     *
     * @param Item $item
     */
    protected function updateItemOwner($item)
    {
        $ownerId = $this->responseData['owner']['id'];
        if (! $ownerId) {
            $item->owner_id = null;
            $item->save();
            return;
        }
        $owner = $this->db->getTable('OmekaApiImportRecordIdMap')->localRecord('User', $ownerId, $this->endpointUri);
        if($owner) {
            $item->owner_id = $owner->id;
        } else {
            $response = $this->service->users->get($ownerId);
            if($response->getStatus() == 200) {
                $responseData = json_decode($response->getBody(), true);
                $adapter = new ApiImport_ResponseAdapter_Omeka_UserAdapter($responseData, $this->endpointUri);
                $adapter->import();
                $item->owner_id = $adapter->record->id;
            } else {
                _log(__("Attempting User import") . " " . $response->getStatus() . ": " . $response->getMessage(), Zend_Log::INFO);
            }
        }
        $item->save();
    }

    /**
     * Process the element text data
     * @param array $responseData
     */
    protected function elementTexts($responseData = null)
    {
        $elementTexts = array();
        if(!$responseData) {
            $responseData = $this->responseData;
        }

        //need to work around a previous bug in contribution, which would store User Profile
        //elements on the Item as well. This made item elements from API also include
        //UP elements, which failed the lookup. 
        $db = get_db();
        $elementTable = $db->getTable('Element');

        $sql = "
            SELECT DISTINCT external_id FROM `$db->ElementSet`
            JOIN `$db->OmekaApiImportRecordIdMap` ON {$db->ElementSet}.id = local_id
            WHERE {$db->OmekaApiImportRecordIdMap}.record_type = 'ElementSet'
            AND {$db->ElementSet}.record_type = 'UserProfilesType'
        ";
        $userProfilesElementSetIdsMap = $db->fetchCol($sql);

        foreach($responseData['element_texts'] as $elTextData) {
            if (in_array($elTextData['element_set']['id'], $userProfilesElementSetIdsMap)) {
                continue;
            }
            $elName = $elTextData['element']['name'];
            $elSet = $elTextData['element_set']['name'];
            $elTextInsertArray = array('text' => $elTextData['text'],
                                       'html' => $elTextData['html']
                                       );
            if (is_null($elTextInsertArray['text'])) {
                $elTextInsertArray['text'] = '';
            }
            $elementTexts[$elSet][$elName][] = $elTextInsertArray;

        }
        return $elementTexts;
    }

    /**
     * Parse out the item metadata for import/update_item
     */
    protected function itemMetadata()
    {
        $metadata = array();
        $metadata['public'] = $this->responseData['public'];
        $metadata['featured'] = $this->responseData['featured'];
        //external vs internal collection ids could be different
        $collectionExternalId = $this->responseData['collection']['id'];
        $collectionId = null;
        if(is_null($collectionExternalId)) {
            $metadata['collection_id'] = null;
        } else {
            $collection = $this->localRecord('Collection', $collectionExternalId);
            if($collection) {
                $collectionId = $collection->id;
            } else {
                //import the collection
                //older version of API exposed non-public collection info on the item
                if ($collection = $this->importCollection($collectionExternalId)) {
                    $collectionId = $collection->id;
                }
            }
            $metadata['collection_id'] = $collectionId;
        }
        $itemType = $this->responseData['item_type'] ? $this->localRecord('ItemType', $this->responseData['item_type']['id']) : null;
        if($itemType) {
            $itemTypeId = $itemType->id;
        } else {
            $itemTypeId = null;
        }
        $metadata['item_type_id'] = $itemTypeId;

        $tagsArray = array();
        foreach($this->responseData['tags'] as $tagData) {
            $tagsArray[] = $tagData['name'];
        }
        $metadata['tags'] = implode(',', $tagsArray);
        return $metadata;
    }

    protected function importFiles($item)
    {
        $ingester = Omeka_File_Ingest_AbstractIngest::factory(
            'Url',
            $item,
            array()
        );
        $files = $this->files();

        //have to step through one by one so we can save the id map for each $fileRecord and $fileData
        foreach($files as $fileData)
        {
            try {
                $fileRecords = $ingester->ingest(array($fileData));
            } catch (Exception $e) {
                _log($e);
                continue;
            }
            $item->saveFiles();
            $fileRecord = array_pop($fileRecords);
            $map = new OmekaApiImportRecordIdMap();
            $map->record_type = 'File';
            $map->local_id = $fileRecord->id;
            $map->external_id = $fileData['externalId'];
            $map->endpoint_uri = $this->endpointUri;
            $map->save();
        }
    }

    /**
     * Parse out and query the data about files for Omeka_File_Ingest_AbstractIngest::factory
     *
     * @return array File data for the file ingester
     */
    protected function files()
    {
        $files = array();
        $response = $this->service->files->get(null, array('item' => $this->externalId()));
        if($response->getStatus() == 200) {
            $responseData = json_decode($response->getBody(), true);
        } else {
            _log($response->getMessage());
        }

        $externalIds = $this->db->getTable('OmekaApiImportRecordIdMap')
                                ->findExternalIdsByParams(array('record_type' =>'File',
                                                               'endpoint_uri' => $this->endpointUri
                                                         ));
        $ids = array_keys($externalIds);
        foreach($responseData as $fileData) {
            if(! in_array($fileData['id'], $ids)) {
                $files[] = array('source'   => $fileData['file_urls']['original'],
                                 'metadata' => $this->elementTexts($fileData),
                                 //add the external id so we can produce the map
                                 'externalId' => $fileData['id']
                                );
            }
        }
        return $files;
    }

    protected function importCollection($collectionId)
    {
        $response = $this->service->collections->get($collectionId);
        if($response->getStatus() == 200) {
            $responseData = json_decode($response->getBody(), true);
            $adapter = new ApiImport_ResponseAdapter_Omeka_CollectionAdapter($responseData, $this->endpointUri);
            $adapter->setService($this->service);
            $adapter->import();
            return $adapter->getRecord();
        }
        return false;
    }
}
<?php

class ApiImport_ResponseAdapter_Omeka_ItemAdapter extends ApiImport_ResponseAdapter_RecordAdapterAbstract
                                  implements ApiImport_ResponseAdapter_RecordAdapterInterface
{
    protected $recordType = 'Item';
    protected $service;
    
    public function import()
    {
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
            $this->addApiRecordIdMap();
        }
        
        //import files after the item is there, so the file has an item id to use
        $this->importFiles($this->record);
    }

    public function externalId()
    {
        return $this->responseData['id'];
    }
    
    protected function updateItemOwner($item)
    {
        $ownerId = $this->responseData['owner']['id'];
        $owner = $this->db->getTable('ApiRecordIdMap')->localRecord('User', $ownerId, $this->endpointUri);
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
                _log($response->getMessage(), Zend_Log::INFO);
            }
        }
        $item->save();
    }
    
    protected function elementTexts($responseData = null)
    {
        $elementTexts = array();
        if(!$responseData) {
            $responseData = $this->responseData;
        }
        
        foreach($responseData['element_texts'] as $elTextData) {
            $elName = $elTextData['element']['name'];
            $elSet = $elTextData['element_set']['name'];
            $elTextInsertArray = array('text' => $elTextData['text'],
                                       'html' => $elTextData['html']
                                       );
            $elementTexts[$elSet][$elName][] = $elTextInsertArray;
            
        }
        return $elementTexts;
    }

    protected function itemMetadata()
    {
        $metadata = array();
        $metadata['public'] = $this->responseData['public'];
        $metadata['featured'] = $this->responseData['featured'];
        //external vs internal collection ids could be different
        $collectionExternalId = $this->responseData['collection']['id'];
        if(is_null($collectionExternalId)) {
            $metadata['collection_id'] = null;
        } else {
            $collection = $this->localRecord('Collection', $collectionExternalId);
            if($collection) {
                $collectionId = $collection->id;
            } else {
                //import the collection
                $collection = $this->importCollection($collectionExternalId);
                $collectionId = $collection->id;
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
        //have to step through one by on so we can save the id map for each
        foreach($files as $fileData)
        {
            $fileRecords = $ingester->ingest(array($fileData));
            $item->saveFiles();
            $fileRecord = array_pop($fileRecords);  
            $map = new ApiRecordIdMap();
            $map->record_type = 'File';
            $map->local_id = $fileRecord->id;
            $map->external_id = $fileData['externalId'];
            $map->endpoint_uri = $this->endpointUri;
            $map->save();  
        }
    }
    
    protected function files()
    {
        $files = array();
        $response = $this->service->files->get(null, array('item' => $this->externalId()));
        if($response->getStatus() == 200) {
            $responseData = json_decode($response->getBody(), true);
        } else {
            debug($response->getMessage());
        }

        $externalIds = $this->db->getTable('ApiRecordIdMap')
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
        }
        $adapter->setService($this->service);
        $adapter->import();
        return $adapter->getRecord();
    }

}
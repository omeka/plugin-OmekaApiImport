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
        } else {
            $item = insert_item($itemMetadata, $elementTexts);
            $this->record = $item;
            $this->addApiRecordIdMap();
        }
        
        //import files after the item is there, so the file has an item id to use
        $this->importFiles($this->record);
    }

    public function externalId()
    {
        $responseJson = json_decode($this->response->getBody(), true);
        return $responseJson['id'];
    }
    
    protected function elementTexts($response = null)
    {
        $elementTexts = array();
        if(!$response) {
            $response = json_decode($this->response->getBody(), true);
        }
        
        foreach($response['element_texts'] as $elTextData) {
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
        $responseJson = json_decode($this->response->getBody(), true);
        $metadata = array();
        $metadata['public'] = $responseJson['public'];
        $metadata['featured'] = $responseJson['featured'];
        //external vs internal collection ids could be different
        $collectionExternalId = $responseJson['collection']['id'];
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

        $itemType = $responseJson['item_type'] ? $this->localRecord('ItemType', $responseJson['item_type']['id']) : null;
        if($itemType) {
            $itemTypeId = $itemType->id;
        } else {
            $itemTypeId = null;
        }
        $metadata['item_type_id'] = $itemTypeId;
        
        $tagsArray = array();
        foreach($responseJson['tags'] as $tagData) {
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
            $responseJson = json_decode($response->getBody(), true);
        } else {
            debug(print_r($response->getBody(), true));
            debug($response->getMessage());
        }

        $externalIds = $this->db->getTable('ApiRecordIdMap')
                               ->findExternalIdsByParams(array('record_type' =>'File',
                                                               'endpoint_uri' => $this->endpointUri
                                                         ));
        $ids = array_keys($externalIds);    
        foreach($responseJson as $fileData) {
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
        $adapter = new ApiImport_ResponseAdapter_Omeka_CollectionAdapter($response, $this->endpointUri);
        $adapter->setService($this->service);
        $adapter->import();
        return $adapter->getRecord();
    }

}
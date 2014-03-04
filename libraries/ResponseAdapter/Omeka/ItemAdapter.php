<?php

class ApiImport_ResponseAdapter_Omeka_ItemAdapter extends ApiImport_ResponseAdapter_RecordAdapterAbstract
                                  implements ApiImport_ResponseAdapter_RecordAdapterInterface
{
    protected $recordType = 'Item';

    public function import()
    {
        $elementTexts = $this->elementTexts();
        $fileMetadata = array('file_transfer_type' => 'Url',
                              'files' => $this->files()
                             );
        $itemMetadata = $this->itemMetadata();
        //avoid accidental duplications
        if($this->record && $this->record->exists()) {
            update_item($this->record, $this->itemMetadata(), $elementTexts);
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
        $omeka = new Zend_Service_Omeka('http://localhost/Omeka/api');        
        $omeka->setKey('key');
        
        $responseJson = json_decode($omeka->files->get(array('item' => $this->externalId()))->getBody(), true);
        
        $externalIds = get_db()->getTable('ApiRecordIdMap')
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
        $omeka = new Zend_Service_Omeka('http://localhost/Omeka/api');
        $omeka->setKey('key');
        $response = $omeka->collections->get($collectionId);
        $adapter = new ApiImport_ResponseAdapter_Omeka_CollectionAdapter($response, 'http://localhost/Omeka/api');
        $adapter->import();
        return $adapter->getRecord();
    }

}
<?php

class ApiImport_ResponseAdapter_Omeka_CollectionAdapter extends ApiImport_ResponseAdapter_RecordAdapterAbstract
                                  implements ApiImport_ResponseAdapter_RecordAdapterInterface
{
    protected $recordType = 'Collection';

    /**
     * Insert the collection into the database
     *
     */
    public function import()
    {
        $collectionMetadata = $this->collectionMetadata();
        $elementTexts = $this->elementTexts();
        debug(print_r($elementTexts, true));
        if($this->record && $this->record->exists()) {
            $collectionMetadata['overwriteElementTexts'] = true;
            update_collection($this->record, $collectionMetadata, $elementTexts);
        } else {
            $collection = insert_collection($collectionMetadata, $elementTexts);
            $this->record = $collection;
            $this->addApiRecordIdMap();
        }
    }

    /**
     * Find the external Id for the collection in the response
     * @see ApiImport_ResponseAdapter_RecordAdapterInterface::externalId()
     */
    public function externalId()
    {
        $responseJson = json_decode($this->response->getBody(), true);
        return $responseJson['id'];
    }

    /**
     * Put together the collection metadata for insertion into database
     *
     * @see insert_collection()
     * @see update_collection()
     * @return array $metadata formatted to be the metadata insert param to insert/update_collection
     */
    protected function collectionMetadata()
    {
        $responseJson = json_decode($this->response->getBody(), true);
        $metadata = array();
        $metadata['public'] = $responseJson['public'];
        $metadata['featured'] = $responseJson['featured'];
        return $metadata;
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
    
}
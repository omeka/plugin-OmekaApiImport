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
        //avoid accidental duplications
        if($this->record && $this->record->exists()) {
            $this->update();
        } else {
            echo 'inserting collection';
            $collection = insert_collection( $this->collectionMetadata());
            $this->record = $collection;
            $this->addApiRecordIdMap($collection);
        }
    }

    /**
     * Update the collection in the database
     * @see ApiImport_ResponseAdapter_RecordAdapterInterface::update()
     */
    public function update()
    {
        echo 'updating collection';
        update_collection($this->record, $this->collectionMetadata());
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
}
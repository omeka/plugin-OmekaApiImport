<?php

abstract class ApiImport_ResponseAdapter_RecordAdapterAbstract implements ApiImport_ResponseAdapter_RecordAdapterInterface
{
    protected $response;
    protected $record;
    protected $recordType;
    protected $endpointUri;
    protected $db;

    public function __construct($response, $endpointUri)
    {
        $this->db = get_db();
        $this->response = $response;
        $this->endpointUri = $endpointUri;
        $this->record = $this->localRecord();
        if(is_null($this->recordType)) {
            throw new Api_Import_RecordAdapterException(__("Record adapters must declare a record type"));
        }
    }

    /**
     * Return the local record being worked with
     *
     * @return Omeka_Record_AbstractRecord
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * Look up a local record based on the record type and the external id of the data corresponding to it
     *
     * @param string|null $recordType
     * @param mixed $externalId
     */
    protected function localRecord($recordType = null, $externalId = null)
    {
        if(is_null($recordType)) {
            $recordType = $this->recordType;
        }

        if(is_null($externalId)) {
            $externalId = $this->externalId();
        }

        $rec =  $this->db->getTable('ApiRecordIdMap')->localRecord($recordType, $externalId, $this->endpointUri);
        return $rec;
    }

    /**
     * Add a mapping between the local record and the external record
     *
     * @param Omeka_Record_RecordAbstract $record
     */
    protected function addApiRecordIdMap()
    {
        $recordType = get_class($this->record);
        $id = $this->externalId();
        $map = new ApiRecordIdMap();
        $map->record_type = $recordType;
        $map->local_id = $record->id;
        $map->external_id = $id;
        $map->endpoint_uri = $this->endpointUri;
        $map->save();
    }

}
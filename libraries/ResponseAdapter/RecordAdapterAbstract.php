<?php

abstract class ApiImport_ResponseAdapter_RecordAdapterAbstract implements ApiImport_ResponseAdapter_RecordAdapterInterface
{
    protected $responseData;
    protected $record;
    protected $recordType;
    protected $endpointUri;
    protected $service;
    protected $db;
    protected $messages;

    public function __construct($responseData, $endpointUri)
    {
        if(!$this->recordType) {
            throw new ApiImport_ResponseAdapter_RecordAdapterException("RecordAdapters must declare a recordType");
        }
        $this->construct($responseData, $endpointUri);    
    }
    
    public function resetResponseData($responseData)
    {
        $this->construct($responseData, $this->endpointUri);    
    }

    public function setService($service)
    {
        $this->service = $service;
    }

    public function getService()
    {
        return $this->service;
    }
    
    public function getMessages()
    {
        return $this->messages;
    }
    
    public function addMessage($message, $priority = ZEND_LOG::INFO)
    {
        _log($message, $priority);
        $this->messages[$priority][] = $message;
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
        $map->local_id = $this->record->id;
        $map->external_id = $id;
        $map->endpoint_uri = $this->endpointUri;
        $map->save();
    }
    
    protected function construct($responseData, $endpointUri)
    {
        $this->messages = array();
        $this->db = get_db();
        $this->responseData = $responseData;
        $this->endpointUri = $endpointUri;
        $this->record = $this->localRecord();
        $this->messages = array();
        if(is_null($this->recordType)) {
            throw new Api_Import_RecordAdapterException(__("Record adapters must declare a record type"));
        }
        if($this->record && (get_class($this->record) != $this->recordType)) {
            throw new Api_Import_RecordAdapterException(__("Declared adapter record type must match local record type"));
        }        
    }
}
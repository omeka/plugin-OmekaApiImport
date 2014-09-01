<?php

/**
 * Class for taking a response from some API and turning it into data to insert for an Omeka Record
 *
 *
 */

abstract class ApiImport_ResponseAdapter_AbstractRecordAdapter implements ApiImport_ResponseAdapter_RecordAdapterInterface
{
    protected $responseData;
    protected $record;
    protected $recordType;
    protected $endpointUri;

    /**
     * Injection of a class service doing the remote data-gathering. One of these typically passes in the $responseData
     * @var unknown_type
     */
    protected $service;
    protected $db;

    /**
     *
     * @param mixed $responseData Data from an API response, typically JSON, XML, RDF, whatevs
     * @param string $endpointUri The API endpoint being used
     * @param string $recordType The Omeka record type being inserted/updated
     */
    public function __construct($responseData, $endpointUri, $recordType = null)
    {
        $this->construct($responseData, $endpointUri, $recordType);
    }

    public function resetResponseData($responseData)
    {
        $this->construct($responseData, $this->endpointUri, $recordType);
    }

    public function setService($service)
    {
        $this->service = $service;
    }

    public function getService()
    {
        return $this->service;
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

        $rec =  $this->db->getTable('OmekaApiImportRecordIdMap')->localRecord($recordType, $externalId, $this->endpointUri);
        return $rec;
    }

    /**
     * Add a mapping between the local record and the external record
     *
     * @param Omeka_Record_RecordAbstract $record
     */
    protected function addOmekaApiImportRecordIdMap()
    {
        $recordType = get_class($this->record);
        $id = $this->externalId();
        $map = new OmekaApiImportRecordIdMap();
        $map->record_type = $recordType;
        $map->local_id = $this->record->id;
        $map->external_id = $id;
        $map->endpoint_uri = $this->endpointUri;
        $map->save();
    }

    protected function construct($responseData, $endpointUri, $recordType)
    {
        if($recordType) {
            $this->recordType = $recordType;
        }
        $this->db = get_db();
        $this->responseData = $responseData;
        $this->endpointUri = $endpointUri;
        if(!empty($this->responseData)) {
            $this->record = $this->localRecord();
        }

        if(is_null($this->recordType)) {
            throw new ApiImport_ResponseAdapter_RecordAdapterException(__("Record adapters must declare a record type"));
        }

        if($this->record && (get_class($this->record) != $this->recordType)) {
            throw new ApiImport_ResponseAdapter_RecordAdapterException(__("Declared adapter record type must match local record type"));
        }
    }
}
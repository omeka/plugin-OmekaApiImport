<?php

class ApiImport_ResponseAdapter_Omeka_ElementSetAdapter extends ApiImport_ResponseAdapter_AbstractRecordAdapter
{

    protected $recordType = 'ElementSet';

    public function import()
    {
        //look for a local record, first by whether it's been imported, which is done in construct,
        //then by the element set name
        if(!$this->record) {
            $this->record = $this->db->getTable('ElementSet')->findByName($this->responseData['name']);
        }

        if(!$this->record) {
            $this->record = new ElementSet;
        }
        //set new value if element set exists and override is set, or if it is brand new
        if( ($this->record->exists() && get_option('omeka_api_import_override_element_set_data')) || !$this->record->exists()) {
            $this->record->description = $this->responseData['description'];
            $this->record->name = $this->responseData['name'];
            $this->record->record_type = $this->responseData['record_type'];
        }

        try {
            $this->record->save(true);
            $this->addOmekaApiImportRecordIdMap();
        } catch(Exception $e) {
            _log($e);
        }
    }

    public function externalId()
    {
        return $this->responseData['id'];
    }
}
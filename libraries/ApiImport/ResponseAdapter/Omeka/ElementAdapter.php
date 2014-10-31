<?php
class ApiImport_ResponseAdapter_Omeka_ElementAdapter extends ApiImport_ResponseAdapter_AbstractRecordAdapter
{

    protected $recordType = 'Element';

    public function import()
    {

        $localElementSet = $this->db->getTable('OmekaApiImportRecordIdMap')
                                        ->localRecord('ElementSet',
                                                       $this->responseData['element_set']['id'],
                                                       $this->endpointUri
                                                      );
        //look for a local record, first by whether it's been imported, which is done in construct,
        //then by the element set name
        if(!$this->record) {
            $this->record = $this->db->getTable('Element')->findByElementSetNameAndElementName($localElementSet->name, $this->responseData['name']);
        }

        if(!$this->record) {
            $this->record = new Element;
        }
        //set new value if element set exists and override is set, or if it is brand new
        if( ($this->record->exists() && get_option('omeka_api_import_override_element_set_data')) || !$this->record->exists()) {
            $this->record->description = $this->responseData['description'];
            $this->record->name = $this->responseData['name'];
            $this->record->element_set_id = $localElementSet->id;
            $this->record->order = $this->responseData['order'];
            $this->record->comment = $this->responseData['comment'];
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
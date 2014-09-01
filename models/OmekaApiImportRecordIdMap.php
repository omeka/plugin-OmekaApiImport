<?php

class OmekaApiImportRecordIdMap extends Omeka_Record_AbstractRecord
{
    public $local_id;
    public $external_id;
    public $record_type;
    public $endpoint_uri;

    protected function afterDelete()
    {
        $skipTypes = array('File', 'Element', 'ElementSet', 'ItemType');
        if(! in_array($this->record_type, $skipTypes)) {
            if(class_exists($this->record_type)) {
                $record = $this->getDb()->getTable($this->record_type)->find($this->local_id);
                if($record) {
                    $record->delete();
                }
            }
        }
    }
}
<?php

class ApiRecordIdMap extends Omeka_Record_AbstractRecord
{
    public $local_id;
    public $external_id;
    public $record_type;
    public $endpoint_uri;

    protected function afterDelete()
    {
        $record = $this->getDb()->getTable($this->record_type)->find($this->local_id)->delete();
    }
}
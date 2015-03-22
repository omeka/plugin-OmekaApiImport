<?php

class OmekaApiImport extends Omeka_Record_AbstractRecord implements Zend_Acl_Resource_Interface
{
    public $status;
    public $endpoint_uri;
    public $date;

    protected function _initializeMixins()
    {
        // Add the search mixin.
        $this->_mixins[] = new Mixin_Timestamp($this, 'date', null);
    }
    public function getResourceId()
    {
	    return 'OmekaApiImport';
    }
}
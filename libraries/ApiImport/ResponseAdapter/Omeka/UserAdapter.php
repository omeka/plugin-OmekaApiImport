<?php

class ApiImport_ResponseAdapter_Omeka_UserAdapter extends ApiImport_ResponseAdapter_AbstractRecordAdapter
{

    protected $recordType = 'User';

    public function import()
    {
        if($this->record && $this->record->exists()) {
            //already mapped
            return;
        }

        //try by email address
        if($user = $this->db->getTable('User')->findByEmail($this->responseData['email']) ) {
            $this->record = $user;
        } else {
            $this->record = new User;
            foreach($this->responseData as $key=>$value) {
                if($key != 'id' && $key != 'external_resources' && $key != 'url') {
                    $this->record->$key = $value;
                }
            }
            try {
                $this->record->save(true);
                $this->addOmekaApiImportRecordIdMap();
            } catch(Exception $e) {
                _log($e->getMessage());
            }
        }
    }

    public function externalId()
    {
        return $this->responseData['id'];
    }
}
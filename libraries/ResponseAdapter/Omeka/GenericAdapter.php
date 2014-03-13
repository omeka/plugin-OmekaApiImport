<?php

class ApiImport_ResponseAdapter_Omeka_GenericAdapter extends ApiImport_ResponseAdapter_RecordAdapterAbstract
                                  implements ApiImport_ResponseAdapter_RecordAdapterInterface
{
    /**
     * Properties in the API that refer to users should be added to $userProperties
     * so the import can look up the correct user or import if not already imported
     * @var array
     */
    protected $userProperties = array();
    
    /**
     * Properties that refer to resources such as items should be added to $resourceProperties
     * so the import can look up the correct referent. Assumes that referents are already imported
     * array('property', 'recordType')
     * @var array
     */
    protected $resourceProperties = array();
    protected $skipProperties = array('id', 'external_resources', 'url');
    
    public function import()
    {
        if(! $this->record) {
            $this->record = new $this->recordType;
            $this->setFromResponseData();
            try {
                $this->record->save(true);
                $this->addApiRecordIdMap();
            } catch (Exception $e) {
                _log($e);
            }
        }
    }
    
    public function setResourceProperties($resourceProperties)
    {
        $this->resourceProperties = $resourceProperties;
    }
    
    public function setUserProperties($userProperties)
    {
        $this->userProperties = $userProperties;
    }
    
    public function addSkipProperty($property)
    {
        $this->skipProperties[] = $property;
    }
    
    public function externalId()
    {
        return $this->responseData['id'];
    }    
    
    protected function setFromResponseData()
    {
        $allSkipProperties = array_merge($this->resourceProperties, $this->userProperties, $this->skipProperties);
        foreach($this->responseData as $key=>$value) {
            if(!in_array($key, $allSkipProperties)) {
                $this->record->$key = $value;
            }
            if(in_array($key, $this->userProperties)) {
                $prop = $key . '_id';
                $this->record->$prop = $this->getLocalUserId($value);
            }
            if(array_key_exists($key, $this->resourceProperties)) {
                $prop = $key . '_id';
                $this->record->$prop = $this->getLocalResourceId($value, $this->resourceProperties[$key]);
            }
        }
    }
    
    protected function getLocalResourceId($resourceData, $type)
    {
        $remoteId = $resourceData['id'];
        $localRecord = $this->db->getTable('ApiRecordIdMap')->localRecord($type, $remoteId, $this->endpointUri);
        return $localRecord->id;
    }
    
    protected function getLocalUserId($userData)
    {
        $userId = $userData['id'];
        $localUser = $this->db->getTable('ApiRecordIdMap')->localRecord('User', $userId, $this->endpointUri);
        if($localUser) {
            return $localUser->id;
        } else {
            $response = $this->service->users->get($userId);
            if($response->getStatus() == 200) {
                $responseData = json_decode($response->getBody(), true);
                $adapter = new ApiImport_ResponseAdapter_Omeka_UserAdapter($responseData, $this->endpointUri);
                $adapter->import();
                return $adapter->record->id;
            } else {
                _log($response->getMessage(), Zend_Log::INFO);
            }
        }
    }
}

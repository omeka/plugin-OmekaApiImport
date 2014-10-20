<?php

/**
 * A generic adapter for taking responses from an Omeka API endpoint and inserting/updating a record
 * If the response data is flat, or only goes down to Omeka a record or a user, this works for most
 * plugins' data. Processing that requires more following-your-nose through the response data
 * calls for its own adapter
 *
 */
class ApiImport_ResponseAdapter_Omeka_GenericAdapter extends ApiImport_ResponseAdapter_AbstractRecordAdapter
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

    /**
     * Properties in the response to ignore when setting record data from the response
     * @var array
     */
    protected $skipProperties = array('id', 'external_resources', 'url');

    public function import()
    {
        if(! $this->record) {
            $this->record = new $this->recordType;
        }
        $this->setFromResponseData();
        $this->record->save(true);
        $this->addOmekaApiImportRecordIdMap();
        return $this->record;
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

    /**
     * Sets data for a record from the JSON response from on Omeka API
     *
     * Array values for a property are json encoded, unless they are marked for skipping as
     * resource properties (e.g. 'item: {}') or user properties (e.g. 'owner: {}' or 'modified_by: {}')
     * Slightly more complex processing can usually be handled by some preprocessing of the responseData
     * before passing it in.
     */
    protected function setFromResponseData()
    {
        $allSkipProperties = array_merge($this->resourceProperties, $this->userProperties, $this->skipProperties);
        foreach($this->responseData as $key=>$value) {
            if(!in_array($key, $allSkipProperties)) {
                if(is_array($value)) {
                    $this->record->$key = json_encode($value);
                } else {
                    $this->record->$key = $value;
                }
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

    /**
     * Dig up a local record ID based on data about the remote resource
     * @param array $resourceData
     * @param string $type The record type in Omeka
     */
    protected function getLocalResourceId($resourceData, $type)
    {
        $remoteId = $resourceData['id'];
        $localRecord = $this->db->getTable('OmekaApiImportRecordIdMap')->localRecord($type, $remoteId, $this->endpointUri);
        return $localRecord->id;
    }

    /**
     * Try to dig up or import a local user id corresponding to remote user data
     *
     * @param array $userData
     */
    protected function getLocalUserId($userData)
    {
        $userId = $userData['id'];
        $localUser = $this->db->getTable('OmekaApiImportRecordIdMap')->localRecord('User', $userId, $this->endpointUri);
        if($localUser) {
            return $localUser->id;
        } else {
            try {
                $response = $this->service->users->get($userId);
            } catch(Exception $e) {
                _log($e);
            }
            if($response->getStatus() == 200) {
                $responseData = json_decode($response->getBody(), true);
                $adapter = new ApiImport_ResponseAdapter_Omeka_UserAdapter($responseData, $this->endpointUri);
                $adapter->import();
                return $adapter->record->id;
            } else {
                _log(__("Failed importing user. Falling back to current user") . " " . $response->getStatus(). " " . $response->getMessage(), Zend_Log::INFO);
                //fallback to the user doing the import owning the record
                return current_user()->id;
            }
            _log(__("Failed looking up user. Falling back to current user."));
            return current_user()->id;
        }
    }
}

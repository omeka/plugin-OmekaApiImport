<?php

class ApiImport_ImportJob_Omeka extends Omeka_Job_AbstractJob
{
    protected $omeka;
    protected $availableResources;
    protected $endpointUri;
    protected $key;
    protected $importId;
    protected $import;

    public function perform()
    {
        _log("Beginning Import", Zend_Log::INFO);
        $this->import->status = 'in progress';
        $this->import->save();
        $this->omeka = new ApiImport_Service_Omeka($this->endpointUri);
        $this->omeka->setKey($this->key);
        $this->getAvailableResources();
        $importableResources = array(
                'element_sets'     => 'ApiImport_ResponseAdapter_Omeka_ElementSetAdapter',
                'elements'         => 'ApiImport_ResponseAdapter_Omeka_ElementAdapter',
                'item_types'       => 'ApiImport_ResponseAdapter_Omeka_ItemTypeAdapter',
                'collections'      => 'ApiImport_ResponseAdapter_Omeka_CollectionAdapter',
                'items'            => 'ApiImport_ResponseAdapter_Omeka_ItemAdapter'
                );

        $filterArgs = array('endpointUri' => $this->endpointUri, 'omeka_service' => $this->omeka );
        $importableResources = apply_filters('api_import_omeka_adapters', $importableResources, $filterArgs);
        foreach($importableResources as $resource=>$adapter) {
            if(in_array($resource, $this->availableResources)) {
                $this->importRecords($resource, $adapter);
            }
        }
        $this->import->status = 'completed';
        $this->import->save();
        _log("Done Importing", Zend_Log::INFO);
    }

    public function setKey($key)
    {
        $this->key = $key;
    }

    public function setEndpointUri($endpointUri)
    {
        $this->endpointUri = $endpointUri;
    }

    public function setImportId($importId)
    {
        $this->importId = $importId;
        $import = get_db()->getTable('OmekaApiImport')->find($importId);
        $this->import = $import;
    }

    /**
     * Go through the registers adapters for resources and do the import
     *
     * @param string $resource The API resource that the adapter will import
     * @param mixed $adapter String or subclass of ApiImport_ResponseAdapter_RecordAdapterAbstract. If string,
     * the name of such a subclass
     */
    protected function importRecords($resource, $adapter)
    {
        $this->import->status = "Importing $resource";
        $this->import->save();
        if(is_string($adapter)) {
            try {
                $adapter = new $adapter(null, $this->endpointUri);
            } catch(Exception $e) {
                $this->import->status = 'error';
                $this->import->save();
                _log($e);
            }
        }
        $adapter->setService($this->omeka);
        $page = 1;

        do {
            _log("Importing $resource page $page", Zend_Log::INFO);
            $response = $this->omeka->$resource->get(null, array('page' => $page));
            if($response->getStatus() == 200) {
                $responseData = json_decode($response->getBody(), true);
                foreach($responseData as $recordData) {
                    _log("$resource ID: " . $recordData['id'], Zend_Log::INFO);
                    $adapter->resetResponseData($recordData);
                    try {
                        $adapter->import();
                    } catch (Exception $e) {
                        $this->import->status = 'error';
                        $this->import->save();
                        _log($e);
                    }
                }
            } else {
                _log($response->getStatus() . ": " . $response->getMessage());
            }
            $page++;
            //sleep for a little while so we don't look like we're DoS attacking
            usleep(200);
        } while ( $this->hasNextPage($response));
    }

    /**
     * Check what resources the remote Omeka API exposes
     */
    protected function getAvailableResources()
    {
        $response = $this->omeka->resources->get();
        if($response->getStatus() == 200) {
            $json = json_decode($response->getBody(), true);
            $this->availableResources = array_keys($json);
        }
    }

    protected function hasNextPage($response)
    {
        $linksHeading = $response->getHeader('Link');
        return strpos($linksHeading, 'rel="next"');
    }
}

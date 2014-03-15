<?php


class ApiImport_ImportProcess_Omeka extends Omeka_Job_Process_AbstractProcess
{
    protected $endpointUri;
    protected $key;
    protected $omeka;
    protected $availableResources;

    public function run($args)
    {
        _log("Beginning Import", Zend_Log::INFO);
        require_once( PLUGIN_DIR . '/ApiImport/libraries/ResponseAdapter/RecordAdapterInterface.php');
        require_once( PLUGIN_DIR . '/ApiImport/libraries/ResponseAdapter/RecordAdapterAbstract.php');
        require_once( PLUGIN_DIR . '/ApiImport/libraries/ZendService_Omeka/Omeka.php');
        
        foreach( glob(PLUGIN_DIR . "/ApiImport/libraries/ResponseAdapter/Omeka/*.php") as $adapterClass) {
            include($adapterClass);
        }        
        
        $this->endpointUri = $args['endpointUri'];
        $this->key = $args['key'];
        $this->omeka = new Zend_Service_Omeka($this->endpointUri);
        $this->omeka->setKey($this->key);
        $this->getAvailableResources();
        $importableResources = array(
                'element_sets'     => 'ApiImport_ResponseAdapter_Omeka_ElementSetAdapter',
                'elements'         => 'ApiImport_ResponseAdapter_Omeka_ElementAdapter',
                'item_types'       => 'ApiImport_ResponseAdapter_Omeka_ItemTypeAdapter',
                'collections'      => 'ApiImport_ResponseAdapter_Omeka_CollectionAdapter',
                'items'            => 'ApiImport_ResponseAdapter_Omeka_ItemAdapter'
                );

        $filterArgs = array('endpointUri' => $this->endpointUri, 'omeka_service' => $this->service );
        $importableResources = apply_filters('api_import_omeka_adapters', $importableResources, $filterArgs);
        foreach($importableResources as $resource=>$adapter) {
            if(in_array($resource, $this->availableResources)) {
                $this->importRecords($resource, $adapter);
            }
        }
        _log("Done Importing", Zend_Log::INFO);
    }

    protected function importRecords($resource, $adapter)
    {
        if(is_string($adapter)) {
            try {
                $adapter = new $adapter(null, $this->endpointUri);
            } catch(Exception $e) {
                _log($e);
            }
            $adapter->setService($this->omeka);
        }

        $page = 1;
        do {
            _log("Importing $resource page $page", Zend_Log::INFO);
            $response = $this->omeka->$resource->get(null, array('page' => $page));
            if($response->getStatus() == 200) {
                $responseData = json_decode($response->getBody(), true);
                foreach($responseData as $recordData) {
                    $adapter->resetResponseData($recordData);
                    $adapter->import();
                }
            } else {
                _log($response->getMessage());
            }
            $page++;
            usleep(1000);
        } while ( $this->hasNextPage($response));
    }

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

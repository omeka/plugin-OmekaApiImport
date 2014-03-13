<?php


class ApiImport_ImportProcess_Omeka extends Omeka_Job_Process_AbstractProcess
{
    protected $endpointUri;
    protected $key;
    protected $omeka;

    public function run($args)
    {
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
        
        $this->importRecords('element_sets', 'ApiImport_ResponseAdapter_Omeka_ElementSetAdapter');
        $this->importRecords('elements', 'ApiImport_ResponseAdapter_Omeka_ElementAdapter');
        $this->importRecords('item_types', 'ApiImport_ResponseAdapter_Omeka_ItemTypeAdapter');
        $this->importRecords('collections', 'ApiImport_ResponseAdapter_Omeka_CollectionAdapter');
        
        $this->importRecords('items', 'ApiImport_ResponseAdapter_Omeka_ItemAdapter');
        _log("Done Importing", Zend_Log::INFO);
    }

    protected function importRecords($resource, $adapter)
    {
        $adapter = new $adapter(null, $this->endpointUri);
        $adapter->setService($this->omeka);
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

    
    protected function hasNextPage($response)
    {
        $linksHeading = $response->getHeader('Link');
        return strpos($linksHeading, 'rel="next"');
    }
}

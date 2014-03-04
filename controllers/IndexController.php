<?php
class ApiImport_IndexController extends Omeka_Controller_AbstractActionController
{
    public function init()
    {
        include('/var/www/Omeka/plugins/ApiImport/libraries/ResponseAdapter/RecordAdapterInterface.php');
        include('/var/www/Omeka/plugins/ApiImport/libraries/ResponseAdapter/RecordAdapterAbstract.php');
        include('/var/www/Omeka/plugins/ApiImport/libraries/ResponseAdapter/Omeka/ItemAdapter.php');
        include('/var/www/Omeka/plugins/ApiImport/libraries/ResponseAdapter/Omeka/CollectionAdapter.php');
    }

    public function itemAction()
    {
        $omeka = $this->getOmekaService('http://localhost/Omeka/api');
        $omeka->setKey('key');
        $response = $omeka->items->get(1811);
        $adapter = new ApiImport_ResponseAdapter_Omeka_ItemAdapter($response, 'http://localhost/Omeka/api');
        $adapter->import();
        $responseArray = json_decode($response->getBody(), true);
        $this->view->test = $responseArray;
    }

    public function collectionAction()
    {
        $omeka = $this->getOmekaService('http://localhost/Omeka/api');
        $omeka->setKey('key');
        $response = $omeka->collections->get(1);
        $adapter = new ApiImport_ResponseAdapter_Omeka_CollectionAdapter($response, 'http://localhost/Omeka/api');
        $adapter->import();
        $responseArray = json_decode($response->getBody(), true);
        $this->view->test = $responseArray;
    }

    /**
     * Gets an Omeka Api Service dependent on PHP and Zend versions
     */
    protected function getOmekaService($apiBaseUrl)
    {
        include('/var/www/Omeka/plugins/ApiImport/vendor/ZendService_Omeka/Omeka.php');
        include('/var/www/Omeka/plugins/ApiImport/vendor/ZendService_Omeka/library/ZendService/Omeka/Omeka.php');
        return new Zend_Service_Omeka($apiBaseUrl);
        //return new ZendService\Omeka\Omeka($apiBaseUrl);
    }
}
<?php
class ApiImport_IndexController extends Omeka_Controller_AbstractActionController
{
    public function init()
    {

        include( PLUGIN_DIR . '/ApiImport/libraries/ResponseAdapter/RecordAdapterInterface.php');
        include( PLUGIN_DIR . '/ApiImport/libraries/ResponseAdapter/RecordAdapterAbstract.php');
        include( PLUGIN_DIR . '/ApiImport/libraries/ZendService_Omeka/Omeka.php');
        foreach(glob(PLUGIN_DIR . "/ApiImport/libraries/ResponseAdapter/Omeka/*.php") as $adapterClass) {
            include($adapterClass);
        }        
    }
    
    public function itemAction()
    {
        if(isset($_POST['submit'])) {
            $omeka = new Zend_Service_Omeka($_POST['api_url']);
            $omeka->setKey($_POST['key']);
            $response = $omeka->items->get($_POST['item_id']);
            if($response->getStatus() == 200) {
                $responseData = json_decode($response->getBody(), true);
                $adapter = new ApiImport_ResponseAdapter_Omeka_ItemAdapter($responseData, $_POST['api_url']);
                $adapter->setService($omeka);
                $adapter->import();                
            } else {
                throw new Exception($response->getMessage());
            }
        }
    }
    
    public function esAction()
    {
            $omeka = new Zend_Service_Omeka('http://localhost/OFrontPage/api');
            $omeka->setKey('');
            $response = $omeka->element_set->get(4);
            if($response->getStatus() == 200) {
                $responseData = json_decode($response->getBody(), true);
                $adapter = new ApiImport_ResponseAdapter_Omeka_ElementSetAdapter($responseData, 'http://localhost/OFrontPage/api');
                $adapter->setService($omeka);
                $adapter->import();                
            } else {
                throw new Exception($response->getMessage());
            }        
    }
    
    public function elementAction()
    {
            $omeka = new Zend_Service_Omeka('http://localhost/OFrontPage/api');
            $omeka->setKey('');
            $response = $omeka->elements->get(86);
            if($response->getStatus() == 200) {
                $responseData = json_decode($response->getBody(), true);
                $adapter = new ApiImport_ResponseAdapter_Omeka_ElementAdapter($responseData, 'http://localhost/OFrontPage/api');
                $adapter->setService($omeka);
                $adapter->import();                
            } else {
                throw new Exception($response->getMessage());
            }        
    }    
}
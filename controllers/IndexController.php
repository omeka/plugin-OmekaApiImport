<?php
class ApiImport_IndexController extends Omeka_Controller_AbstractActionController
{
    public function init()
    {

    }
    
    
    public function indexAction()
    {
        if(isset($_POST['submit'])) {
            set_option('api_import_override_element_set_data', $_POST['api_import_override_element_set_data']);
            $args = array('endpointUri' => $_POST['api_url'], 'key' => $_POST['key']);
            $process = Omeka_Job_Process_Dispatcher::startProcess('ApiImport_ImportProcess_Omeka', null, $args);
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

    public function typesAction()
    {
            $omeka = new Zend_Service_Omeka('http://localhost/OFrontPage/api');
            $omeka->setKey('');
            $response = $omeka->item_types->get(1);
            if($response->getStatus() == 200) {
                $responseData = json_decode($response->getBody(), true);
                $adapter = new ApiImport_ResponseAdapter_Omeka_ItemTypeAdapter($responseData, 'http://localhost/OFrontPage/api');
                $adapter->setService($omeka);
                $adapter->import();                
            } else {
                throw new Exception($response->getMessage());
            }                
    }
}
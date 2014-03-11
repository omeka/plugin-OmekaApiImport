<?php
class ApiImport_IndexController extends Omeka_Controller_AbstractActionController
{
    public function init()
    {
        include( PLUGIN_DIR . '/ApiImport/libraries/ResponseAdapter/RecordAdapterInterface.php');
        include( PLUGIN_DIR . '/ApiImport/libraries/ResponseAdapter/RecordAdapterAbstract.php');
        include( PLUGIN_DIR . '/ApiImport/libraries/ResponseAdapter/Omeka/ItemAdapter.php');
        include( PLUGIN_DIR . '/ApiImport/libraries/ResponseAdapter/Omeka/CollectionAdapter.php');
        include( PLUGIN_DIR . '/ApiImport/libraries/ResponseAdapter/Omeka/ElementSetAdapter.php');
        include( PLUGIN_DIR . '/ApiImport/libraries/ZendService_Omeka/Omeka.php');
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
            $response = $omeka->element_sets->get(4);
            if($response->getStatus() == 200) {
                $responseData = json_decode($response->getBody(), true);
                $adapter = new ApiImport_ResponseAdapter_Omeka_ElementSetAdapter($responseData, 'http://localhost/OFrontPage/api');
                $adapter->setService($omeka);
                $adapter->import();                
            } else {
                throw new Exception($response->getMessage());
            }        
    }
}
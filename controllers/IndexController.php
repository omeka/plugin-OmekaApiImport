<?php
class ApiImport_IndexController extends Omeka_Controller_AbstractActionController
{
    public function init()
    {
        include( PLUGIN_DIR . '/ApiImport/libraries/ResponseAdapter/RecordAdapterInterface.php');
        include( PLUGIN_DIR . '/ApiImport/libraries/ResponseAdapter/RecordAdapterAbstract.php');
        include( PLUGIN_DIR . '/ApiImport/libraries/ResponseAdapter/Omeka/ItemAdapter.php');
        include( PLUGIN_DIR . '/ApiImport/libraries/ResponseAdapter/Omeka/CollectionAdapter.php');
        include( PLUGIN_DIR . '/ApiImport/libraries/ZendService_Omeka/Omeka.php');
    }
    
    public function itemAction()
    {
        if(isset($_POST['submit'])) {
            $omeka = new Zend_Service_Omeka($_POST['api_url']);
            $omeka->setKey($_POST['key']);
            $response = $omeka->items->get($_POST['item_id']);
            $adapter = new ApiImport_ResponseAdapter_Omeka_ItemAdapter($response, $_POST['api_url']);
            $adapter->setService($omeka);
            $adapter->import();
        }
    }
}
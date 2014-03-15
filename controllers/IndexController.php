<?php
class ApiImport_IndexController extends Omeka_Controller_AbstractActionController
{

    public function indexAction()
    {
        $apiMapTable = $this->_helper->db->getTable('ApiRecordIdMap');
        $urls = $apiMapTable->getImportedEndpoints();
        if(isset($_POST['submit'])) {
            set_option('api_import_override_element_set_data', $_POST['api_import_override_element_set_data']);
            if(!empty($_POST['api_url'])) {
                $args = array('endpointUri' => $_POST['api_url'], 'key' => $_POST['key']);
                $process = Omeka_Job_Process_Dispatcher::startProcess('ApiImport_ImportProcess_Omeka', null, $args);
            }
            if(isset($_POST['undo'])) {
                foreach($_POST['undo'] as $endpointIndex) {
                    $mapRecords = $apiMapTable->findBy(array('endpoint_uri' => $urls[$endpointIndex]));
                    foreach($mapRecords as $record) {
                        $record->delete();
                    }
                }
            }
        }
        $process = $this->_helper->db->getTable('Process')
                                        ->findBy(array('class' => 'ApiImport_ImportProcess_Omeka',
                                                       'sort_field' => 'id',
                                                       'sort_dir' => 'd'
                                                      ), 1
                                                );
        $this->view->process = $process[0];
        //reget the imported urls in case the submit deleted some
        $urls = $apiMapTable->getImportedEndpoints();
        $this->view->urls = $urls;
    }
}
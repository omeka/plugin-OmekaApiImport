<?php
class OmekaApiImport_IndexController extends Omeka_Controller_AbstractActionController
{

    public function indexAction()
    {
        //check cli path
        try {
            Omeka_Job_Process_Dispatcher::getPHPCliPath();
        } catch(RuntimeException $e) {
            $this->_helper->flashMessenger(__("The background.php.path in config.ini is not valid. The correct path must be set for the import to work."), 'error');
        }
        $apiMapTable = $this->_helper->db->getTable('OmekaApiImportRecordIdMap');
        $urls = $apiMapTable->getImportedEndpoints();
        if(isset($_POST['submit'])) {
            set_option('omeka_api_import_override_element_set_data', $_POST['omeka_api_import_override_element_set_data']);
            if(!empty($_POST['api_url'])) {
                //do a quick check for whether the API is active
                $client = new Zend_Http_Client;
                $client->setUri($_POST['api_url'] . '/site');
                $response = json_decode($client->request()->getBody(), true);

                if(isset($response['message'])) {
                    $this->_helper->flashMessenger(__("The API at %s is not active", $_POST['api_url']), 'error');

                } else {
                    $args = array('endpointUri' => $_POST['api_url'], 'key' => $_POST['key']);
                    try {
                        Zend_Registry::get('bootstrap')->getResource('jobs')
                            ->sendLongRunning('ApiImport_ImportJob_Omeka', $args);
                    } catch(Exception $e) {
                        _log($e);
                    }
                }
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
                                        ->findBy(array(
                                                       'sort_field' => 'id',
                                                       'sort_dir' => 'd'
                                                      ), 1
                                                );
        if (!empty($process)) {
            $firstProcess = $process[0];
            $args = unserialize($firstProcess->args);
            
            if (isset($args['job'])) {
                $job = json_decode($args['job'], true);
                if ($job['className'] == 'ApiImport_ImportJob_Omeka') {
                    $this->view->job = $job;
                    $this->view->process = $firstProcess;
                }
            } else {
                debug(print_r($args, true));
            }
        }
        //reget the imported urls in case the submit deleted some
        $urls = $apiMapTable->getImportedEndpoints();
        $this->view->urls = $urls;
    }
}
<?php
class OmekaApiImport_IndexController extends Omeka_Controller_AbstractActionController
{

    protected $_pluginConfig = null;

    public function indexAction()
    {
        //check cli path
        try {
            Omeka_Job_Process_Dispatcher::getPHPCliPath();
        } catch(RuntimeException $e) {
            $this->_helper->flashMessenger(__("The background.php.path in config.ini is not valid. The correct path must be set for the import to work."), 'error');
        }

        if(isset($_POST['submit'])) {
            set_option('omeka_api_import_override_element_set_data', $_POST['omeka_api_import_override_element_set_data']);
            if(!empty($_POST['api_url'])) {
                $endpointUri = rtrim(trim($_POST['api_url']), '/');
                //do a quick check for whether the API is active
                $client = new Zend_Http_Client;
                $client->setUri($endpointUri . '/site');
                if (!empty($_POST['key'])) {
                    $client->setParameterGet('key', trim($_POST['key']));
                }

                $error = null;
                try {
                    $response = $client->request();
                    $body = json_decode($response->getBody(), true);
                } catch (Zend_Http_Client_Exception $e) {
                    $error = $e->getMessage();
                }

                if ($error) {
                    // we already hit an error, skip the other checks
                } else if ($response->isError()) {
                    $message = isset($body['message']) ? $body['message'] : null;
                    if ($message == 'API is disabled') {
                        $error = __('The API at %s is not active', $endpointUri);
                    } else if ($message == 'Invalid key.')  {
                        $error = __('The provided API key was invalid');
                    } else {
                        $error = __('Error accessing the API at %s (%s), check that you have the right URL', $endpointUri, $response->getStatus());
                    }
                } else if ($body === null) {
                    $error = __('The API response was not JSON, check that you have the right URL');
                } else if (!isset($body['omeka_url'])) {
                    $error = __('%s is not an Omeka Classic API URL, check that you have the right URL', $endpointUri);
                } else if ($body['omeka_url'] === WEB_ROOT) {
                    $error = __('You cannot import a site into itself');
                }

                if ($error) {
                    $this->_helper->flashMessenger($error, 'error');
                } else {
                    $import = new OmekaApiImport;
                    $import->endpoint_uri = $endpointUri;
                    $import->status = 'starting';
                    $import->save();

                    $pluginConfig = $this->_getPluginConfig();
                    $importUsers = isset($pluginConfig['importUsers']) ? $pluginConfig['importUsers'] : true;

                    $args = array(
                        'endpointUri' => $endpointUri,
                        'key' => trim($_POST['key']),
                        'importId' => $import->id,
                        'importUsers' => $importUsers,
                    );
                    $jobsDispatcher = Zend_Registry::get('bootstrap')->getResource('jobs');
                    $jobsDispatcher->setQueueNameLongRunning('imports');
                    try {                       
                        $jobsDispatcher->sendLongRunning('ApiImport_ImportJob_Omeka', $args);
                    } catch(Exception $e) {
                        $import->status = 'error';
                        $import->save();
                        _log($e);
                    }
                }
            }
            if(isset($_POST['undo'])) {
                $urls = $this->_helper->db->getTable('OmekaApiImport')->getImportedEndpoints();
                foreach($_POST['undo'] as $endpointIndex) {
                    $mapRecords = $this->_helper->db->getTable('OmekaApiImportRecordIdMap')->findBy(array('endpoint_uri' => $urls[$endpointIndex]));
                    foreach($mapRecords as $record) {
                        $record->delete();
                    }
                    $imports = $this->_helper->db->getTable('OmekaApiImport')->findBy(array('endpoint_uri' => $urls[$endpointIndex]));
                    foreach ($imports as $import) {
                        $import->delete();
                    }
                }
            }
        }

        if (! isset($import)) {
            $imports = $this->_helper->db->getTable('OmekaApiImport')->findBy(array('sort_field' => 'id', 'sort_dir' => 'd'), 1);
            if (empty($imports)) {
                $import = null;
            } else {
                $import = $imports[0];
            }
        }
        $this->view->import = $import;
        $urls = $this->_helper->db->getTable('OmekaApiImport')->getImportedEndpoints();
        $this->view->urls = $urls;
    }

    /**
     * Returns the plugin configuration
     *
     * @return array
     */
    protected function _getPluginConfig()
    {
        if (!$this->_pluginConfig) {
            $config = $this->getInvokeArg('bootstrap')->config->plugins;
            if ($config && isset($config->OmekaApiImport)) {
                $this->_pluginConfig = $config->OmekaApiImport->toArray();
            }
        }
        return $this->_pluginConfig;
    }
}

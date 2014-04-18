<?php

/**
 * A partial clone/backport of original ZendService_Omeka from Jim Safley https://github.com/jimsafley/ZendService_Omeka
 * that will run on PHP < 5.3 and serve the needs of GET requests only to import from other Omeka 2.x sites
 *
 */

class ApiImport_Service_Omeka
{
    /**
     * @var Zend_Http_Client
     */
    protected $httpClient;

    /**
     * @var array
     */
    protected $methods = array('get');

    /**
     * @var array
     */
    protected $callbacks = array();

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $apiBaseUrl;

    /**
     * @var string
     */
    protected $resource;

    /**
     * @var int
     */
    protected $id;

    /**
     * Constructor
     *
     * @param string $apiBaseUrl
     */
    public function __construct($apiBaseUrl)
    {
        $this->apiBaseUrl = $apiBaseUrl;

        // Set the callback for POST /files.
        $filesPost = function($omeka, $filename, $data, array $params = array()) {
            if ($omeka->getKey()) {
                $params = array_merge($params, array('key' => $omeka->getKey()));
            }
            $client = $omeka->getHttpClient()
            ->resetParameters()
            ->setEncType('multipart/form-data')
            ->setUri($omeka->getApiBaseUrl() . '/files')
            ->setMethod(Http\Request::METHOD_POST)
            ->setFileUpload($filename, 'file')
            ->setParameterPost(array('data' => $data))
            ->setParameterGet($params);
            return $client->send();
        };
        $this->setCallback('files', 'post', $filesPost);
    }

    /**
     * Proxy resources
     *
     * @param string $resource
     * @return Omeka
     */
    public function __get($resource)
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * Method overloading
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (!in_array($method, $this->methods)) {
            throw new Exception('Invalid method.');
        }
        // Check for a callback.
        if (array_key_exists($this->resource, $this->callbacks)
                && array_key_exists($method, $this->callbacks[$this->resource])
        ) {
            $callback = $this->callbacks[$this->resource][$method];
            // Prepend this Omeka client to the argument list.
            array_unshift($args, $this);
            return call_user_func_array($callback, $args);
        }
        return call_user_func_array(array($this, $method), $args);
    }

    /**
     * Set custom behavior for a resource/method.
     *
     * @param string $resource
     * @param string $method
     * @param \Closure $callback
     */
    public function setCallback($resource, $method, \Closure $callback)
    {
        $this->callbacks[$resource][$method] = $callback;
    }

    /**
     * Get the HTTP client.
     *
     * @return Zend_Http_Client
     */
    public function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = new Zend_Http_Client;
        }
        return $this->httpClient;
    }

    /**
     * Get the API base URL.
     *
     * @return string
     */
    public function getApiBaseUrl()
    {
        return $this->apiBaseUrl;
    }

    /**
     * Set the authentication key.
     *
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Get the authentication key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Make a GET request.
     *
     * Setting the first argument as an integer will make a request for one
     * resource, while not setting the first argument (or setting it as
     * an array of parameters) will make a request for multiple resources.
     *
     * @param integer|array $id
     * @param array $params
     * @return Zend\Http\Response
     */
    protected function get($id = null, array $params = array())
    {
        if (is_array($id)) {
            $params = $id;
        } else {
            $this->id = $id;
        }
        $client = $this->prepare($params);
        return $client->request();
    }

    /**
     * Prepare and return the API client.
     *
     * @param string $method
     * @param array $params
     * @return Zend_Http_Client
     */
    protected function prepare(array $params = array())
    {
        $method = 'GET';
        if (!$this->resource) {
            throw new Exception('A resource must be set before making a request.');
        }
        $path = '/' . $this->resource;

        if ($this->id) {
            $path = $path . '/' . $this->id;
        }
        $client = $this->getHttpClient()
        ->resetParameters()
        ->setUri($this->apiBaseUrl . $path)
        ->setMethod($method);
        if ($this->key) {
            $params = array_merge($params, array('key' => $this->key));
        }
        $client->setParameterGet($params);
        return $client;
    }
}

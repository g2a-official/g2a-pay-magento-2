<?php
/**
 * G2A Pay default request client.
 *
 * @category    G2A
 * @package     G2A_Pay
 * @author      G2A Team
 * @copyright   Copyright (c) 2016 G2A.COM
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace G2A\Pay\Model\Client;

/**
 * Class Curl.
 * @package G2A\Pay\Model\Client
 */
class Curl
{
    const METHOD_GET    = 'GET';
    const METHOD_POST   = 'POST';
    const METHOD_PUT    = 'PUT';
    const METHOD_PATCH  = 'PATCH';
    const METHOD_DELETE = 'DELETE';

    /** @var resource */
    protected $_curl;

    /** @var  string */
    protected $_url;

    /** @var string */
    protected $_method;

    /** @var array */
    protected $_headers;

    /** @var  array|null */
    protected $_data;

    /**
     * Setter for url.
     *
     * @param $url
     */
    public function setUrl($url)
    {
        $this->_url = $url;
    }

    /**
     * Setter for method.
     *
     * @param $method
     */
    public function setMethod($method)
    {
        $this->_method = $method;
    }

    /**
     * Setter for headers.
     *
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->_headers = $headers;
    }

    /**
     * Setter for data.
     *
     * @param $data
     */
    public function setData($data)
    {
        $this->_data = $data;
    }

    /**
     * Sends post request and return array data from response json.
     *
     * @param null $data
     * @return array
     * @throws \G2A\Pay\Exception\Error
     */
    public function request($data = null)
    {
        if ($data !== null) {
            $this->setData($data);
        }

        $this->configure();
        $response = $this->execute();

        if (!$response) {
            throw new \G2A\Pay\Exception\Error('Request error: ' . $this->getLastError());
        }

        $result = json_decode($response, true);

        if (!is_array($result)) {
            throw new \G2A\Pay\Exception\Error('Wrong response: ' . $result);
        }

        return $result;
    }

    /**
     * Init and return curl resource.
     *
     * @return resource
     */
    protected function resource()
    {
        if (is_null($this->_curl)) {
            $this->_curl = curl_init();
        }

        return $this->_curl;
    }

    /**
     * Set curl option.
     *
     * @param $option
     * @param null $value
     */
    protected function setOption($option, $value = null)
    {
        if (is_array($option)) {
            curl_setopt_array($this->resource(), $option);

            return;
        }
        curl_setopt($this->resource(), $option, $value);
    }

    /**
     * Configure curl request method.
     */
    private function configureMethod()
    {
        if (self::METHOD_POST === $this->_method) {
            $this->setOption(CURLOPT_POST, 1);
        } elseif (in_array($this->_method, [
                self::METHOD_PUT,
                self::METHOD_PATCH,
                self::METHOD_DELETE,
            ]
            )) {
            $this->setOption(CURLOPT_CUSTOMREQUEST, $this->_method);
        }
    }

    /**
     * Configure curl headers.
     */
    private function configureHeaders()
    {
        $headers = [];
        if (empty($this->_headers)) {
            $this->setOption(CURLOPT_HTTPHEADER, $headers);

            return;
        }
        foreach ($this->_headers as $name => $header) {
            if (is_string($name)) {
                $headers[] = "{$name}:{$header}";
                continue;
            }
            $headers[] = $header;
        }
        $this->setOption(CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Configure curl request.
     */
    private function configureRequest()
    {
        $this->setOption(CURLOPT_URL, $this->_url);
        $this->setOption(CURLOPT_RETURNTRANSFER, 1);
        $this->setOption(CURLOPT_VERBOSE, 0);
    }

    /**
     * Configure curl data.
     */
    private function configureData()
    {
        if (!empty($this->_data) && in_array($this->_method, [
                self::METHOD_POST,
                self::METHOD_PUT,
                self::METHOD_PATCH,
            ])) {
            $data = is_array($this->_data) ? http_build_query($this->_data) : $this->_data;
            $this->setOption(CURLOPT_POSTFIELDS, $data);
        }
    }

    /**
     * Configure curl.
     */
    protected function configure()
    {
        $this->configureRequest();
        $this->configureMethod();
        $this->configureHeaders();
        $this->configureData();
    }

    /**
     * Execute curl request.
     *
     * @return mixed
     */
    protected function execute()
    {
        return curl_exec($this->resource());
    }

    /**
     * Get last curl error.
     *
     * @return string|null
     */
    public function getLastError()
    {
        return curl_error($this->resource());
    }
}

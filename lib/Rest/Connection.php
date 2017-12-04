<?php
namespace project5\Rest;

use Pest;
use project5\Xml\Provider as XmlProvider;
use project5\Provider\Entity;

class Connection
{
    protected $_host;
    protected $_port;

    protected $_xml = [];
    protected $_json = [];

    protected $_response = null;
    protected $_filename = null;

    public function __construct($host, $port = null)
    {
        $this->_host = $host;
        $this->_port = $port;
    }

    /**
     * @param $path
     * @return XmlProvider
     */
    public function xml($path)
    {
        $filename = null;
        if (is_string($this->_response)) {
            $filename = tempnam('/tmp', 'xml');
            file_put_contents($filename, $this->_response);
        } elseif (is_file($this->_filename)) {
            $filename = $this->_filename;
        }

        if ($filename) {
            return new XmlProvider($filename, $path);
        }


    }

    public function get($url)
    {
        $this->_filename = tempnam('/tmp', 'xml');
        $base = 'https://'.$this->_host.($this->_port ? (':'.$this->_port) : '').'/';
        $client = new Pest($base);
        $client->curl_opts[CURLOPT_FILE] = fopen($this->_filename, 'wb');

        $client->setupAuth('42bdae9a26284a98551f293463c2fe91905ac423', 's');
        $client->get($url);

        return $this;
    }


}
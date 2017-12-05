<?php
namespace project5\Upload;

use StasDovgodko\Uri;

class Resource
{
    /**
     * @var IStorage
     */
    private $storage;

    /**
     * @var Uri
     */
    private $uri;


    public function __construct(IStorage $storage, Uri $uri)
    {
        $this->storage = $storage;
        $this->uri = $uri;
    }

    /**
     * @return IStorage
     */
    public function getStorage()
    {
        return $this->storage;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getStorageUri()
    {
        return $this->storage->uri($this->uri);
    }
}
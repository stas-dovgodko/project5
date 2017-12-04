<?php
namespace project5\Provider\Cache\Tag;

use project5\Provider\Cache\ITag;

class Object implements ITag
{
    private $_object;

    public function __construct($object)
    {
        $this->_object = $object;
    }


    /**
     * @return string|null
     */
    public function getStateHash()
    {
        try {
            return md5(serialize($this->_object));
        } catch (\Exception $e) {
            return null; // not serializable
        }
    }
}
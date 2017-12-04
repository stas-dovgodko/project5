<?php
namespace project5\Provider\Cache\Tag;

use project5\Provider\Cache\ITag;

class Scalar implements ITag
{
    private $_var;

    public function __construct($value)
    {
        $this->_var = $value;
    }


    /**
     * @return string|null
     */
    public function getStateHash()
    {
        try {
            if ($this->_var !== null) {
                return md5(serialize($this->_var));
            } else {
                return null;
            }
        } catch (\Exception $e) {
            return null; // not serializable
        }

    }
}
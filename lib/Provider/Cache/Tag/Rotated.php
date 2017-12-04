<?php
namespace project5\Provider\Cache\Tag;

use project5\Provider\Cache\ITag;

class Rotated implements ITag
{
    private $_base;
    private $_seconds;

    /**
     * @param int $seconds
     * @param string|ITag $base
     */
    public function __construct($seconds, $base)
    {
        $this->_seconds = $seconds;
        $this->_base = $base;
    }


    /**
     * @return string|null
     */
    public function getStateHash()
    {
        if ($this->_base instanceof ITag) $base = $this->_base->getStateHash();
        elseif ($this->_base !== null) $base = (string)$this->_base;
        else $base = null;

        if ($base !== null) {
            return sprintf('%s_%s', (string)$base, ceil(time() / $this->_seconds));
        } else return null;

    }
}
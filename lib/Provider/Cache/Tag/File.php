<?php
namespace project5\Provider\Cache\Tag;

use project5\Provider\Cache\ITag;

class File implements ITag
{
    private $_filename;

    public function __construct($filename)
    {
        $this->_filename = $filename;
    }


    /**
     * @return string|null
     */
    public function getStateHash()
    {
        return is_file($this->_filename) ? md5(sprintf('%s_%s', $this->_filename, filemtime($this->_filename))) : null;
    }
}
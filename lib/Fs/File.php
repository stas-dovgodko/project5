<?php
    namespace project5\Fs;

    use project5\Provider\IEntity;

    class File implements IEntity
    {
        const TYPE_FILE = 'file';
        const TYPE_DIR  = 'dir';
        const TYPE_LINK = 'link';
        /**
         * @var \SplFileInfo
         */
        private $_info;

        public function __construct(\SplFileInfo $info)
        {
            $this->_info = $info;
        }


        public function serialize()
        {
            return $this->_info->getFilename();
        }

        /**
         * (PHP 5 &gt;= 5.1.0)<br/>
         * Constructs the object
         * @link http://php.net/manual/en/serializable.unserialize.php
         * @param string $serialized <p>
         * The string representation of the object.
         * </p>
         * @return void
         */
        public function unserialize($serialized)
        {
            $this->_info = new \SplFileInfo($serialized);
        }

        /**
         * (PHP 5 &gt;= 5.0.0)<br/>
         * Whether a offset exists
         * @link http://php.net/manual/en/arrayaccess.offsetexists.php
         * @param mixed $offset <p>
         * An offset to check for.
         * </p>
         * @return boolean true on success or false on failure.
         * </p>
         * <p>
         * The return value will be casted to boolean if non-boolean was returned.
         */
        public function offsetExists($offset)
        {
            return in_array($offset, [
                'filename',
                'size',
                'ctime',
                'mtime',
                'atime',
                'type',
            ]);
        }

        /**
         * (PHP 5 &gt;= 5.0.0)<br/>
         * Offset to retrieve
         * @link http://php.net/manual/en/arrayaccess.offsetget.php
         * @param mixed $offset <p>
         * The offset to retrieve.
         * </p>
         * @return mixed Can return all value types.
         */
        public function offsetGet($offset)
        {
            switch ($offset) {
                case 'filename':
                    return $this->_info->getFilename();
                case 'atime':
                    return $this->_info->getATime();
                case 'mtime':
                    return $this->_info->getMTime();
                case 'size':
                    return $this->_info->getSize();
                case 'ctime':
                    return $this->_info->getCTime();
                case 'type':
                    return $this->_info->getType();
            }
        }

        /**
         * (PHP 5 &gt;= 5.0.0)<br/>
         * Offset to set
         * @link http://php.net/manual/en/arrayaccess.offsetset.php
         * @param mixed $offset <p>
         * The offset to assign the value to.
         * </p>
         * @param mixed $value <p>
         * The value to set.
         * </p>
         * @return void
         */
        public function offsetSet($offset, $value)
        {
            throw new \LogicException('Can\'t reset file properties');
        }

        /**
         * (PHP 5 &gt;= 5.0.0)<br/>
         * Offset to unset
         * @link http://php.net/manual/en/arrayaccess.offsetunset.php
         * @param mixed $offset <p>
         * The offset to unset.
         * </p>
         * @return void
         */
        public function offsetUnset($offset)
        {
            throw new \LogicException('Can\'t unset file properties');
        }



        /**
         * @return mixed
         */
        public function getUid()
        {
            return md5($this->_info->getFilename().$this->_info->getMTime().$this->_info->getCTime());
        }
    }
<?php
    namespace project5\Provider;

    class Entity extends \ArrayObject implements IEntity
    {
        private $_uid;
        /**
         * @return string
         */
        public function getUid()
        {
            return $this->_uid;
        }

        /**
         * @param $uid
         * @return mixed
         */
        public function setUid($uid)
        {
            return $this->_uid = $uid;
        }
    }
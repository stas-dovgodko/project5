<?php

    namespace project5\Provider;

    interface IEntity extends \ArrayAccess, \Serializable
    {

        /**
         * @return mixed
         */
        public function getUid();
    }
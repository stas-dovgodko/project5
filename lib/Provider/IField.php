<?php
    namespace project5\Provider;

    interface IField {
        /**
         * Get title
         *
         * @return string
         */
        public function getTitle();

        /**
         * Get name
         *
         * @return string
         */
        public function getName();

        /**
         * Field can be empty
         *
         * @return bool
         */
        public function canBeEmpty();

        /**
         * Get description
         * 
         * @return string
         */
        public function getDescription();

        /**
         * Format value
         *
         * @param IEntity $object
         * @return string
         */
        public function get(IEntity $object);

        /**
         * @param IEntity $object
         * @param $value
         * @return mixed
         */
        public function set(IEntity $object, $value);

        /**
         * @return bool
         */
        public function isSurrogate();

        /**
         * @param mixed $value
         * @param string $error Error message if value does not OK
         * @return mixed
         */
        public function validate($value, &$error = null);
    }


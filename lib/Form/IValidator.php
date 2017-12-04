<?php

    namespace project5\Form;

    interface IValidator {

        /**
         * @param array $params
         * @return string
         */
        public function getErrorDefaultMessage(array &$params = []);

        /**
         * @param $value
         * @param string $message
         * @return boolean True if valid
         */
        public function validate($value, &$message = null);
    }
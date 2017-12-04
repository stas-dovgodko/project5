<?php
    namespace project5\Provider;

    interface IComparer
    {
        /**
         * @param $value
         * @return bool
         */
        public function match($value);
    }
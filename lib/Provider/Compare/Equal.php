<?php
    namespace project5\Provider\Compare;

    use project5\Provider\IComparer;

    class Equal implements IComparer
    {
        private $_match;
        public function __construct($match)
        {
            $this->_match = $match;
        }

        public function getMatch()
        {
            return $this->_match;
        }

        /**
         * @param $value
         * @return bool
         */
        public function match($value)
        {
            return ($this->_match == $value);
        }
    }
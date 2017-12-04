<?php
    namespace project5\Provider\Compare;

    use project5\Provider\IComparer;

    class Not implements IComparer
    {
        /**
         * @var IComparer
         */
        private $_comparer;
        public function __construct(IComparer $comparer)
        {
            $this->_comparer = $comparer;
        }

        /**
         * @param $value
         * @return bool
         */
        public function match($value)
        {
            return !$this->_comparer->match($value);
        }
    }
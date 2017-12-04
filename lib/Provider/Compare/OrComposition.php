<?php
    namespace project5\Provider\Compare;

    use project5\Provider\IComparer;

    class OrComposition implements IComparer
    {
        /**
         * @var IComparer[]
         */
        private $_comparers = [];
        public function __construct(IComparer $a1, IComparer $a2)
        {
            foreach(func_get_args() as $comparer) {
                $this->add($comparer);
            }
        }

        public function add(IComparer $comparer)
        {
            $this->_comparers[] = $comparer;
        }

        /**
         * @param $value
         * @return bool
         */
        public function match($value)
        {
            foreach($this->_comparers as $comparer) {
                if ($comparer->match($value)) return true;
            }

            return false;
        }
    }
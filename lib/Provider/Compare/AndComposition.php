<?php
    namespace project5\Provider\Compare;

    use project5\Provider\IComparer;

    class AndComposition implements IComparer
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
            $return = true;
            foreach($this->_comparers as $comparer) {
                $return &= ($comparer->match($value));

                if (!$return) return false;
            }

            return $return;
        }
    }
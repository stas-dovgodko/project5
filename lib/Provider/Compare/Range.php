<?php
    namespace project5\Provider\Compare;

    use project5\Provider\IComparer;

    class Range implements IComparer
    {
        protected $from, $to;
        public function __construct($from = null, $to = null)
        {
            $this->from = $from;
            $this->to = $to;
        }

        public function getFrom()
        {
            return $this->from;
        }

        public function getTo()
        {
            return $this->to;
        }

        /**
         * @param $value
         * @return bool
         */
        public function match($value)
        {
            return (
                ($this->from === null || $this->from <= $value)
                &&
                ($this->to === null || $this->to >= $value)
            );
        }
    }
<?php
    namespace project5\Provider\Sort;

    interface ISorter
    {
        const IS_LESS = -1;
        const IS_SAME = 0;
        const IS_GREATER = 1;

        /**
         * @param $a1
         * @param $a2
         * @return int
         */
        public function compare($a1, $a2);
    }
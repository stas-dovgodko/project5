<?php
    namespace project5\Provider\Sort;

    class Asc implements ISorter
    {
        /**
         * @param $a1
         * @param $a2
         * @return int
         */
        public function compare($a1, $a2)
        {
            $cmp = strcmp($a1, $a2);

            if ($cmp == 1) return self::IS_GREATER;
            elseif ($cmp == -1) return self::IS_LESS;
            else return self::IS_SAME;
        }
    }
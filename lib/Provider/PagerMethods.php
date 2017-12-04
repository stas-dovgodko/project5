<?php
    namespace project5\Provider;

    /**
     * Methods for ICanBePaged, ICanBeCounted combination
     *
     * Class PagerMethods
     * @package project5\Provider
     */
    trait PagerMethods
    {
        protected $_pageNumber = 0;
        protected $_limit = 10;
        private $_total = null;

        /**
         * @param int $limit
         * @return void
         */
        public function setPagerLimit($limit)
        {
            $this->_limit = $limit;
        }

        /**
         * @param string $offset
         * @return void
         */
        public function setPagerOffset($offset)
        {
            $this->setPage((int)floor($offset / $this->_limit));
        }


        /**
         * @param int $count
         * @return int[]
         */
        public function getPages($count = 5)
        {
            $pages_count = $this->_getPagesCount();

            $min = max(0, $this->_pageNumber - floor(($count - 1)/2));
            $max = $min + $count - 1;

            if ($max >= $pages_count) {
                $max = $pages_count-1;
                $min = max(0, $max - $count + 1);
            }

            return range($min, $max);
        }

        protected function _getPagesCount()
        {
            if ($this->_total === null) {
                $this->_total = $this->getCount();
            }
            return (int)ceil(($this->_total?$this->_total:1) / $this->_limit);
        }

        /**
         * @param int $number
         * @return void
         */
        public function setPage($number) {
            $this->_pageNumber = $number;
        }

        /**
         * @return int
         */
        public function getPage()
        {
            return $this->_pageNumber;
        }

        /**
         * @return int
         */
        public function getPagerLimit()
        {
            return $this->_limit;
        }

        /**
         * @return int
         */
        public function getPagerOffset() {
            return $this->_pageNumber * $this->_limit;
        }

        /**
         * @return int
         */
        abstract public function getCount();

        /**
         * @return bool
         */
        public function canBeCounted() {
            return true;
        }

        /**
         * @return bool|null
         */
        public function hasNextPage()
        {
            return ($this->_pageNumber < ($this->_getPagesCount() - 1));
        }

        /**
         * @return bool|null
         */
        public function hasPrevPage() {
            return ($this->_pageNumber > 0);
        }

        /**
         * @return string|null
         */
        public function getNextPageOffset()
        {
            return $this->getNextPage() * $this->_limit;
        }

        /**
         * @return string|null
         */
        public function getPrevPageOffset()
        {
            return $this->getPrevPage() * $this->_limit;
        }

        /**
         * @return int
         */
        public function getFirstPage()
        {
            return 0;
        }

        /**
         * @return int
         */
        public function getLastPage()
        {
            return $this->_getPagesCount() - 1;
        }

        /**
         * @return int
         */
        public function getNextPage()
        {
            return min($this->_pageNumber + 1, $this->getLastPage());
        }

        /**
         * @return int
         */
        public function getPrevPage()
        {
            return max(0, $this->_pageNumber - 1);
        }

        /**
         * @return int
         */
        public function getFirstIndex()
        {
            return ($this->getPage() - 1) * $this->_limit + 1;
        }

        /**
         * @return int
         */
        public function getLastIndex()
        {
            if ($this->_total === null) {
                $this->_total = $this->getCount();
            }
            return min($this->getPage() * $this->_limit, $this->_total);
        }
    }
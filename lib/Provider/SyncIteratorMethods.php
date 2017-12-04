<?php
    namespace project5\Provider;

    trait SyncIteratorMethods
    {
        protected $_offset = null;
        protected $_limit = 10;

        protected $_prev = null;
        protected $_next = null;
        protected $_traversable = null;

        protected $_syncFilterCallback = null;

        protected $_filters = [];
        protected $_comparers = [];

        /**
         * @var Index
         */
        protected $_index;
        protected $_skip;

        protected $_pos;

        /**
         * @param string $offset
         * @return void
         */
        public function setPagerOffset($offset)
        {
            $this->_offset = $offset;
        }

        public function setPagerPage($page)
        {
            $this->setPagerOffset($this->getPagerLimit() * $page);
        }

        /**
         * @param int $limit
         * @return void
         */
        public function setPagerLimit($limit)
        {
            $this->_limit = $limit;
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
        public function getPagerOffset()
        {
            return $this->_offset;
        }

        /**
         * @return bool|null
         */
        public function hasNextPage()
        {
            $this->_sync();

            return ($this->_next !== null);
        }

        /**
         * @return bool|null
         */
        public function hasPrevPage()
        {
            return ($this->_offset > 0);
        }

        /**
         * @return string|null
         */
        public function getNextPageOffset()
        {
            $this->_sync();

            return $this->_next;
        }

        /**
         * @return string|null
         */
        public function getPrevPageOffset()
        {
            return max(0, $this->_offset - $this->_limit);
        }

        //protected abstract function _iterate($offset, $limit);

        /**
         * @param $cursor
         * @param array $extra
         * @return int
         */
        protected abstract function _reset($cursor, array $extra = []);
        protected abstract function _next(array &$extra);

        /**
         * @return int
         */
        public function getCount()
        {
            $c = 0;
            $this->_sync();
            foreach($this->iterate() as $r) $c++;

            return $c;
        }

        /**
         * @return bool
         */
        public function canBeCounted()
        {
            return false;
        }

        protected function _sync()
        {
            if (true || $this->_traversable === null) {
                $this->_traversable = [];

                $offset = $this->getPagerOffset();

                $index = $offset; $cursor = null; $extra = [];
                if ($offset && $this->_index) { // get cursor
                    $info = $this->_index->getCursor($index);
                    if (!$info) {
                        $info = $this->_index->getPrevCursor($index);
                    }
                    if ($info) list($cursor, $extra) = $info;
                }
                $index = $this->_reset($cursor, $extra);

                $this->_pos = $offset;

                //$offset = $this->getPagerOffset();
                $this->_next = $offset;
                $pos = 0;
                while(($item = $this->_next($extra)) && sizeof($this->_traversable) <= $this->_limit) {
                    /** @var $item IEntity */
                    if ($this->_filter($item)) {
                        if ($pos++ >= ($offset - $index)) {
                            $this->_traversable[] = $item;
                            $this->_next++;

                            if ($this->_index) {

                                $this->_index->setCursor($this->_pos, $item->getUid(), $extra);
                            }
                            $this->_pos++;


                            if (sizeof($this->_traversable) >= $this->_limit) {
                                break;
                            }
                        }
                    }

                }

                if ($this->_index) {
                    $this->_index->flush();
                }

                if (sizeof($this->_traversable) < $this->_limit) {
                    $this->_next = null;
                }
            }

            return $this->_traversable;
        }

        /**
         * Entity's
         *
         * @return \Traversable
         */
        public function iterate()
        {
            return $this->_sync();
        }

        /**
         * @param callable $filter
         * @return void
         */
        public function setFilter(callable $filter)
        {
            $this->_filters[] = $filter;
        }

        /**
         * @param IField $field
         * @param IComparer $comparer
         * @return ICanBeFiltered
         */
        public function filter(IField $field, IComparer $comparer)
        {
            $this->_comparers[] = array($field, $comparer);

            return $this;
        }

        protected function _filter(IEntity $entity)
        {
            foreach($this->_filters as $callback) {
                if (!call_user_func($callback, $entity)) return false;
            }
            if ($this->_comparers) {
                foreach($this->_comparers as list($field, $comparer))
                {
                    /** @var $field IField */
                    /** @var $comparer IComparer */
                    $field_value = $field->get($entity);

                    if (!$comparer->match($field_value)) return false;
                }
            }
            return true;
        }

        /**
         * @return ICanBeFiltered
         */
        public function resetFilter()
        {
            $this->_filters = [];
            $this->_comparers = [];

            return $this;
        }

        /**
         * @return IField[]
         */
        public function getFilterable()
        {
            /** @var $this IProvider */
            return $this->getFields();
        }

        /**
         * @param Index $index
         * @return ICanBeIndexed
         */
        public function setIndex(Index $index)
        {
            if (!$this->_syncFilterCallback) {
                $this->_index = $index;
            }

            return $this;
        }

        /**
         * @param $uid
         * @return Entity
         */
        public function findByUid($uid)
        {
            $extra = [];
            if ($this->_index) {
                $extra = $this->_index->getCursorExtradata($uid);
            }
            $this->_reset($uid, $extra);



            while($entity = $this->_next($extra)) {
                if ($entity->getUid() == $uid) {
                    return $entity;
                } /*elseif ($entity->getUid() > $uid) { //?
                    break;
                }*/
            }

            return null;
        }


    }
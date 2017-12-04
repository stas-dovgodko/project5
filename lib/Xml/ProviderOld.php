<?php
    namespace project5\Xml;

    use project5\Provider\IEntity;
    use project5\IProvider;

    use project5\Xml\Reader;

    use project5\Provider\Entity;
    use project5\Provider\IField;
    use project5\Provider\Field\String;

    use project5\Provider\ICanBePaged;
    use project5\Provider\ICanSearch;
    use project5\Provider\ICanBeFiltered;


    use project5\Provider\IComparer;

    class ProviderOld implements IProvider, ICanBePaged, ICanBeFiltered, ICanSearch
    {
        private $_offset = 1;
        private $_next;
        private $_limit = 10;
        private $_hasNext;
        private $_xmlFilename;
        private $_page;

        private $_path;
        private $_spec;

        private $_search;
        private $_filter, $_filterCallback;

        private $_fields;

        public function __construct($xmlFilename, $path, callable $spec = null)
        {
            $this->_xmlFilename = $xmlFilename;
            $this->_path = $path;
            if ($spec) {
                $this->_spec = $spec;
            } else {
                $this->_spec = function (\DOMElement $element) {
                    $data = array();
                    foreach($element->childNodes as $child) {
                        if ($child instanceof \DOMElement) $data[$child->nodeName] = $child->nodeValue;
                    }
                    return new Entity($data);
                };
            }
        }

        /**
         * Create empty entity
         *
         * @return IEntity
         */
        public function createEntity()
        {
            return new Entity();
        }

        protected function _prepareXml()
        {
            return Reader::File($this->_xmlFilename);
        }

        protected function _filterEntity(IField $field, $expected, $actual)
        {

            if ($expected && $expected != $actual) {

                return false;
            }

            return true;
        }

        public function iterate()
        {
            $xml = $this->_prepareXml();

            if ($this->_page === null) {
                $list = array(); $pos = 0; $start = $this->_offset;
                $this->_hasNext = false;

                $fields = $this->getFields();


                $expected_filter = array();
                if ($this->_filter) {
                    foreach($fields as $field) { /** @var $field \CRUD\IField */
                        $expected_value = $field->get($this->_filter);
                        $expected_filter[spl_object_hash($field)] = $expected_value;
                    }
                }

                $filter = $this->_filterCallback;
                $xml->findDom($this->_path, function (\DOMElement $element) use(&$list, &$pos, $filter, &$fields, $expected_filter)
                {
                    $dumper = $this->_spec;
                    $entry = $dumper($element);
                    if (!$entry->getUid()) $entry->setUid($pos);

                    $skip = false;
                    if ($expected_filter) {
                        foreach($fields as $field) { /** @var $field \CRUD\IField */

                            $expected_value = isset($expected_filter[$hash = spl_object_hash($field)]) ? $expected_filter[$hash] : null;
                            $actual_value = $field->get($entry);

                            if (!$this->_filterEntity($field, $expected_value, $actual_value)) {
                                $skip = true;
                                break;
                            }
                        }
                    }

                    if (!$skip && (!$filter || $filter($entry))) {
                        if ($search_term = $this->getSearchTerm()) {

                            $str_entry = print_r($entry, true);

                            if (mb_stripos($str_entry, $search_term, null, 'UTF-8') !== false) {
                                $list[] = $entry;
                            }
                        }
                        else {
                            $list[] = $entry;
                        }
                    }

                }, function($atts) use(&$pos, $start)
                {
                    $this->_next = ++$pos;
                    return ($pos >= $start);
                });
                while($xml->progress()) {

                    if (sizeof($list) > $this->_limit) {
                        array_pop($list);
                        $this->_hasNext = true;
                        break;
                    }
                }
                $this->_page = \SplFixedArray::fromArray($list);
            }
            return $this->_page;
        }

        public function setFields($fields) {
            $this->_fields = $fields;
        }

        /**
         * List of IField's
         *
         * @param callable $filter
         * @return \Traversable
         */
        public function getFields(callable $filter = null)
        {
            if (!$this->_fields) {
                $xml = $this->_prepareXml();
                $fields = array();


                $xml->findDom($this->_path, function (\DOMElement $element) use(&$fields) {

                    if (!$fields) {
                        $list = array();
                        foreach($element->childNodes as $child) {
                            if ($child instanceof \DOMElement) {
                                $name = $child->nodeName;
                                $field = new String($name);
                                $field->setTitle($name.'2');
                                $field->setCallback(function(Entity $entity) use($name) {
                                    return $entity->offsetExists($name) ? $entity->offsetGet($name) : null;
                                });

                                $list[$name] = $field;
                            }
                        }
                        $fields = $list;//\SplFixedArray::fromArray($list);
                    }
                }, function($atts) {
                    return true;
                });


                /*$xml->findDom('data/item', function (\DOMElement $element) use(&$entity, &$pos, $uid) {
                    $pos++;
                    if ($pos == $uid) {
                    $data = array();
                    foreach($element->childNodes as $child) {
                        if ($child instanceof \DOMElement) $data[$child->nodeName] = $child->nodeValue;
                    }
                    if (!isset($data['id'])) {
                        $data['id'] = $pos;
                    }
                    $entity = new Entity($data);
                    }

                });*/

                while($xml->progress() && !$fields) {
                    // ok
                }

                $this->_fields = $fields;
            }
            return $this->_fields;
        }

        /**
         * @param $uid
         * @return IEntity
         */
        public function findByUid($uid)
        {
            $xml = $this->_prepareXml();
            $entity = null; $pos = 0;


            $xml->findDom($this->_path, function (\DOMElement $element) use(&$entity, &$pos, $uid) {
                $pos++;
                $dumper = $this->_spec;
                $candidate_entity = $dumper($element);
                if (!$candidate_entity->getUid()) $candidate_entity->setUid($pos);

                if ($candidate_entity->getUid() == $uid) {
                    $entity = $candidate_entity;
                }

            });/*, function($atts) use(&$pos, $uid) {
                $pos++;
                return ($pos == $uid);
            });*/

            /*$xml->findDom('data/item', function (\DOMElement $element) use(&$entity, &$pos, $uid) {
                $pos++;
                if ($pos == $uid) {
                $data = array();
                foreach($element->childNodes as $child) {
                    if ($child instanceof \DOMElement) $data[$child->nodeName] = $child->nodeValue;
                }
                if (!isset($data['id'])) {
                    $data['id'] = $pos;
                }
                $entity = new Entity($data);
                }

            });*/

            while($xml->progress() && !$entity) {
                // ok
            }
            return $entity;
        }

        /**
         * @param string $offser
         * @return void
         */
        public function setPagerOffset($offset)
        {
            $this->_offset = $offset;
            $this->_page = null;
        }

        /**
         * @param int $limit
         * @return void
         */
        public function setPagerLimit($limit)
        {
            $this->_limit = $limit;
            $this->_page = null;
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
            $this->iterate();
            return $this->_hasNext;
        }

        /**
         * @return bool|null
         */
        public function hasPrevPage()
        {
            return ($this->_offset > 1);
        }

        /**
         * @return string|null
         */
        public function getNextPageOffset()
        {
            return $this->_next;//$this->hasNextPage() ? ($this->_offset + $this->_limit) : $this->_offset;
        }

        /**
         * @return string|null
         */
        public function getPrevPageOffset()
        {
            return 1;//min(1, $this->_offset - $this->_limit);
        }

        /**
         * @return string|null
         */
        public function getSearchTerm()
        {
            return $this->_search;
        }

        /**
         * @param string|null $term
         * @return void
         */
        public function setSearchTerm($term)
        {
            $this->_search = $term;
        }

        /**
         * @param callable $filter
         * @return void
         */
        public function setFilter(callable $filter)
        {
            $this->_filterCallback = $filter;
        }

        /**
         * @param $name
         * @param IComparer $comparer
         * @return ICanBeFiltered
         */
        public function filter(IField $field, IComparer $comparer)
        {

        }

        /**
         * @return ICanBeFiltered
         */
        public function resetFilter()
        {
            $this->_filter = null;

            return $this;
        }

        /**
         * @return IField[]
         */
        public function getFilterable()
        {
            return $this->getFields();
        }
    }
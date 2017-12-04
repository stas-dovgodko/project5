<?php

    namespace project5\Csv;

    use project5\IProvider;
    use project5\Provider\Entity;
    use project5\Provider\ICanBeFiltered;
    use project5\Provider\ICanBePaged;
    use project5\Provider\Methods;
    use project5\Provider\SyncIteratorMethods;
    use project5\Provider\Field\String;

    class Provider implements IProvider, ICanBeFiltered, ICanBePaged
    {
        use Methods, SyncIteratorMethods;

        private $_fp;

        private $_fields = null;

        private $_filename;
        private $_firstRowIsHeader;
        //private $_pos;

        private $_delimiter = ',';
        private $_enclosure = '"';
        private $_escape = '\\';

        private $_charset = null;

        public function __construct($filename, $firstRowIsHeader = true)
        {
            $this->_filename = $filename;
            $this->_firstRowIsHeader = $firstRowIsHeader;
        }

        public function setCharset($charset)
        {
            $this->_charset = $charset;
        }

        protected function _decode($value)
        {
            return $this->_charset ? iconv($this->_charset, 'UTF-8', $value) : $value;
        }

        /**
         * @param string $delimiter
         */
        public function setDelimiter($delimiter)
        {
            $this->_delimiter = $delimiter;
        }

        /**
         * @return string
         */
        public function getDelimiter()
        {
            return $this->_delimiter;
        }

        /**
         * @param string $enclosure
         */
        public function setEnclosure($enclosure)
        {
            $this->_enclosure = $enclosure;
        }

        /**
         * @return string
         */
        public function getEnclosure()
        {
            return $this->_enclosure;
        }

        /**
         * @param string $escape
         */
        public function setEscape($escape)
        {
            $this->_escape = $escape;
        }

        /**
         * @return string
         */
        public function getEscape()
        {
            return $this->_escape;
        }



        protected function _reset($cursor, array $extra = [])
        {
            if ($this->_fp = fopen($this->_filename, "r")) {
                if ($this->_firstRowIsHeader && $row = fgetcsv($this->_fp, null, $this->_delimiter, $this->_enclosure, $this->_escape)) {
                    $this->_fields = []; $i = 1;
                    foreach($row as $column) {
                        $field = new String($i++, $this->_decode($column));
                        $this->_fields[] = $field;
                    }
                }
                $this->_pos = ftell($this->_fp);
            }

            return 0;
        }

        protected function _next(array &$extra)
        {
            if ($this->_fp) {

                $pos = ftell($this->_fp)-1;
                if ($pos !== $this->_pos) {
                    fseek($this->_fp, $this->_pos);
                }
                $row = fgetcsv($this->_fp, null, $this->_delimiter, $this->_enclosure, $this->_escape);

                if ($row !== false) {
                    $entity = $this->createEntity();
                    $entity->setUid($pos); $i = 1;
                    foreach($row as $value) {
                        $entity->offsetSet($i++, $this->_decode($value));
                    }
                    $this->_pos = ftell($this->_fp)-1;
                    return $entity;
                }
            }

            return null;
        }

        /**
         * Create empty entity
         *
         * @return Entity
         */
        public function createEntity()
        {
            return new Entity();
        }

        /**
         * @param $uid
         * @return Entity
         */
        public function findByUid($uid)
        {
            if ($this->_fp) {
                fseek($this->_fp, $uid);
                $row = fgetcsv($this->_fp, null, $this->_delimiter, $this->_enclosure, $this->_escape);

                if ($row !== false) {
                    $entity = $this->createEntity();
                    $entity->setUid($uid); $i = 1;
                    foreach($row as $value) {
                        $entity->offsetSet($i++, $this->_decode($value));
                    }
                    return $entity;
                }
            }

            return null;
        }

        public function getFields(callable $filter = null)
        {
            if (!$this->_fp) $this->_reset(0);
            if ($this->_fields === null) {

                if ($this->_fp) {
                    $pos = ftell($this->_fp);
                    if ($pos !== false) {
                        if ($row = fgetcsv($this->_fp, null, $this->_delimiter, $this->_enclosure, $this->_escape)) {
                            $this->_fields = []; $i = 1;
                            foreach($row as $column) {
                                $field = new String($i++);
                                $this->_fields[] = $field;
                            }
                        }
                        fseek($this->_fp, $pos);
                    }
                }
            }

            return ($filter === null) ? $this->_fields :array_filter($this->_fields, $filter);
        }

    }
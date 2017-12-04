<?php
    namespace project5\Xml;

    use project5\IProvider;
    use project5\Provider\Entity;
    use project5\Provider\ICanBeFiltered;
    use project5\Provider\ICanBeIndexed;
    use project5\Provider\ICanBePaged;

    use project5\Provider\IEntity;
    use project5\Provider\Index;
    use project5\Provider\Field\String as XmlField;
    use project5\Provider\Methods;
    use project5\Provider\SyncIteratorMethods;

    use Doctrine\Common\Cache\ApcCache;


    class Provider implements IProvider, ICanBeFiltered, ICanBePaged, ICanBeIndexed
    {
        use Methods, SyncIteratorMethods;

        /**
         * @var Reader
         */
        protected $_xml;

        /**
         * @var Entity
         */
        private $_nextEntity;



        protected $_uid = 0;

        private $_fields = null;


        public function __construct($xmlFilename, $path, callable $spec = null)
        {
            $this->_xmlFilename = $xmlFilename;
            $this->_path = $path;
            if ($spec) {
                $this->_spec = $spec;
            } else {
                $this->_spec = function (Entity $entity, \DOMElement $element) {

                    $data = [];
                    foreach($element->childNodes as $child) {
                        if ($child instanceof \DOMElement) $data[$child->nodeName] = $child->nodeValue;
                    }
                    $entity->exchangeArray($data);
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




        protected function _reset($cursor, array $extra = [])
        {
            $this->_uid = 0;
            $this->_xml = Reader::File($this->_xmlFilename);
            $this->_xml->findDom($this->_path, function (\DOMElement $element)
            {
                $uid = $this->_xml->getPosIdx();

                $this->_nextEntity = $this->createEntity();
                $this->_nextEntity->setUid($uid);

                call_user_func($this->_spec, $this->_nextEntity, $element);
            });

            $this->_xml->setCursor($cursor);

            return 0;
        }

        protected function _next(array &$extra)
        {
            while(!$this->_nextEntity) {
                if (!$this->_xml->progress()) {
                    return null;
                }
            }

            $next = $this->_nextEntity;
            $this->_nextEntity = null;
            return $next;
        }



        public function getFields(callable $filter = null)
        {
            if ($this->_fields === null) {
                $current_offset = $this->_xml ? $this->_xml->getCursor() : 0;
                $this->_reset(0);
                $extra = [];
                $first = $this->_next($extra);

                if ($first instanceof \Traversable) {
                    $this->_fields = [];
                    foreach($first as $k=>$v) {
                        $this->_fields[] = new XmlField($k);
                    }
                    $this->_reset($current_offset);


                }
            }

            return $this->_fields;
        }

        /**
         * @return string
         */
        public function getIndexKey()
        {
            $_filter_key = var_export($this->_filters, true) . var_export($this->_comparers, true);
            return sprintf('%s_%s_%s_%s_%s_v4', __CLASS__, $this->_xmlFilename, md5($_filter_key), filemtime($this->_xmlFilename), filemtime(__FILE__));
        }
    }
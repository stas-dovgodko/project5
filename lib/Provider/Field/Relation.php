<?php
    namespace project5\Provider\Field;

    use project5\Provider\IField;
    use project5\IProvider;
    use project5\Provider\IEntity;

    class Relation implements IField
    {
        use Methods;

        protected $title_names = [
            'title', 'name', 'username', 'email'
        ];

        /**
         * @var IProvider
         */
        private $_foreignProvider;

        /**
         * @var IField|null
         */
        private $_titleField;

        public function __construct($name, array $names, IProvider $foreignProvider, IField $titleField = null, $title = null)
        {
            $this->_name = $name;
            $this->_title = $title;
            $this->_names = $names;

            $this->_foreignProvider = $foreignProvider;
            $this->_titleField = $titleField;
        }

        public function getForeignEntityString(IEntity $entity)
        {
            $rel_pk = $this->get($entity);
            $entity = $this->getForeignProvider()->findByUid($rel_pk);

            if ($entity) return (string)$entity;
            else return (string)$rel_pk;
        }

        public function getLocals()
        {
            return $this->_names;
        }

        /**
         * @deprecated
         * @return IField
         */
        public function getForeignField()
        {
            if ($this->_titleField) {
                return $this->_titleField;
            } else {
                $fields = $this->_foreignProvider->getFields();

                foreach($fields as $field) {
                    if (in_array(strtolower($field->getName()), $this->title_names)) {
                        return $field;
                    }
                }

                return reset($fields);
            }
        }

        /**
         * @return IProvider
         */
        public function getForeignProvider()
        {
            return $this->_foreignProvider;
        }

        /**
         * @param IEntity $object
         * @return mixed|null
         */
        public function get(IEntity $object)
        {
            list($get, ) = $this->_callbacks;

            if ($get) {
                $value = call_user_func_array($get, array($object));
            } else {

                $value = [];
                foreach($this->_names as $name) {
                    if (method_exists($object, $method_name = 'get'.ucfirst($name))) {
                        $value[] = call_user_func_array(array($object, $method_name), []);
                    } else {
                        $value[] = isset($object[$name]) ? $object[$name] : null;
                    }
                }
                if (sizeof($value) === 1) return $value[0];
            }

            return $value;
        }

        public function set(IEntity $object, $value)
        {
            if (!$value && $this->canBeEmpty()) {
                // check for null value
                if (!$this->_foreignProvider->findByUid($value)) {
                    $value = null;
                }
            }

            list(, $set) = $this->_callbacks;
            if ($set) {
                return call_user_func_array($set, array($object, $value));
            } else {
                self::_SetEntityFieldValue($object, $this->getName(), $value);
            }
        }
    }
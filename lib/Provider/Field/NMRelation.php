<?php
    namespace project5\Provider\Field;

    use project5\Provider\IEntity;
    use project5\Provider\IField;
    use project5\IProvider;

    class NMRelation implements IField
    {
        use Methods;

        /**
         * @var IProvider
         */
        private $_foreignProvider;

        /**
         * @var IField
         */
        private $_foreignField;

        public function __construct($name, IProvider $foreignProvider, IField $foreignField, $title = null)
        {
            $this->_name = $name;
            $this->_title = $title;

            $this->_foreignProvider = $foreignProvider;
            $this->_foreignField = $foreignField;
        }

        /**
         * @return IField
         */
        public function getForeignField()
        {
            return $this->_foreignField;
        }

        /**
         * @return IProvider
         */
        public function getForeignProvider()
        {
            return $this->_foreignProvider;
        }

        /**
         * Get foreign ids
         *
         * @param IEntity $object
         * @return string[]
         */
        public function getForeignUids(IEntity $object)
        {
            return (array)$this->get($object);
        }


    }
<?php
    namespace project5\Form;

    use project5\Form;

    class Field
    {
        const TYPE_TEXT = 'text';
        const TYPE_NUMBER = 'number';
        const TYPE_EMAIL = 'email';

        private $_name;
        private $_title;
        private $_description;

        /**
         * @var bool
         */
        private $_canBeEmpty = true;
        private $emptyErrorMessage = null;

        protected $readonly = false;

        private $_validators;

        /**
         * @var string
         */
        protected $type = 'text';

        public function __construct($name, $title = null)
        {
            $this->_name = $name;
            $this->_title = $title ? $title : $name;
        }

        /**
         * @return string
         */
        public function getType()
        {
            return $this->type;
        }

        /**
         * @param string $type
         */
        public function setType($type)
        {
            $this->type = $type;
        }



        public function setReadonly($readonly = true)
        {
            $this->readonly = $readonly;

            return $this;
        }

        public function isReadonly()
        {
            return (bool)$this->readonly;
        }

        public function getDataValue($value)
        {
            if ($this->type == self::TYPE_NUMBER) {

                if ($value === '') return null;
                else (float)($value);
            }
            return $value;
        }

        public function formatDataValue($value)
        {
            if ($this->type == self::TYPE_NUMBER) {
                if ($value === '') return null;
                else return $value;//number_format($value);
            }
            return $value;
        }

        /**
         * @throws \Form\Validate\Exception
         * @param $value
         */
        final public function validate($value) {
            if ($this->_validators) foreach($this->_validators as $validator) {
                $validator($value);
            }

            $this->_doValidate($value);
        }

        protected function _doValidate($value)
        {

        }




        /**
         * @param callback $callback
         */
        public function addValidator(callback $callback)
        {
            $this->_validators[] = $callback;
        }

        /**
         * @return mixed
         */
        public function getName()
        {
            return $this->_name;
        }

        /**
         * @param mixed $name
         */
        public function setName($name)
        {
            $this->_name = $name;
        }

        /**
         * @return mixed
         */
        public function getTitle()
        {
            return $this->_title;
        }

        /**
         * @param mixed $title
         */
        public function setTitle($title)
        {
            $this->_title = $title;
        }

        /**
         * @return mixed
         */
        public function getDescription()
        {
            return $this->_description;
        }

        /**
         * @param mixed $description
         * @return Field
         */
        public function setDescription($description)
        {
            $this->_description = $description;

            return $this;
        }



        /**
         * @param boolean|null $canBeEmpty
         * @return bool
         */
        public function canBeEmpty($canBeEmpty = null, $message = null)
        {
            if ($canBeEmpty !== null) {
                $this->_canBeEmpty = $canBeEmpty;

                if ($message !== null) {
                    $this->emptyErrorMessage = $message;
                }
            }

            return $this->_canBeEmpty;
        }

        /**
         * @return null
         */
        public function getEmptyErrorMessage()
        {
            return $this->emptyErrorMessage;
        }

        /**
         * @param null $emptyErrorMessage
         */
        public function setEmptyErrorMessage($emptyErrorMessage)
        {
            $this->emptyErrorMessage = $emptyErrorMessage;
        }


        /**
         * @return IValidator[]
         */
        public function getValidators(Form $form)
        {
            return [];
        }
    }
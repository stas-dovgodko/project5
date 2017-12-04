<?php
    namespace project5\Form;

    class Select extends Field {
        protected $matrix;

        public function __construct($name, $matrix, $title = null)
        {
            parent::__construct($name, $title);

            $this->matrix = $matrix;
        }


        public function getOptions()
        {
            return $this->matrix;
        }
    }
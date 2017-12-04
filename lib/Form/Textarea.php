<?php
    namespace project5\Form;

    use project5\Form;

    class Textarea extends Field {

        protected $rows;

        /**
         * @return mixed
         */
        public function getRows()
        {
            return $this->rows;
        }

        /**
         * @param mixed $rows
         */
        public function setRows($rows)
        {
            $this->rows = $rows;
        }


    }
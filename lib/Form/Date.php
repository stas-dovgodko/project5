<?php
    namespace project5\Form;

    class Date extends Field
    {
        protected $format = 'MM/DD/YYYY HH:mm';

        public function getFormat()
        {
            return $this->format;
        }

        public function getDataValue($value)
        {
            return $value ? new \DateTime($value) : null;
        }

        public function formatDataValue($value)
        {
            if (!$value) return null;
            if (!($value instanceof \DateTime)) {

                $value = new \DateTime(date('r', $value));
            }

            return $value->getTimestamp();
        }

    }
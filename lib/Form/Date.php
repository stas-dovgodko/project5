<?php
    namespace project5\Form;

    class Date extends Field
    {
        protected $timeZone = nul;
        protected $format = 'MM/DD/YYYY HH:mm';

        /**
         * @param \DateTimeZone|null $tz
         * @return $this
         */
        public function setTimezone(\DateTimeZone $tz = null)
        {
            $this->timeZone = $tz;

            return $this;
        }

        public function getFormat()
        {
            return $this->format;
        }

        public function getDataValue($value)
        {
            return $value ? new \DateTime($value, $this->timeZone) : null;
        }

        public function formatDataValue($value)
        {
            if (!$value) return null;
            if (!($value instanceof \DateTime)) {

                $value = new \DateTime(date('r', $value), $this->timeZone);
            }

            return $value->getTimestamp();
        }

    }
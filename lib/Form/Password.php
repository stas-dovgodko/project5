<?php
    namespace project5\Form;

    use project5\Form;

    class Password extends Field
    {

        public function hashValue($value)
        {
            return md5($value);
        }


    }
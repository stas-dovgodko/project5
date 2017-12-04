<?php
namespace project5\Form;

use project5\Form;

    class Checkbox extends Field {

        public function getDataValue($value)
        {
            return (bool)$value;
        }

    }
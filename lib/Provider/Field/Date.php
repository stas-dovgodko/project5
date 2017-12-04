<?php
namespace project5\Provider\Field;

use project5\Provider\IField;

class Date implements IField
{
    use Methods;


    public function __construct($name, $title = null)
    {
        $this->_name = $name;
        $this->_title = $title;
    }



}
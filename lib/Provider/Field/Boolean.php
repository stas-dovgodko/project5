<?php
namespace project5\Provider\Field;


use project5\Provider\IEntity;
use project5\Provider\IField;

class Boolean implements IField
{
    use Methods;


    public function __construct($name, $title = null)
    {
        $this->_name = $name;
        $this->_title = $title;
    }

    /**
     * @param mixed $value
     * @param string $error Error message if value does not OK
     * @return bool
     */
    public function validate($value, &$error = null)
    {
        return (bool)$value;
    }
}
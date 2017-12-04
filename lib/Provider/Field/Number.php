<?php
namespace project5\Provider\Field;


use project5\Provider\IField;
use project5\Provider\Field\Methods;

class Number implements IField
{
    use Methods;

    /**
     * @var int|null
     */
    protected $min;

    /**
     * @var int|null
     */
    protected $max;



    public function __construct($name, $title = null)
    {
        $this->_name = $name;
        $this->_title = $title;
    }

}
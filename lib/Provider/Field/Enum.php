<?php
namespace project5\Provider\Field;

use project5\Provider\IField;

class Enum implements IField
{
    use Methods;

    /**
     * @var IField
     */
    private $valueField;


    private $enums = [];

    public function __construct(IField $valueField, array $enums = [], $title = null)
    {
        $this->valueField = $valueField;

        $this->_name = $valueField->getName();
        $this->_title = $title ?: $valueField->getTitle();

        $this->enums = $enums;
    }

    /**
     * @param mixed $value
     * @param string $error Error message if value does not OK
     * @return bool
     */
    public function validate($value, &$error = null)
    {
        return $this->valueField->validate($value, $error);
    }
}
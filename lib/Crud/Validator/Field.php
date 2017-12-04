<?php
namespace project5\Crud\Validator;

use project5\Form\IValidator;
use project5\Provider\IField;

class Field implements IValidator
{
    /**
     * @var IField
     */
    protected $field;

    public function __construct(IField $field)
    {
        $this->field = $field;
    }

    /**
     * @param array $params
     * @return string
     */
    public function getErrorDefaultMessage(array &$params = [])
    {
        return 'Wrong value';
    }

    /**
     * @param $value
     * @param string $message
     * @return boolean True if valid
     */
    public function validate($value, &$message = null)
    {
        $error = null;
        $this->field->validate($value, $error);

        if ($error !== null) {
            $message = $error;

            return false;
        } else {
            return true;
        }
    }
}
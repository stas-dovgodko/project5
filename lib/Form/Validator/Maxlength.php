<?php

namespace project5\Form\Validator;

use project5\Form\IValidator;

class Maxlength implements IValidator
{
    protected $length;

    public function __construct($length)
    {
        $this->length = $length;
    }
    /**
     * @param array $params
     * @return string
     */
    public function getErrorDefaultMessage(array &$params = [])
    {
        $params[] = $this->length;

        return 'Length should be equal or less %d chars';
    }

    /**
     * @param $value
     * @param string $message
     * @return boolean True if valid
     */
    public function validate($value, &$message = null)
    {
        if (mb_strlen($value, 'utf-8') <= $this->length) {
            return true;
        } else {
            return false;
        }
    }
}
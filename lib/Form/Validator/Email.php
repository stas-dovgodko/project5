<?php

namespace project5\Form\Validator;

use project5\Form\IValidator;

class Email implements IValidator
{
    /**
     * @param array $params
     * @return string
     */
    public function getErrorDefaultMessage(array &$params = [])
    {
        return 'Wrong email';
    }

    /**
     * @param $value
     * @param string $message
     * @return boolean True if valid
     */
    public function validate($value, &$message = null)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}
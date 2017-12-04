<?php

namespace project5\Form\Validator;

use project5\Form\IValidator;

class OrFrom implements IValidator
{
    private $validators = [];

    public function __construct()
    {
        $args = func_get_args();

        foreach($args as $arg) {
            if (is_array($arg)) {
                foreach($arg as $a) {
                    if ($arg instanceof IValidator) {
                        $this->validators[] = $arg;
                    }
                }
            } elseif ($arg instanceof IValidator) {
                $this->validators[] = $arg;
            }
        }
    }

    /**
     * @param array $params
     * @return string
     */
    public function getErrorDefaultMessage(array &$params = [])
    {
        return '';
    }

    /**
     * @param $value
     * @param string $message
     * @return boolean True if valid
     */
    public function validate($value, &$message = null)
    {
        foreach($this->validators as $validator) {
            if (!$validator->validate($value)) return false;
        }

        return true;
    }
}
<?php

namespace project5\Form\Validator;

use project5\Form\IValidator;

class Custom implements IValidator
{
    protected $callback;
    protected $error;
    public function __construct(callable $callback, $error = 'wrong value')
    {
        $this->callback = $callback;
        $this->error = $error;
    }
    /**
     * @param array $params
     * @return string
     */
    public function getErrorDefaultMessage(array &$params = [])
    {
        return $this->error;
    }

    /**
     * @param $value
     * @param string $message
     * @return boolean True if valid
     */
    public function validate($value, &$message = null)
    {
        return (bool)call_user_func($this->callback, $value);
    }
}
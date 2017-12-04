<?php

namespace project5\Form\Validator;

use project5\Form\IValidator;

class Regexp implements IValidator
{
    /**
     * @var string
     */
    private $expected;

    /**
     * @var callable|null
     */
    private $filter;

    public function __construct($pattern, callable $filter = null)
    {
        $this->expected = $pattern;
        $this->filter = $filter;
    }

    /**
     * @param array $params
     * @return string
     */
    public function getErrorDefaultMessage(array &$params = [])
    {
        return 'Does not match';
    }

    /**
     * @param $value
     * @param string $message
     * @return boolean True if valid
     */
    public function validate($value, &$message = null)
    {
        if ($this->filter) {
            $value = call_user_func($this->filter, $value);
        }

        if (is_callable($this->expected)) {
            $expected = call_user_func($this->expected);
        } else {
            $expected = $this->expected;
        }

        return @preg_match($expected, $value);
    }
}
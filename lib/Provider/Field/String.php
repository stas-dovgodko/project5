<?php
namespace project5\Provider\Field;


use project5\Provider\IField;
use project5\Provider\Field\Methods;

class String implements IField
{
    use Methods;

    /**
     * @var int|null
     */
    protected $maxLength;


    public function __construct($name, $title = null)
    {
        $this->_name = $name;
        $this->_title = $title;
    }

    /**
     * @return int|null
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * @param int|null $maxLength
     */
    public function setMaxLength($maxLength)
    {
        $this->maxLength = $maxLength;
    }

    /**
     * @return int|null
     */
    public function hasMaxLength()
    {
        return $this->maxLength !== null;
    }

    /**
     * @param mixed $value
     * @param string $error Error message if value does not OK
     * @return bool
     */
    public function validate($value, &$error = null)
    {
        if (is_string($value)) {
            if ($this->hasMaxLength()) {
                if (mb_strlen($value, 'UTF-8') > $this->getMaxLength()) {

                    $error = sprintf('Max length %d', $this->getMaxLength());

                    return null;
                }
            }

            return $value;
        } else {
            $error = sprintf('Value should be string');
            return null;
        }

    }
}
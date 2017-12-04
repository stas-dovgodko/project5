<?php
namespace project5\Provider\Field;

use project5\Provider\IEntity;

trait Methods
{
    protected $_name;
    protected $_title;
    protected $_description;
    protected $_allowEmpty;
    protected $_surrogate = false;
    protected $_callbacks = [null,null];

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Field can be empty
     *
     * @return bool
     */
    public function canBeEmpty()
    {
        return ($this->_allowEmpty === true);
    }

    public function setCanBeEmpty($canBeEmpty = true)
    {
        $this->_allowEmpty = $canBeEmpty;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->_description;
    }

    public function setCallback(callable $get = null, callable $set = null)
    {
        if ($get !== null) {
            $this->_callbacks[0] = $get;
        }
        if ($set !== null) {
            $this->_callbacks[1] = $set;
        }
    }

    /**
     * @param IEntity $object
     * @param $value
     * @return mixed
     */
    public function set(IEntity $object, $value)
    {
        assert('$this instanceof \project5\Provider\IField');
        list(, $set) = $this->_callbacks;
        if ($set) {
            return call_user_func_array($set, array($object, $value));
        } else {
            self::_SetEntityFieldValue($object, $this->getName(), $value);
        }
    }

    /**
     * @param IEntity $object
     * @return mixed|null
     */
    public function get(IEntity $object)
    {
        list($get, ) = $this->_callbacks;
        if ($get) {
            return call_user_func_array($get, array($object));
        } else {
            return self::_GetEntityFieldValue($object, $this->getName());
        }
    }

    protected static function _SetEntityFieldValue(IEntity $object, $name, $value)
    {
        if (method_exists($object, $method_name = 'set'.ucfirst($name))) {
            return call_user_func_array(array($object, $method_name), [$value]);
        } else {
            $object[$name] = $value;
        }
    }

    protected static function _GetEntityFieldValue(IEntity $object, $name, &$found = false)
    {
        if (method_exists($object, $method_name = 'get'.ucfirst($name))) {
            $found = true;
            return call_user_func_array(array($object, $method_name), []);
        } elseif (isset($object[$name])) {
            $found = true;
            return $object[$name];
        }
    }


    public function setSurrorate($isSurrorate = true)
    {
        $this->_surrogate = (bool)$isSurrorate;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSurrogate()
    {
        return (bool)$this->_surrogate;
    }

    /**
     * @param mixed $value
     * @param string $error Error message if value does not OK
     * @return mixed
     */
    public function validate($value, &$error = null)
    {
        return $value;
    }
}
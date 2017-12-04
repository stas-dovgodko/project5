<?php
namespace project5\Provider\Cache\Tag;

use project5\Provider\Cache\ITag;



class CallableType implements ITag
{
    /**
     * @var callable
     */
    private $_callable;

    public function __construct(callable $callable)
    {
        $this->_callable = $callable;
    }


    /**
     * @return string|null
     */
    public function getStateHash()
    {
        try {
            if(is_string($this->_callable)) {
                return md5($this->_callable);
            } elseif(is_array($this->_callable)) {
                if(count($this->_callable) !== 2) throw new \Exception("Array-callables must have exactly 2 elements");
                if(!is_string($this->_callable[1])) throw new \Exception("Second element of array-callable must be a string function name");
                if(is_object($this->_callable[0])) return spl_object_hash($this->_callable[0]).'::'.$this->_callable[1];
                elseif(is_string($this->_callable[0])) return implode('::',$this->_callable);

                throw new \Exception("First element of array-callable must be a class name or object instance");
            } elseif($this->_callable instanceof \Closure) {
                $reflection = new \ReflectionFunction($this->_callable);
                $file_tag = new File($reflection->getFileName());

                if ($hash = $file_tag->getStateHash()) {
                    return md5($hash . $reflection->getStartLine().$reflection->getEndLine());
                }

                return null;
            } else return null;
        } catch (\Exception $e) {
            return null; // not serializable
        }

    }
}
<?php
namespace project5;

/**
 * Class Config
 *
 * @property bool DEBUG_MODE
 * @property string secure_salt
 */
class Config implements ISingleton, \ArrayAccess
    {
        private $_env;

        /**
         * @var Config
         */
        private static $_Instance = null;
        private static $_Instances = array();

        private $_templatePath = array();


        private function __construct($env)
        {
            $this->_env = $env;
        }

        public function getEnv()
        {
            return $this->_env;
        }

        public function addTemplatePath($path)
        {
            if ($realpath = realpath($path)) {
                $this->_templatePath[] = $realpath;
            }

            return $this;
        }

        public function getTemplatePath()
        {
            return $this->_templatePath;
        }

        /**
         * @return Config
         */
        public static function GetInstance($env = null)
        {
            if ($env) {
                if (!isset(self::$_Instances[$env])) {
                    self::$_Instances[$env] = new Config($env);

                    if (!self::$_Instance) self::$_Instance = self::$_Instances[$env];
                }
                return self::$_Instances[$env];
            } else if (self::$_Instance) {
                return self::$_Instance;
            } else {
                throw new \Exception('Config env not initiated');
            }
        }

        public function readConfig($fileName)
        {
            $cl = function($fileName) {
                require $fileName;
                assert('!isset($Config)');
            };
            $cl->bindTo($this);

            if (is_file($fileName)) {
                $cl($fileName);
            }
            if (is_file($env_fileName = dirname($fileName) . DIRECTORY_SEPARATOR . $this->_env. '_' . basename($fileName))) {
                $cl($env_fileName);
            }

            return true;
        }

        /**
         * (PHP 5 &gt;= 5.0.0)<br/>
         * Whether a offset exists
         * @link http://php.net/manual/en/arrayaccess.offsetexists.php
         * @param mixed $offset <p>
         * An offset to check for.
         * </p>
         * @return boolean true on success or false on failure.
         * </p>
         * <p>
         * The return value will be casted to boolean if non-boolean was returned.
         */
        public function offsetExists($offset)
        {
            return property_exists($this, $offset);
        }

        /**
         * (PHP 5 &gt;= 5.0.0)<br/>
         * Offset to retrieve
         * @link http://php.net/manual/en/arrayaccess.offsetget.php
         * @param mixed $offset <p>
         * The offset to retrieve.
         * </p>
         * @return mixed Can return all value types.
         */
        public function offsetGet($offset)
        {
            return $this->$offset;
        }

        /**
         * (PHP 5 &gt;= 5.0.0)<br/>
         * Offset to set
         * @link http://php.net/manual/en/arrayaccess.offsetset.php
         * @param mixed $offset <p>
         * The offset to assign the value to.
         * </p>
         * @param mixed $value <p>
         * The value to set.
         * </p>
         * @return void
         */
        public function offsetSet($offset, $value)
        {
            return $this->$offset = $value;
        }

        /**
         * (PHP 5 &gt;= 5.0.0)<br/>
         * Offset to unset
         * @link http://php.net/manual/en/arrayaccess.offsetunset.php
         * @param mixed $offset <p>
         * The offset to unset.
         * </p>
         * @return void
         */
        public function offsetUnset($offset)
        {
            unset($this->$offset);
        }
    }
<?php
namespace project5;
/**
 * Autoloader
 *
 * @author Stas Dovgodko
 */
require_once(__DIR__ . '/ISingleton.php');

interface IAutoloader {
    public function __invoke($className);
}

class AutoloaderPRS0 implements IAutoloader
{
    private $_includePath = array();
    public function __construct($path = null)
    {
        if ($path === null) $path = get_include_path();
        if (is_string($path)) $path = explode(PATH_SEPARATOR, $path);

        $this->_includePath = array_unique($path);
    }
    public function __invoke($className)
    {
        $className = ltrim($className, '\\');
        $fileName  = '';
        $namespace = '';
        if ($lastNsPos = strrpos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        foreach($this->_includePath as $path) {
            $abs_fileName = $path . $fileName;

            if (is_file($abs_fileName)) return $abs_fileName;
        }

        return false;
    }

    /**
     * @param array $includePaths
     * @return $this
     */
    public function pushIncludePaths($path)
    {
        $this->_includePath = array_unique(array_merge($this->_includePath, is_string($path)? explode(PATH_SEPARATOR, $path):$path));

        return $this;
    }

    /**
     * @param array $includePaths
     * @return $this
     */
    public function addIncludePaths($path)
    {
        $this->_includePath = array_unique(array_merge(is_string($path)? explode(PATH_SEPARATOR, $path):$path, $this->_includePath));

        return $this;
    }

    public function getIncludePaths()
    {
        return $this->_includePath;
    }
}

class Autoload implements ISingleton
{
    static private $_Instance;

    private $_minorPrefix;

    private $_preload = array();

    private $_autoloads = array();

    /**
     * @var AutoloaderPRS0
     */
    private $_autoloader;

    private function __construct()
    {
        $this->registerAutoloader($this->_autoloader = new AutoloaderPRS0);
    }

    /**
     * @return AutoloaderPRS0
     */
    public function getPRS0Autoloader()
    {
        return $this->_autoloader;
    }

    public function init($prefix)
    {
        $this->setPrefix($prefix);

        return $this;
    }

    public function setPrefix($prefix)
    {
        $this->_minorPrefix = $prefix;

        return $this;
    }

    public function getPrefix($full = false)
    {
        return ($full ? md5(implode('_', $this->_autoloader->getIncludePaths())) : '').$this->_minorPrefix;
    }

    public function load($className)
    {
        if (isset($this->_preload[$className])) {
            // next will pop used keys to top
            $absolutePath = $this->_preload[$className];
            unset($this->_preload[$className]);
        } else {

            foreach($this->_autoloads as $info) {
                list($callback, $prefix) = $info;
                if (is_string($prefix) && strpos($className, $prefix) !== 0) {
                    continue;
                } elseif(is_array($prefix)) {
                    $found = false;
                    foreach($prefix as $p) {
                        if (strpos($className, $p) === 0) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) continue;
                }

                $result = call_user_func_array($callback, array($className));

                if (is_string($result)) {
                    $absolutePath = $result;
                    break;
                } elseif($result === true) {
                    return true;
                }
            }
        }

        if (isset($absolutePath) && $absolutePath) {
            //$this->_preload = array($className => $absolutePath) + $this->_preload;

            /** @noinspection PhpIncludeInspection */
            require_once $absolutePath;

            return true;
        }

        return false;
    }

    /**
     * Registration of function/method as autoloader
     *
     * @param callable|Closure|IAutoloader $autoloader
     * @param string|null $prefix
     * @return Autoload
     */
    public function registerAutoloader($autoloader, $prefix = null)
    {
        assert('$prefix === null || is_string($prefix)');
        assert('is_callable($autoloader)');
        $this->_autoloads[] = array($autoloader, $prefix);

        return $this;
    }

    /**
     * Push registration of function/method as autoloader to top
     *
     * @param $autoloader
     * @param null $prefix
     * @return $this
     */
    public function pushAutoloader($autoloader, $prefix = null)
    {
        assert('$prefix === null || is_string($prefix) || is_array($prefix)');
        assert('is_callable($autoloader)');
        array_unshift($this->_autoloads, array($autoloader, $prefix));

        return $this;
    }

    /**
     * Generic Singleton's function
     */
    public static function getInstance()
    {
        if (self::$_Instance === null) {
            self::$_Instance = new Autoload();

            spl_autoload_register(array(self::$_Instance, 'load'));
        }

        return self::$_Instance;
    }
}


<?php
namespace project5\Template\Templater\Twig\Extension;

use project5\Template\Templater\Twig\IExtension;
use Twig_Environment;
use project5\Web\Router;

class Tools implements IExtension
{
    /**
     * @param Router $router
     */
    public function __construct()
    {

    }

    public function extendTwig(Twig_Environment $twig, array $options = [])
    {
        $twig->getExtension('core')->setEscaper('jsinject', function($twig, $raw, $charset) {
            var_dump($raw);
            if ($raw === null) return 'null';
            elseif ($raw === 0) return '0';
            elseif ($raw === false) return 'false';
            elseif (is_numeric($raw)) return number_format($raw,null, '.');
            else return json_encode($raw);
        });
        $twig->addFilter('id', new \Twig_SimpleFilter('id', function ($v) {

            $r = unpack('v*', md5(json_encode($v), true));
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                $r[1],
                $r[2],
                $r[3],
                $r[4] & 0x0fff | 0x4000,
                $r[5] & 0x3fff | 0x8000,
                $r[6],
                $r[7],
                $r[8]
            );

            return $uuid;
        }));
        $twig->addFilter('uid', new \Twig_SimpleFilter('uid', function ($v) {

            static $conter = 0;

            $r = unpack('v*', md5(($conter++).'_'.json_encode($v), true));
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                $r[1],
                $r[2],
                $r[3],
                $r[4] & 0x0fff | 0x4000,
                $r[5] & 0x3fff | 0x8000,
                $r[6],
                $r[7],
                $r[8]
            );

            return $uuid;
        }));
        $twig->addFilter('string', new \Twig_SimpleFilter('string', function ($v) {

            return (string)$v;
        }));
        $twig->addFilter('dump', new \Twig_SimpleFilter('dump', function ($v) {

            $return = var_export($v, true);
            return $return;
        }));
        $twig->addFilter('md5', new \Twig_SimpleFilter('md5', function ($v) {

            return md5((string)$v);
        }));
        $twig->addFilter('size', new \Twig_SimpleFilter('size', function () {

            $args = func_get_args();
            $bytes = (int)array_shift($args);
            if (sizeof($args) > 0) $precision = array_shift($args);
            else $precision = 2;

            $units = array('B', 'KB', 'MB', 'GB', 'TB');

            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);

            $bytes /= pow(1024, $pow);

            return round($bytes, $precision) . ' ' . $units[$pow];
        }));
        $twig->addFilter('call', new \Twig_SimpleFilter('call', function () {
            $args = func_get_args();
            $callback = array_shift($args);

            return is_callable($callback) ? call_user_func_array($callback, $args) : $callback;
        }));
        $twig->addTest(new \Twig_SimpleTest('a', function ($object, $class) {

            if (!is_object($object)) return false;

            if (get_class($object) === $class) {
                return true;
            } elseif (is_subclass_of($object, $class)) {
                return true;
            } elseif (substr($class,0,1) !== '\\') {

                $reflection = new \ReflectionObject($object);
                do {
                    $object_class = substr($reflection->getName(), -1*strlen($class));
                    if (strtolower($object_class) === strtolower($class)) {
                        return true;
                    }
                    foreach($reflection->getInterfaceNames() as $interface) {
                        $interface_class = substr($interface, -1*strlen($class));
                        if (strtolower($interface_class) === strtolower($class)) {
                            return true;
                        }
                    }
                } while($reflection = $reflection->getParentClass());


                return false;
            } else {
                return false;
            }
        }));

    }
}

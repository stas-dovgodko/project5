<?php
namespace project5\Provider\Cache;

class Autoloader implements \project5\IAutoloader
{
    private static $_Traits = [

    ];

    private static $_Iterfaces = [

    ];

    public function __invoke($class)
    {
        if (substr($class, -8) === 'Provider' && substr($class, 0, strlen(__NAMESPACE__)+1) === (__NAMESPACE__.'\\')) {
            $basename = substr($class, strlen(__NAMESPACE__)+1, -8);

            if ($basename) {
                $interfaces = [];
                $traits = [];
                $parts = preg_split("/(?<=[a-z])(?![a-z])/", $basename, -1, PREG_SPLIT_NO_EMPTY);

                foreach($parts as $part) {
                    if (isset(self::$_Iterfaces[$part])) $interfaces[] = self::$_Iterfaces[$part];
                    if (isset(self::$_Traits[$part])) $traits[] = self::$_Traits[$part];
                }

                $code = '
                namespace project5\Provider\Cache;

                class '.$basename.'Provider extends Provider
                '.($interfaces ? sprintf('implements %s', implode(', ', $interfaces)):'').'
                {
                    '.($traits ? sprintf('use %s;', implode(', ', $traits)):'').'
                }
                ';

                eval($code);

                return class_exists($class, false);
            }
        }
        return false;
    }
}
<?php
namespace project5\Template\Templater\Twig\Extension\Cache;

use Asm89\Twig\CacheExtension\CacheStrategy\KeyGeneratorInterface;

class KeyGenerator implements KeyGeneratorInterface {

    /**
     * Generate a cache key for a given value.
     *
     * @param mixed $value
     *
     * @return string
     */
    public function generateKey($value)
    {
        if (is_object($value) || is_array($value)) {
            return serialize($value);
        }
        return (string)$value;
    }
}
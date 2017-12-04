<?php
namespace project5\Template\Templater\Twig;


class ArrayLoader extends \Twig_Loader_Array
{
    public function getCacheKey($name)
    {
        return $name . parent::getCacheKey($name);
    }
}
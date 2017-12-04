<?php
namespace project5;
/**
 * Interface for implementation of {@link http://c2.com/cgi/wiki?SingletonPattern Singleton pattern} 
 *
 */
interface ISingleton
{
    /**
    * Generic Singleton's function
    */
    public static function GetInstance();
}

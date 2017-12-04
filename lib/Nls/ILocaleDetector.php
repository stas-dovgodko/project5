<?php
namespace project5\Nls;

interface ILocaleDetector
{
    /**
     * @return string[]|null
     */
    function determine();


}
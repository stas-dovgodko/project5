<?php
namespace project5\Nls\LocaleDetector;

use project5\Nls\ILocaleDetector;

class Config implements ILocaleDetector
{
    /**
     * @var string
     */
    private $locale;

    public function __construct($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return string[]|null
     */
    function determine()
    {
        if ($this->locale) {
            return [$this->locale];
        } else {
            return null;
        }
    }
}
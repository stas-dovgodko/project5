<?php
namespace project5\Nls;

class LocaleManager
{
    /**
     * @var ILocaleDetector[]
     */
    private $detectors;

    private $supported;

    private $locale;

    public function __construct($supported = [], array $detectors = [])
    {
        $this->detectors = $detectors;
        $this->supported = $supported;
    }

    public function getSupported()
    {
        return $this->supported;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function isSupported($locale)
    {
        if (empty($this->supported)) {
            return true;
        } else {
            if (in_array($locale, $this->supported)) {
                return true;
            }
        }
        return false;
    }

    public function getLocale()
    {
        if ($this->locale !== null) {
            return $this->locale;
        } else {
            $supported_locales = $this->supported;

            $locale = null;
            if ($this->detectors) {
                foreach ($this->detectors as $detector) {
                    /** $detector ILocaleDetector  */
                    $detector_locales = $detector->determine();

                    if (!empty($detector_locales)) {
                        if (!empty($supported_locales)) {
                            $locales = array_intersect($supported_locales, $detector_locales);
                        } else {
                            $locales = $detector_locales;
                        }
                        if ($locales) {
                            $locale = array_shift($locales);
                            break;
                        }
                    }
                }
            }

            return $this->locale = $locale;
        }
    }
}
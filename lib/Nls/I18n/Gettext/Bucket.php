<?php
namespace project5\Nls\I18n\Gettext;

use Gettext\Translation;
use Gettext\Translations;
use Gettext\Translator;
use project5\Nls\I18n\IBucket;
use project5\Nls\I18n\IKey;

class Bucket implements IBucket
{
    /**
     * @var Translations[]
     */
    private $_translations = [];
    private $_hash;

    /**
     * @var Translator[]
     */
    private $_translators = [];

    private function __construct()
    {

    }

    /**
     * @return string[]
     */
    public function getLocales()
    {
        return array_keys($this->_translations);
    }

    /**
     * @return IKey[]
     */
    public function getList()
    {
        $list = [];
        foreach($this->_translations as $locale => $translations)
        {
            foreach($translations as $translation) {
                /** @var $translation Translation */

                if (isset($list[$original = $translation->getOriginal()])) {
                    $key = $list[$original];
                } else {
                    $key = $list[$original] = new Key($original);
                }

                $key->addTranslation($translations, $locale, $translation);
            }
        }

        return $list;
    }

    /**
     * @param $locale
     * @param $key
     * @return mixed
     */
    public function hasTranslation($locale, $key)
    {
        if (isset($this->_translations[$locale])) {
            if ($this->_translations[$locale]->find(null, $key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $locale
     * @throws \OutOfBoundsException
     * @return string|null
     */
    public function getTranslation($locale, $key, $plural = null)
    {
        if (isset($this->_translations[$locale])) {
            if (!isset($this->_translators[$locale])) {
                $this->_translators[$locale] = new Translator();
                $this->_translators[$locale]->loadTranslations($this->_translations[$locale]);
            }

            if ($plural) {
                return $this->_translators[$locale]->ngettext($key, null, $plural);
            } else {
                return $this->_translators[$locale]->gettext($key);
            }
        }
        return null;
    }


    /**
     * Get storage state hash
     *
     * @return string
     */
    public function getStateHash()
    {
        return $this->_hash;
    }

    /**
     * @param array $filenames
     * @return Bucket[]
     */
    public static function LoadPoFiles(array $filenames = [])
    {
        $list = [];
        foreach ($filenames as $filename) {
            $translations = \Gettext\Translations::fromPoFile($filename); /** @var Translations $translations */

            $domain = (string)$translations->getDomain();
            if (isset($list[$domain])) {
                $gettext = $list[$domain];
            } else {
                $gettext = new Bucket();
                $list[$domain] = $gettext;
            }


            $gettext->_translations[$translations->getLanguage()] = $translations;
            $gettext->_hash = md5($gettext->_hash . $filename. (is_file($filename) ? filemtime($filename) : filemtime(__FILE__)));
        }

        return $list;
    }


    /**
     * @param $dir
     * @return Bucket[]
     */
    public static function ScanPoFiles($dir)
    {
        $list = [];
        foreach (glob($dir . DIRECTORY_SEPARATOR . "*" . DIRECTORY_SEPARATOR) as $subdir) {
            if (is_dir($subdir)) {
                foreach(self::ScanPoFiles($subdir) as $domain => $gettext) {
                    $list[$domain] = $gettext;
                }
            }
        }

        foreach (glob($dir . DIRECTORY_SEPARATOR . "*.po") as $filename) {
            $translations = \Gettext\Translations::fromPoFile($filename); /** @var Translations $translations */

            $domain = (string)$translations->getDomain();
            if (isset($list[$domain])) {
                $gettext = $list[$domain];
            } else {
                $gettext = new Bucket();
                $list[$domain] = $gettext;
            }

            $gettext->_translations[$translations->getLanguage()] = $translations;
            $gettext->_hash = md5($gettext->_hash . $filename. (is_file($filename) ? filemtime($filename) : filemtime(__FILE__)));
        }

        return $list;
    }
}
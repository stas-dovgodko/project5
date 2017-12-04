<?php
namespace project5\Nls\I18n\Gettext;

use Gettext\Translations;
use project5\Nls\I18n\Translator as SuperTranslator;

/**
 * Production-ready gettext fast translator
 *
 * Class Translator
 * @package project5\Nls\I18n\Gettext
 */
class Translator extends SuperTranslator
{
    private $_dir;
    private $dumpPot;
    private $locales = [];

    private $_domains = [];

    protected $translations;

    public function __construct($dir, $locales, $dumpPotTo = null)
    {
        $this->_dir = $dir;
        $this->dumpPot = $dumpPotTo;
        $this->locales = $locales;
    }

    public function setLocaleAlias($locale, $systemAlias)
    {
        $locales = is_scalar($systemAlias)? [$systemAlias] : $systemAlias;

        if (!isset($this->locales[$locale])) $this->locales[$locale] = [];

        $this->locales[$locale] = array_merge($this->locales[$locale], $locales);

        return $this;
    }

    /**
     * @param $locale
     * @param $key
     * @param null $domain
     * @return string|null
     */
    public function getTranslation($locale, $key, $domain = null)
    {
        $translation = parent::getTranslation($locale, $key, $domain);

        if ($translation === null) {
            $this->_setLocale($locale);

            if (!$domain) $domain = 'default';
            if (!isset($this->_domains[$domain])) {
                bindtextdomain($domain, $this->_dir);
                bind_textdomain_codeset ($domain, 'UTF-8');

                $this->_domains[$domain] = $this->_dir;
            }

            $translation = dgettext($domain, $key);
        }



        if ($this->dumpPot) {
            $pot_file = rtrim($this->dumpPot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $locale.'_'.($domain ? $domain : 'default').'.pot';

            if (is_writable(dirname($pot_file))) {
                if (!$this->translations[$domain]) {
                    $translations = new Translations();
                    $translations->setDomain($domain);
                    $translations->setLanguage($locale);
                    $this->translations[$domain] = $translations;
                } else {
                    $translations = $this->translations[$domain];
                }

                if (!$translations->find('', $key)) {
                    $translations->insert('', $key)->setTranslation($translation);
                }
            }
        }
        return $translation;
    }

    public function __destruct()
    {
        if ($this->dumpPot && $this->translations) {
            foreach($this->translations as $domain => $new_translation) {
                $pot_file = rtrim($this->_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $new_translation->getLanguage().'_'.($domain ? $domain : 'default') . '.pot';

                if (is_file($pot_file)) {
                    $translations = \Gettext\Translations::fromPoFile($pot_file);
                    $translations->mergeWith($new_translation);
                } else {
                    $translations = clone $new_translation;
                }

                if (is_writable(dirname($pot_file))) {
                    $translations->toPoFile($pot_file);
                }
                unset($this->translations[$domain]);
            }
        }
    }

    protected function _setLocale($locale)
    {
        if (getenv('LANG') !== $locale)
        {
            putenv("LANG=$locale");


            $system_locale = isset($this->locales[$locale]) ? $this->locales[$locale] : $locale;
            if (!is_array($system_locale)) {
                $system_locale = [$system_locale];
            }

            if (($pos = strrpos($locale, '.')) !== false) {
                $locale_without_cp = substr($locale,0,$pos);
            } else {
                $locale_without_cp = $locale;
            }
            $locale_with_cp = $locale_without_cp.'_'.strtoupper($locale_without_cp);

            $system_locale[] = $locale;
            $system_locale[] = $locale.'.utf-8';
            $system_locale[] = $locale.'.utf8';
            $system_locale[] = $locale.'.UTF-8';
            $system_locale[] = $locale.'.UTF8';
            $system_locale[] = $locale.'.65001'; // windows UTF-8 codepage
            $system_locale[] = $locale_with_cp;
            $system_locale[] = $locale_with_cp.'.utf-8';
            $system_locale[] = $locale_with_cp.'.utf8';
            $system_locale[] = $locale_with_cp.'.UTF-8';
            $system_locale[] = $locale_with_cp.'.UTF8';
            $system_locale[] = $locale_with_cp.'.65001'; // windows UTF-8 codepage
            $system_locale[] = $locale_without_cp;
            $system_locale[] = $locale_without_cp;
            $system_locale[] = $locale_without_cp.'.utf-8';
            $system_locale[] = $locale_without_cp.'.utf8';
            $system_locale[] = $locale_without_cp.'.UTF-8';
            $system_locale[] = $locale_without_cp.'.UTF8';
            $system_locale[] = $locale_without_cp.'.65001'; // windows UTF-8 codepage
            $system_locale[] = $locale_without_cp;
            $system_locale[] = str_replace('_', '-', $locale_without_cp); // windows


            $found_locale = setlocale(defined('LC_MESSAGES') ? LC_MESSAGES : LC_ALL, $system_locale);

            if ($found_locale) putenv("LC_MESSAGES=$found_locale"); else putenv("LC_MESSAGES=$locale"); // windows

            return $found_locale;
        } else {
            return setlocale(defined('LC_MESSAGES') ? LC_MESSAGES : LC_ALL, 0);
        }

    }

    /**
     * @param $locale
     * @param $key
     * @param $number
     * @param null $domain
     * @return string|null Null if not specified
     */
    public function getPlural($locale, $key, $pluralKey, $number, $domain = null)
    {
        $translation = parent::getTranslation($locale, $key, $domain);

        if ($translation === null) {
            $this->_setLocale($locale);

            bindtextdomain($domain ? $domain : '*', $this->_dir);
            bind_textdomain_codeset ($domain ? $domain : '*', 'utf-8');

            return ngettext($key, $pluralKey, $number);
        } else {
            return $translation;
        }
    }
}
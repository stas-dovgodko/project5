<?php
namespace project5\Nls\I18n\Gettext;

use Gettext\Translation;
use Gettext\Translations;
use project5\Nls\I18n\IKey;

class Key implements IKey
{
    private $_original;
    private $_translations = [];
    private $_comments = [];

    /**
     * @var string
     */
    private $_authors = [];

    public function __contruct($original)
    {
        $this->_original = $original;
    }

    public function addTranslation(Translations $translations, $locale, Translation $translation)
    {
        $this->_comments = array_unique(array_merge($this->_comments, $translation->getExtractedComments()));

        $headers = $translations->getHeaders();

        if (isset($headers['Language-Team'])) {
            $this->_authors[$locale] = $headers['Language-Team'];
        } elseif (isset($headers['Last-Translator'])) {
            $this->_authors[$locale] = $headers['Last-Translator'];
        }

        if (isset($headers['Plural-Forms'])) {
            // plural format
        }

        if ($translation->hasPlural()) {
            $this->_translations[$locale] = $translation->getPluralTranslation();
        } else $this->_translations[$locale] = $translation->getTranslation();
    }

    /**
     * @param $locale
     * @return string|string[]
     */
    public function getTranslation($locale)
    {
        return isset($this->_translations[$locale]) ? $this->_translations[$locale] : null;
    }

    /**
     * @param $locale
     * @return boolean
     */
    public function hasTranslation($locale)
    {
        return (isset($this->_translations[$locale]));
    }

    public function getAuthor($locale)
    {
        return isset($this->_authors[$locale]) ? $this->_authors[$locale] : null;
    }

    /**
     * @return string
     */
    public function getComments()
    {
        return implode("\n", $this->_comments);
    }
}
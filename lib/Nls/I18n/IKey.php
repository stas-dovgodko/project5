<?php
namespace project5\Nls\I18n;

/**
 * 
 *
 * @author stas
 * 
 */
interface IKey
{
    /**
     * @param $locale
     * @return string|string[]
     */
    public function getTranslation($locale);

    /**
     * @param $locale
     * @return boolean
     */
    public function hasTranslation($locale);

    /**
     * @param $locale
     * @return string
     */
    public function getAuthor($locale);

    /**
     * @return string
     */
    public function getComments();
}
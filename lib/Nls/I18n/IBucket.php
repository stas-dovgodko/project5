<?php
namespace project5\Nls\I18n;

interface IBucket
{
    /**
     * @return IKey[]
     */
    public function getList();

    /**
     * @param $locale
     * @param $key
     * @return bool
     */
    public function hasTranslation($locale, $key);

    /**
     * @param $locale
     * @throws \OutOfBoundsException
     * @return string|null
     */
    public function getTranslation($locale, $key, $plural = null);




    /**
     * Get storage state hash
     *
     * @return string
     */
    public function getStateHash();

    /**
     * @return string[]
     */
    public function getLocales();
}
<?php
namespace project5\Provider;

interface ICanSearch
{
    /**
     * @return string|null
     */
    public function getSearchTerm();

    /**
     * @param string|null $term
     * @return ICanSearch
     */
    public function setSearchTerm($term);
}
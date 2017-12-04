<?php
namespace project5\Provider\Cache;

interface ITag
{
    /**
     * @return string|null
     */
    public function getStateHash();
}
<?php
namespace project5\Provider\Cache;

/**
 * Class ICanBeCached
 * @package project5\Provider\Cache
 */
interface ICanBeCached
{
    /**
     * @return int|null
     */
    public function getCacheLifetime();

    /**
     * @param bool $hydrate True to obtain hydrate tags
     * @return mixed
     */
    public function getCacheTags($hydrate = false);


}
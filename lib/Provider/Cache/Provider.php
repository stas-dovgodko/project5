<?php
namespace project5\Provider\Cache;

use project5\IProvider;
use project5\Provider\IEntity;
use project5\Provider\IField;
use project5\Provider\ICanBePaged;
use project5\Provider\ICanBeFiltered;
use project5\Provider\ICanBeSorted;
use project5\Provider\Cache\Tag\Object as TagObject;
use project5\Provider\Cache\Tag\Scalar as TagScalar;

use Doctrine\Common\Cache\CacheProvider;

/**
 * Class Provider
 * @package project5\Provider\Cache
 */
class Provider implements IProvider
{
    /**
     * @var IProvider
     */
    protected $_provider;

    /**
     * @var CacheProvider
     */
    protected $_cache;

    public function __construct(IProvider $aggregate, CacheProvider $cache)
    {
        $this->_provider = $aggregate;
        $this->_cache = $cache;
    }

    /**
     * List of IField's
     *
     * @param callable $filter
     * @return \Traversable|IField[]
     */
    public function getFields(callable $filter = null)
    {
        return $this->_provider->getFields($filter);
    }

    /**
     * @param $uid
     * @return IEntity
     */
    public function findByUid($uid)
    {
        return $this->_provider->findByUid($uid);
    }

    /**
     * IEntity's
     *
     * @return \Traversable|IEntity[]
     */
    public function iterate()
    {
        $key = $this->_getCacheKey();
        $list = $this->_cache->fetch($key);

        if (is_array($list)) {
            return $list;
        }

        $list = array();
        foreach($this->_provider->iterate() as $item) {
            $list[] = $item;
        }
        $this->_cache->save($key, $list, $this->_getCacheLifetime());
        return $list;
    }

    /**
     * Create empty entity
     *
     * @return IEntity
     */
    public function createEntity()
    {
        return $this->_provider->createEntity();
    }

    /**
     * @param IProvider $provider
     * @param bool $hydrate
     * @return ITag[]
     */
    public static function GetProviderTags(IProvider $provider, $hydrate = false)
    {
        if ($provider instanceof ICanBeCached) {
            $tags = $provider->getCacheTags($hydrate);
        } else {
            $tags = [new TagObject($provider)];

            if (!$hydrate) {
                if ($provider instanceof ICanBePaged) {
                    $tags[] = new TagScalar([$provider->getPagerOffset(), $provider->getPagerLimit()]);
                }
                if ($provider instanceof ICanBeFiltered) {

                }
                if ($provider instanceof ICanBeSorted) {

                }
            }
        }

        return $tags;
    }

    protected function _getCacheKey($uid = null)
    {
        $tags = self::GetProviderTags($this->_provider, ($uid !== null));

        assert('is_array($tags)');

        if ($uid !== null) {
            $tags[] = new TagScalar($uid);
        }

        $key = '';
        if (is_array($tags)) foreach($tags as $tag) { /** @var $tag ITag */
            assert('$tag instanceof \project5\Provider\Cache\ITag');
            $state = $tag->getStateHash();
            if ($state !== null) {
                $key .= $state;
            } else {
                $key = null;
                break;
            }
        }

        return $key;
    }

    protected function _getCacheLifetime()
    {
        if ($this->_provider instanceof ICanBeCached) {
            return $this->_provider->getCacheLifetime();
        } else {
            return null;
        }
    }

    /**
     * @param IProvider $provider
     * @param CacheProvider $cache
     * @return Provider virtual cache provider
     */
    public static function Create(IProvider $provider, CacheProvider $cache)
    {
        $class_name = 'VirtualProvider';

        $class_name = __NAMESPACE__ . '\\' . $class_name;
        return new $class_name($provider, $cache);
    }
}
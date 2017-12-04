<?php
namespace project5\Provider;


use project5\IProvider;
use project5\Provider\Sort\E\UnsupportedException;
use project5\Provider\Sort\ISorter;
use project5\Provider\Cache\ICanBeCached;
use project5\Provider\Cache\Provider as CacheProvider;
use project5\Provider\Cache\Tag\Object as TagObject;
use project5\Provider\Cache\Tag\CallableType as TagCallableType;

class Aggregator implements IProvider, ICanBeSorted, ICanBeCached
{
    private $_srcProvider;

    /**
     * @var IField
     */
    private $_groupBy;
    /**
     * @var callable|null
     */
    private $_groupByCallback;
    private $_aggrerators = [];

    /**
     * @var ISorter[]
     */
    private $_sorters = [];

    public function __construct(IProvider $src, IField $groupBy, callable $groupByCallback = null)
    {
        $this->_srcProvider = $src;
        $this->_groupBy = $groupBy;
        $this->_groupByCallback = $groupByCallback;
    }

    public function aggregate(IField $field, callable $aggrerator)
    {
        $this->_aggrerators[] = [$field, $aggrerator];

        return $this;
    }

    /**
     * List of IField's
     *
     * @param callable $filter
     * @return \Traversable|IField[]
     */
    public function getFields(callable $filter = null)
    {
        $fields = [$this->_groupBy];

        foreach($this->_aggrerators as list($field, $aggrerator)) {
            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * @param $uid
     * @return IEntity
     */
    public function findByUid($uid)
    {
        $list = $this->_aggregate();

        return isset($list[$uid]) ? $list[$uid] : null;
    }

    private function _aggregate()
    {
        $list = array();
        foreach($this->_srcProvider->iterate() as $entity) /** @var $entity IEntity */
        {
            $uid = $this->_groupBy->get($entity);

            if ($this->_groupByCallback) {
                $uid = call_user_func($this->_groupByCallback, $uid);
            }

            if (isset($list[$uid])) {
                $aggregated = $list[$uid];
            } else {
                $aggregated = $this->createEntity();
                $aggregated->setUid($uid);
                $this->_groupBy->set($aggregated, $uid);

            }

            foreach($this->_aggrerators as list($field, $aggrerator)) {
                /** @var $field IField */
                /** @var $aggrerator callable */


                /** @var $aggregated IEntity */
                $field->set($aggregated, call_user_func($aggrerator, $entity, $field->get($aggregated)));
            }
            $list[$uid] = $aggregated;
        }

        if ($this->_sorters) {
            $sorters = $this->_sorters;
            uasort($list, function(IEntity $e1, IEntity $e2) use($sorters) {
                foreach($sorters as list($field, $sorter)) {

                    $a1 = $field->get($e1); $a2 = $field->get($e2);
                    $cmp = $sorter->compare($a1, $a2);
                    if ($cmp === ISorter::IS_SAME) {
                        continue;
                    } else {
                        return $cmp;
                    }
                }
            });
        }

        return $list;
    }

    /**
     * IEntity's
     *
     * @return \Traversable|IEntity[]
     */
    public function iterate()
    {
        foreach($this->_aggregate() as $entity) {
            yield $entity;
        }
    }

    /**
     * Create empty entity
     *
     * @return Entity
     */
    public function createEntity()
    {
        return new Entity();
    }

    /**
     * @return IField[]
     */
    public function getSortable()
    {
        return $this->getFields();
    }

    /**
     * @throws UnsupportedException
     * @param IField $field
     * @param ISorter $sorter
     * @return ICanBeSorted
     */
    public function sort(IField $field, ISorter $sorter)
    {
        if (in_array($field, $this->getSortable(), true)) {
            $this->_sorters[] = array($field, $sorter);
        }
        else throw new UnsupportedException(sprintf('%s field sorting is not supported by this provider', $field->getName()));



        return $this;
    }

    /**
     * @return ICanBeSorted
     */
    public function resetSorting()
    {
        $this->_sorters = [];

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCacheLifetime()
    {
        if ($this->_srcProvider instanceof ICanBeCached) return $this->_srcProvider->getCacheLifetime();
        else {
            return null;
        }
    }

    /**
     * @param bool $hydrate True to obtain hydrate tags
     * @return mixed
     */
    public function getCacheTags($hydrate = false)
    {
        $tags = CacheProvider::GetProviderTags($this->_srcProvider, $hydrate);
        $tags[] = new TagObject($this->_groupBy);

        if (!$hydrate && $this->_sorters) foreach($this->_sorters as $sorter) {
            $tags[] = new TagObject($sorter);
        }

        if ($this->_aggrerators) foreach($this->_aggrerators as list($field, $aggrerator)) {
            /** @var $field IField */
            /** @var $aggrerator callable */

            $tags[] = new TagObject($field);
            $tags[] = new TagCallableType($aggrerator);
        }
        if ($this->_groupByCallback) {
            $tags[] = new TagCallableType($this->_groupByCallback);
        }

        return $tags;
    }

}
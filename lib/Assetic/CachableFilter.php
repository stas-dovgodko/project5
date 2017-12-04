<?php

    namespace  project5\Assetic;

    use Assetic\Asset\AssetInterface;
    use Assetic\Filter\FilterInterface;
    use Doctrine\Common\Cache\Cache;


    class CachableFilter implements FilterInterface
    {
        /**
         * @var FilterInterface
         */
        protected $filter;

        /**
         * @var Cache
         */
        protected $cache;

        public function __construct(Cache $cache, FilterInterface $filter)
        {
            $this->cache = $cache;
            $this->filter = $filter;
        }

        protected function getKey(AssetInterface $asset, $method)
        {
            return get_class($this->filter).'_'.$method.'_'.$asset->getSourcePath().'_'.md5($asset->getContent());//.'_'.md5(sprintf('%s', serialize($this->filter)));
        }

        /**
         * Filters an asset after it has been loaded.
         *
         * @param AssetInterface $asset An asset
         */
        public function filterLoad(AssetInterface $asset)
        {
            $key = $this->getKey($asset, 'load');

            if (is_array($content = $this->cache->fetch($key))) {
                $asset->setContent($content['content']);
                $asset->setTargetPath($content['target']);
                $asset->setValues($content['values']);
            } else {
                $this->filter->filterLoad($asset);

                $this->cache->save($key, [
                    'content' => $asset->getContent(),
                    'target' => $asset->getTargetPath(),
                    'values' => $asset->getValues()
                ]);
            }
        }

        /**
         * Filters an asset just before it's dumped.
         *
         * @param AssetInterface $asset An asset
         */
        public function filterDump(AssetInterface $asset)
        {
            $key = $this->getKey($asset, 'dump');

            if (is_array($content = $this->cache->fetch($key))) {
                $asset->setContent($content['content']);
                $asset->setTargetPath($content['target']);
                $asset->setValues($content['values']);
            } else {
                $this->filter->filterDump($asset);

                $this->cache->save($key, [
                    'content' => $asset->getContent(),
                    'target' => $asset->getTargetPath(),
                    'values' => $asset->getValues()
                ]);
            }
        }
    }
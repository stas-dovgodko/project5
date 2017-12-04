<?php
/**
 * Created by PhpStorm.
 * User: Stas
 * Date: 1/14/2016
 * Time: 9:14 PM
 */
namespace project5\Assetic;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\AssetReference;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\Worker\WorkerInterface;
use project5\File;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Doctrine\Common\Cache\Cache;

class Factory extends AssetFactory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Worker|null
     */
    protected $worker;

    protected $protectedRoot;

    protected $cache;

    static private $assets = null;

    public function __construct(WorkerInterface $worker, Cache $cache, $root, $debug = false)
    {
        parent::__construct($this->protectedRoot = $root, $debug);
        
        $this->cache = $cache;


        if (self::$assets === null) {
            self::$assets = [];
        }

        $this->setWorker($worker);
    }

    public function getCache() {
        return $this->cache;
    }



    /**
     * Add external resource
     *
     * @param AssetInterface $asset
     * @return AssetInterface
     */
    public function addResource(AssetInterface $asset, AssetInterface $resource)
    {
        $key = $asset->getSourceRoot() . $asset->getSourcePath();

        self::$assets[$key][] = $resource;

        return $resource;
    }

    /**
     * @return array
     */
    public function getResources(AssetInterface $asset)
    {
        $key = $asset->getSourceRoot() . $asset->getSourcePath();

        if (array_key_exists($key, self::$assets)) {
            return self::$assets[$key];
        } else {
            return [];
        }
    }

    public function getRoot()
    {
        return $this->protectedRoot;
    }

    public function addWorker(WorkerInterface $worker)
    {
        parent::addWorker($worker);

        if ($worker instanceof Worker) {
            $this->worker = $worker;
        }
    }

    public function getFilter($name)
    {
        $filter = parent::getFilter($name);

        return new CachableFilter($this->cache, $filter);
    }

    /**
     * @param Worker $worker
     */
    public function setWorker(Worker $worker)
    {
        $this->worker = $worker;

        parent::addWorker($worker);
    }

    /**
     * @return Worker
     */
    public function getWorker()
    {
        return $this->worker;
    }

    protected function createAssetCollection(array $assets = array(), array $options = array())
    {
        if ($this->logger) {
            foreach($assets as $asset) {
                if ($asset instanceof AssetInterface) $this->logger->debug('HTTP collection with: ' . $asset->getSourcePath(), $assets->getVars());
            }
        }

        return parent::createAssetCollection($assets, $options);
    }

    public function createGlobAsset($glob, $root = null, $vars)
    {
        if ($this->logger) {
            //$this->logger->debug('Glof asset '.$glob, $vars);
        }

        return new Glob($glob, array(), $root, $vars);
    }

    public function createHttpAsset($sourceUrl, $vars)
    {


        if ($this->logger) {
            //$this->logger->debug('HTTP asset '.$sourceUrl, $vars);
        }

        $asset = parent::createHttpAsset($sourceUrl, $vars);

        if (substr($asset->getSourcePath(), -4) === '.css') {

            $asset->ensureFilter(new Filter($this));
        }


        return $asset;
    }

    protected function createAssetReference($name)
    {
        if ($this->logger) {

            //$this->logger->debug('Reference asset '.$name);
        }

        return new Reference($this->getAssetManager(), $name);
    }
    


    protected function createFilteredAssetReference($name, $ext)
    {
        $asset = new Reference($this->getAssetManager(), $name);


        /*$asset = $this->getAssetManager()->get($name);

        print_r($asset);
        die('--');

        if ($asset instanceof Reference) {
            $list = $asset->all($ext);
        } else {
            $list = [$asset];
        }*/
        $list = $asset->all($ext);

        if ($this->logger) {

            //$this->logger->debug('Filtered reference asset '.$name.' for '.$ext);
        }


        $collection = new AssetCollection(array_values($list), $asset->getFilters(), $asset->getSourceRoot(), $asset->getVars());
        $collection->setTargetPath($asset->getTargetPath());

        return $collection;
    }

    public function createFileAsset($source, $root = null, $path = null, $vars)
    {


        $asset = parent::createFileAsset($source, $root, $path, $vars);

        if (substr($asset->getSourcePath(), -4) === '.css') {

            $asset->ensureFilter(new Filter($this));

            if ($this->logger) {
                //$this->logger->debug('File asset ' . $source, $vars);
            }
        }

        
        return $asset;
    }
    
    private function flattenCollection(AssetCollection $collection, array &$flatten)
    {
        foreach ($collection->all() as $asset) {

            if ($asset instanceof  AssetCollection) {



                $this->flattenCollection($asset, $flatten);
            } else {
                $path = realpath($xpath = $collection->getSourceRoot() . DIRECTORY_SEPARATOR . $asset->getSourceRoot() .DIRECTORY_SEPARATOR.$asset->getSourcePath());

                if ($path && array_key_exists($path, $flatten)) {
                    $collection->removeLeaf($asset);
                } else {
                    $flatten[$path] = $asset;
                }
            }
        }
    }



    /**
     * Parses an input string string into an asset.
     *
     * The input string can be one of the following:
     *
     *  * A reference:     If the string starts with an "at" sign it will be interpreted as a reference to an asset in the asset manager
     *  * An absolute URL: If the string contains "://" or starts with "//" it will be interpreted as an HTTP asset
     *  * A glob:          If the string contains a "*" it will be interpreted as a glob
     *  * A path:          Otherwise the string is interpreted as a filesystem path
     *
     * Both globs and paths will be absolutized using the current root directory.
     *
     * @param string $input   An input string
     * @param array  $options An array of options
     *
     * @return AssetInterface An asset
     */
    protected function parseInput($input, array $options = array())
    {
        if ('@' == $input[0] && isset($options['output'])) {
            
            $file = new File($options['output']);
            $ext = $file->getExtension();
            
            if ($ext) {
                return $this->createFilteredAssetReference(substr($input, 1), $ext);
            }
        }

        return parent::parseInput($input, $options);
    }


}
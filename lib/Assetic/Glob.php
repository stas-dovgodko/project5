<?php
/**
 * Created by PhpStorm.
 * User: Stas
 * Date: 3/15/2016
 * Time: 1:18 PM
 */

namespace project5\Assetic;


use Assetic\Asset\AssetCollection;
use Assetic\Filter\FilterInterface;
use Assetic\Util\VarUtils;

class Glob extends AssetCollection
{
    protected $factory;
    private $globs;
    private $initialized;

    /**
     * Constructor.
     *
     * @param string|array $globs A single glob path or array of paths
     * @param array $filters An array of filters
     * @param string $root The root directory
     * @param array $vars
     */
    public function __construct(Factory $factory, $globs, $filters = array(), $root = null, array $vars = array())
    {
        $this->factory = $factory;
        $this->globs = (array)$globs;
        $this->initialized = false;

        parent::__construct(array(), $filters, $root, $vars);
    }

    public function all()
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return parent::all();
    }

    public function load(FilterInterface $additionalFilter = null)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        parent::load($additionalFilter);
    }

    public function dump(FilterInterface $additionalFilter = null)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return parent::dump($additionalFilter);
    }

    public function getLastModified()
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return parent::getLastModified();
    }

    public function getIterator()
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return parent::getIterator();
    }

    public function setValues(array $values)
    {
        parent::setValues($values);
        $this->initialized = false;
    }

    /**
     * Initializes the collection based on the glob(s) passed in.
     */
    private function initialize()
    {
        foreach ($this->globs as $glob) {
            $glob = VarUtils::resolve($glob, $this->getVars(), $this->getValues());

            if (false !== $paths = glob($glob)) {
                foreach ($paths as $path) {
                    if (is_file($path)) {
                        $asset = $this->factory->createFileAsset($path, $this->getSourceRoot(), null, $this->getVars());
                        $asset->setValues($this->getValues());
                        $this->add($asset);
                    }
                }
            }
        }

        $this->initialized = true;
    }
}
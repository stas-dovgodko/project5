<?php
/**
 * Created by PhpStorm.
 * User: Stas
 * Date: 1/14/2016
 * Time: 9:14 PM
 */
namespace project5\Debug\Decorator\Assetic;

use project5\Assetic\Worker as ConcreteWorker;
use Assetic\Asset\AssetInterface;
use Assetic\Factory\AssetFactory;
use DebugBar\DataCollector\TimeDataCollector;
use project5\Debug\Decorator\TimeCollector;

class Worker extends \project5\Assetic\Worker
{
    use TimeCollector;

    /**
     * @var ConcreteWorker
     */
    protected $worker;

    public function __construct(ConcreteWorker $worker)
    {
        parent::__construct($worker->output, $worker->cache);
        $this->worker = $worker;
    }

    public function setChmod($mode)
    {
        $this->worker->setChmod($mode);
    }

    public function setIgnoreFile($flag = true)
    {
        $this->worker->setIgnoreFile($flag);
    }

    /**
     * Processes an asset.
     *
     * @param AssetInterface $asset   An asset
     * @param AssetFactory   $factory The factory
     *
     * @return AssetInterface|null May optionally return a replacement asset
     */
    public function process(AssetInterface $asset, AssetFactory $factory)
    {
        $start = microtime(true);
        $return = $this->worker->process($asset, $factory);

        $end = microtime(true);

        if ($this->timeDataCollector) {
            $name = sprintf("assetic.process(%s)", $asset->getTargetPath());
            $this->timeDataCollector->addMeasure($name, $start, $end);
        }

        return $return;
    }


}
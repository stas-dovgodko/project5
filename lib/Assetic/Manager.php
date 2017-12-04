<?php
/**
 * Created by PhpStorm.
 * User: Stas
 * Date: 1/14/2016
 * Time: 9:14 PM
 */
namespace project5\Assetic;

use Assetic\Asset\AssetInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Assetic\AssetManager;
use project5\DI\IContainer;
use project5\DI\Container;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetReference;
use Assetic\Asset\FileAsset;
use Assetic\Asset\HttpAsset;
use Assetic\Asset\GlobAsset;
use Symfony\Component\DependencyInjection\Reference as DependencyInjectionReference;


class Manager extends AssetManager implements IContainer, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var \SplObjectStorage
     */
    protected $resources;
    
    public function __construct()
    {
        $this->resources = new \SplObjectStorage();
    }

    /**
     * @param Container $container
     * @param $id
     * @return mixed
     */
    public static function Setup(Container $container, $id)
    {
        // nothing todo
        return [
            'id' => $id,
            'factory' => $container->getDefinition('asset.factory'),
        ];
    }

    /**
     * @param AssetInterface $asset
     * @return array|null
     */
    public function getResources(AssetInterface $asset)
    {
        if ($this->resources->contains($asset)) {
            return (array)$this->resources->offsetGet($asset);
        } else {
            return null;
        }
    }

    /**
     * Registers an asset to the current asset manager.
     *
     * @param string         $name  The asset name
     * @param AssetInterface $asset The asset
     *
     * @throws \InvalidArgumentException If the asset name is invalid
     */
    public function setWithResources($name, AssetInterface $asset, array $globs = [])
    {
        parent::set($name, $asset);

        if (!empty($globs)) {
            if (!$this->resources->contains($asset)) {
                $this->resources->attach($asset);
            }

            $existed = (array)$this->resources->offsetGet($asset);

            $this->resources->offsetSet($asset, array_unique($existed += $globs));
        }
    }



    public static function Inject(Definition $definition, $config, ContainerBuilder $builder, $setupOptions = null)
    {
        foreach($config as $name => $list) {
            $collection = new Definition(AssetCollection::class);

            if (isset($list['require'])) {
                foreach($list['require'] as $refname) {

                    $ref_def = new Definition(Reference::class, [new DependencyInjectionReference($setupOptions['id']), $refname]);
                    $collection->addMethodCall('add', [$ref_def]);
                }
            }

            if (isset($list['file'])) {
                foreach($list['file'] as $file) {
                    $file_def = new Definition(FileAsset::class, [$file, null, null, []]);

                    $file_def->setFactory([$setupOptions['factory'], 'createFileAsset']);

                    $collection->addMethodCall('add', [$file_def]);
                }
            }

            if (isset($list['http'])) {
                foreach($list['http'] as $url) {
                    $http_def = new Definition(HttpAsset::class, [$url, []]);


                    $http_def->setFactory([$setupOptions['factory'], 'createHttpAsset']);

                    $collection->addMethodCall('add', [$http_def]);
                }
            }

            if (isset($list['glob'])) {
                foreach($list['glob'] as $pattern) {
                    $http_def = new Definition(GlobAsset::class, [$pattern]);
                    $collection->addMethodCall('add', [$http_def]);
                }
            }
            $globs = [];
            if (isset($list['copy'])) {
                $globs = $list['copy'];
            }



            $definition->addMethodCall('setWithResources', [$name, $collection, $globs]);
        }
    }
}
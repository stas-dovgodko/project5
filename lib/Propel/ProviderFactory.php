<?php
namespace project5\Propel;


use project5\DI\Container;
use project5\DI\IContainer;
use project5\IFactory;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;

class ProviderFactory implements IFactory, IContainer
{

    /**
     * Create ne provider instance
     *
     * @param array $arguments
     * @return Provider
     */
    public function FactoryMethod(array $arguments)
    {
        $query = null; $connection = null;
        if (isset($arguments['connection'])) {
            $connection = $arguments['connection'];
        }
        if (isset($arguments['query-class'])) {
            $modelQueryClassname = $arguments['query-class'];

            $query = $modelQueryClassname::Create();
        } elseif (isset($arguments['query'])) {
            $query = $arguments['query'];
        }

        if ($connection instanceof ConnectionInterface) {
            // ok
        } elseif ($connection !== null) {
            throw new \BadMethodCallException('Wrong connection instance');
        }

        if ($query instanceof ModelCriteria) {
            return new Provider($query, $connection);
        } else {
            throw new \BadMethodCallException('Wrong query/query-class argument');
        }
    }

    public static function Setup(Container $container, $id)
    {

    }

    public static function Inject(Definition $definition, $config, ContainerBuilder $builder, $setupOptions = null)
    {
        foreach($config as $id => $data) {
            $d = new Definition(Provider::class);
            $d->setFactory([$definition, 'FactoryMethod']);
            $d->setArguments([$data]);
            $d->setShared(false);

            $builder->setDefinition($id, $d);
        }
    }
}
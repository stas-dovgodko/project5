<?php
namespace project5\Crud;

use project5\DI\Container;
use project5\DI\IContainer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class Factory implements IContainer {

    public static function Setup(Container $container, $id)
    {
        // TODO: Implement Setup() method.
    }

    public static function Inject(Definition $definition, $config, ContainerBuilder $builder, $setupOptions = null)
    {
        foreach($config as $id => $option) {

            $class = isset($option['class']) ? $option['class'] : Page::class;

            $arguments = isset($option['arguments']) ? $option['arguments'] : [new Reference('this')];

            $d = new Definition($class);

            if (isset($option['factory'])) {
                $d->setFactory($option['factory']);
            } else {
                $d->setClass($class);
            }
            $d->setArguments($arguments);

            if (isset($option['provider'])) {
                $d->addMethodCall('addDefault', ['provider', $option['provider']]);
            }
            if (isset($option['default']) && is_array($option['default'])) {
                foreach($option['default'] as $property_name => $property) {
                    $d->addMethodCall('addDefault', [$property_name, $property]);
                }
            }

            $d->setScope(ContainerInterface::SCOPE_PROTOTYPE);

            $builder->setDefinition($id, $d);
        }
    }
}
<?php
namespace project5\DI;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

interface IContainer
{
    /**
     * @param Container $container
     * @param $id
     * @return mixed
     */
    public static function Setup(Container $container, $id);

    public static function Inject(Definition $definition, $config, ContainerBuilder $builder, $setupOptions = null);
}
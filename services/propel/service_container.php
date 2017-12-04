<?php

use project5\DI\Container;

/** @var $container Container */




if (!function_exists('propel_service_container_factory') && !$container->hasDefinition('propel.service_container')) {
    function propel_service_container_factory(Container $container) {
        $params = $container->getParameter('propel.connections');

        $serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
        $serviceContainer->checkVersion('2.0.0-dev');
        if ($serviceContainer instanceof  \Propel\Runtime\ServiceContainer\StandardServiceContainer) {
            $logger = $container->get('logger');
            $serviceContainer->setLogger('defaultLogger', $container->get('logger'));
        }

        foreach($params as $name => $info) {
            list($adapter, $settings) = $info;
            $serviceContainer->setAdapterClass($name, $adapter);

            $manager = new \Propel\Runtime\Connection\ConnectionManagerSingle();
            $manager->setConfiguration($settings);
            $manager->setName($name);


            $serviceContainer->setConnectionManager($name, $manager);
            $serviceContainer->setDefaultDatasource($name);
        }

        return $serviceContainer;
    };
    $container
        ->register('propel.service_container', "Propel\\Runtime\\ServiceContainer\\ServiceContainerInterface")
        ->setFactory('propel_service_container_factory')
        ->setArguments([$container]);

    $container->addOnCompile(function () use ($container) {
        $container->get('propel.service_container');
    });
}
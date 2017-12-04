<?php

use project5\DI\Container;

/** @var $container Container */




if (!$container->hasDefinition('web.panel.collector.pdo') && $container->hasDefinition('propel.service_container')) {


    function debug_web_panel_collector_pdo_factory(Container $container) {

        $collector = new \DebugBar\DataCollector\PDO\PDOCollector();


        $serviceContainer = $container->get('propel.service_container'); /** @var $serviceContainer Propel\Runtime\ServiceContainer\ServiceContainerInterface **/

        if ($serviceContainer instanceof \Propel\Runtime\ServiceContainer\StandardServiceContainer) {
            foreach ($serviceContainer->getConnectionManagers() as $manager) { /** @var $manager \Propel\Runtime\Connection\ConnectionManagerInterface */
                $traceble_pdo_read = new DebugBar\DataCollector\PDO\TraceablePDO($serviceContainer->getReadConnection($manager->getName())->getWrappedConnection());
                $collector->addConnection($traceble_pdo_read, $manager->getName().'_read');

                $traceble_pdo_write = new DebugBar\DataCollector\PDO\TraceablePDO($serviceContainer->getWriteConnection($manager->getName())->getWrappedConnection());
                $collector->addConnection($traceble_pdo_write, $manager->getName().'_write');
            }
        }


        return $collector;
    };

    $container
        ->register('web.panel.collector.pdo', "\\DebugBar\\DataCollector\\PDO\\PDOCollector")
        ->addTag('web.debug.panel.collector')
        ->setFactory('debug_web_panel_collector_pdo_factory')
        ->setArguments([$container])
        ;
}
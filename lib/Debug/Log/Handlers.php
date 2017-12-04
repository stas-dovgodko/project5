<?php
namespace project5\Debug\Log;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use project5\DI\Container;
use project5\DI\IContainer;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class Handlers implements IContainer
{
    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        if ($logger instanceof Logger) {
            $this->logger = $logger;
        } else {
            // decorate
            $this->logger = new Logger('proxy', [new PsrHandler($logger)]);
        }
    }

    public function addHandler(HandlerInterface $handler)
    {
        $this->logger->pushHandler($handler);
    }

    /**
     * @param Container $container
     * @param $id
     * @return mixed
     * @throws InvalidArgumentException
     */
    public static function Setup(Container $container, $id)
    {
        $container->addOnCompile(function() use ($container, $id) {
            $container->get($id);
        });
    }

    public static function Inject(Definition $definition, $config, ContainerBuilder $builder, $setupOptions = null)
    {
        foreach($config as $ref) {
            if (($ref instanceof Definition) || ($ref instanceof Reference)) {

                $definition->addMethodCall('addHandler', [$ref]);
            }
        }
    }
}
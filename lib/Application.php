<?php
namespace project5;

use Monolog\Handler\NullHandler;
use Monolog\Logger;
use project5\Auth\IArea;
use project5\DI\Container;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Finder\Finder;

class Application implements LoggerAwareInterface, IArea
{
    use LoggerAwareTrait;

    /**
     * @var Container DI container
     */
    protected $container;

    private $configs = [];

    /**
     * @var callable|string
     */
    private $envFinder;

    private $env;

    /**
     * @var string
     */
    private $name = 'app';

    public function __construct($name = 'app')
    {
        $this->name = $name;
        $this->container = new Container([
            __DIR__ . '/../services/'
        ]);


        $this->container->setDefinition('app', new Definition(self::class))->setSynthetic(true);

        $this->container->setDefinition('logger', new Definition(NullLogger::class));


        $this->container->setParameter('app.name', $name);
        $this->container->setParameter('app.dir', getcwd());
        $this->container->setParameter('web.dir', getcwd());
        $this->container->setParameter('compile.dir', sys_get_temp_dir());
        $this->container->setParameter('project5.dir', __DIR__ . '/../');

        $this->container->addExtension('web', function () {
            // empty stub
        });

        $this->envFinder = function($env) {

            return null;
        };
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }




    public function setEnvFinder($finder)
    {
        if (is_callable($finder)) {
            $this->envFinder = $finder;
        } elseif (is_dir($finder)) {
            $this->envFinder = function ($env) use ($finder) {
                if (is_file($filename = $finder . DIRECTORY_SEPARATOR . $env . '.yml')) {
                    return [$filename];
                } else {
                    return null;
                }
            };
        } elseif ($finder instanceof Finder) {
            $this->envFinder = function ($env) use ($finder) {
                $files = [];
                foreach($finder->name($env.'.yml')->files() as $file) {
                    $files[] = $file;
                }

                return !empty($files) ? $files : null;
            };
        } else {
            throw new \InvalidArgumentException('env-finder should be callable, dir or Symfony\Component\Finder\Finder instance');
        }
    }

    /**
     * @param $id
     * @param $service
     * @return Application
     */
    public function inject($id, $service)
    {
        $this->container->set($id, $service);

        return $this;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        $this->container->setLogger($logger);


    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    public function addExtension($name, callable $callback)
    {
        $this->container->addExtension($name, $callback);
    }

    public function setProperty($name, $value)
    {
        $this->container->setParameter($name, $value);
    }

    /**
     * @return void
     */
    public function isConfigured()
    {
        return $this->env;
    }

    /**
     * @return DI\Container
     */
    public function configure($env = null)
    {
        if (!$this->container->isFrozen()) {

            if ($this->configs) {
                foreach ($this->configs as $filename) {
                    $this->container->configure($filename);
                }
            }


            if ($this->logger) {
                $this->container->set('logger', $this->logger);
            }

            if ($env) {
                $configs = call_user_func($this->envFinder, $env);

                if (!empty($configs)) {
                    if (!is_array($configs)) $configs = [$configs];
                    foreach($configs as $file) {
                        $this->container->configure((string)$file);
                    }
                } else {
                    throw new \DomainException(sprintf('Can\'t configure app with "%s" env', $env));
                }
            }


            $this->container->compile();

            $this->container->set('app', $this);

            if ($this->logger) {
                //$this->container->set('logger', $this->logger);
            }

            $this->env = $env ? $env : true;


        }

        return $this->container;
    }


    public function hasConfig()
    {
        return sizeof($this->configs) > 0;
    }

    public function addConfig($filename)
    {
        $this->configs[] = $filename;
    }

    /**
     * @return string
     */
    public function getUid()
    {
        return $this->name;
    }
}

<?php
namespace project5;

use project5\DI\Container;
use project5\DI\IContainer;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

abstract class Plugin implements LoggerAwareInterface, IContainer
{
    use LoggerAwareTrait;

    private static $Added = [];
    private static $Instances = [];

    protected $name;

    /**
     * @var Container
     */
    protected $container;

    final protected function __construct(Container $container)
    {
        $this->container = $container;
        $this->init();
    }

    public static function Setup(Container $container, $id)
    {

    }

    public static function Inject(Definition $definition, $config, ContainerBuilder $builder, $setupOptions = null)
    {

    }


    /**
     * Init instance
     */
    protected function init()
    {

    }

    /**
     * Configure plugin instance
     * This runs on first plugin depends
     *
     * @param array $settings
     * @return void
     */
    protected function config(array $settings = [])
    {

    }

    /**
     * Add plugin
     *
     * @param string plugin name
     * @param string $bootstrapper bootstrapper file
     * @return void
     */
    public static function Add($name) //, $bootstrapper, $callback = null)
    {
        $class = get_called_class();

        Plugin::$Added[$name] = $class;
    }

    /**
     * @param $name
     * @param Container $container
     * @param array $settings
     * @return Plugin
     * @throws \BadFunctionCallException
     * @throws \InvalidArgumentException
     */
    public static function Configure($name, Container $container, array $settings = [])
    {
        if (isset(Plugin::$Instances[$name])) {
            // already configured
            return Plugin::$Instances[$name];
        } elseif (isset(Plugin::$Added[$name])) {
            $class = Plugin::$Added[$name];

            $plugin = new $class($container);
            if ($plugin instanceof Plugin) {
                $plugin->name = $name;
            } else {
                throw new \InvalidArgumentException('Missed "'.$name.'" plugin');
            }
            $plugin->config($settings);

            Plugin::$Instances[$name] = $plugin;
        }
        else throw new \BadFunctionCallException('Missed "'.$name.'" plugin');

        return $plugin;
    }

    /**
     * @param $name
     * @param callable $fallback
     * @return Plugin|null
     * @throws \BadFunctionCallException
     * @throws \InvalidArgumentException
     */
    public static function Configured($name, callable $fallback = null)
    {
        if (isset(Plugin::$Instances[$name])) {
            return Plugin::$Instances[$name];
        } elseif (is_callable($fallback)) {
            $fallback();
        }

        return null;
    }

    public static function IsConfigured($name)
    {
        return (isset(Plugin::$Instances[$name]));
    }

    public static function GetAddedList()
    {
        return array_keys(Plugin::$Added);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}

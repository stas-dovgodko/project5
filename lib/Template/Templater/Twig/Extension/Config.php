<?php
namespace project5\Template\Templater\Twig\Extension;

use project5\DI\Container;
use project5\Template\Templater\Twig\IExtension;
use Twig_Environment;

class Config implements IExtension
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function extendTwig(Twig_Environment $twig, array $options = [])
    {
        $twig->addFunction(new \Twig_SimpleFunction('service', function ($name, $params = []) {

            $service = $this->container->get($name);

            return $service;
        }));

        $twig->addFunction(new \Twig_SimpleFunction('param', function ($name, $params = []) {

            return $this->container->getParameter($name);
        }));
    }
}

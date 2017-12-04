<?php
namespace project5\Template\Templater\Twig\Extension;

use project5\Template\Templater\Twig\IExtension;
use Twig_Environment;
use project5\Web\Router;

class Web implements IExtension
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @param Router $router
     */
    public function __construct(Router $router = null)
    {
        $this->router = $router;
    }

    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    public function extendTwig(Twig_Environment $twig, array $options = [])
    {
        $twig->addFunction(new \Twig_SimpleFunction('url', function ($name, $params = []) {

            try {
                if (($pos = strpos($name, '?')) !== false) {
                    parse_str(substr($name, $pos + 1), $url_params);
                    $params = array_merge($url_params, $params);
                    $name = substr($name, 0, $pos);
                }

                return $this->router->uri($this->router->getRoute($name), $params);
            } catch (\OutOfBoundsException $e) {
                return null;
            }
        }));

    }
}

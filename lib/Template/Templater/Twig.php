<?php
namespace project5\Template\Templater;


use project5\Template\ITemplater;
use project5\Template\Render;
use project5\Template\Templater\Twig\IExtension;
use Twig_Environment;
use Twig_ExtensionInterface;

/**
 * XHTML templating support
 *
 */
class Twig implements ITemplater
{
    protected $environment;
    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @var string|null
     */
    private $cache = null;

    /**
     * @var \Twig_LoaderInterface|null
     */
    protected $loader;

    public function __construct(Twig_Environment $environment, $outputMode = 'html5')
    {
        $this->environment = $environment;
        $this->environment->setBaseTemplateClass(Twig\Template::class);
    }

    public function setLoader(\Twig_LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Get configured Twig ENV
     *
     * @return Twig_Environment
     */
    public function getEnvironment()
    {
        if ($cache = $this->getCache()) {
            $this->environment->setCache(new \Twig_Cache_Filesystem($cache));
        }
        if ($this->isDebug()) {
            $this->environment->enableDebug();
        } else {
            $this->environment->disableDebug();
        }

        if ($this->loader) {
            $this->environment->setLoader($this->loader);
        }


        return $this->environment;
    }

    /**
     * @return null|string
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param null|string $cache
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
        $this->environment->setCache(new \Twig_Cache_Filesystem($cache));
    }

    /**
     * @return boolean
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * @param boolean $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
        if ($debug) {
            $this->environment->enableDebug();

        } else {
            $this->environment->disableDebug();
        }
    }


    public function addExtension($extension, $options = [])
    {
        if ($extension instanceof IExtension) {
            /** @var $extension IExtension */
            $extension->extendTwig($this->environment, $options);
        } elseif ($extension instanceof Twig_ExtensionInterface) {
            $this->environment->addExtension($extension);
        }
    }

    public function render(Render $render, $content, $name, array $arguments = [])
    {
        
        $static_loader = new Twig\ArrayLoader([
            $name => $content
        ]);

        $fs_loader = new Twig\Loader($render);


        /*if ($cache = $this->getCache()) {
            $this->environment->setCache(new \Twig_Cache_Filesystem($cache));
        }*/
        $env = $this->environment;
        if ($this->isDebug() && !$this->environment->isAutoReload()) {
            $env->enableAutoReload();
        } else {
            $env->disableDebug();
        }


        
        $env->setLoader(new \Twig_Loader_Chain([$static_loader, $fs_loader]));

        $variables['this'] = $this;

        return $env->render($name, $arguments);
    }


}

<?php
namespace project5\Template\Templater\Twig;

use project5\Stream\IStreamable;
use project5\Stream\String;
use project5\Template\ILoader;
use project5\Template\Render;
use Twig_Error_Loader;

class Loader implements \Twig_LoaderInterface
{
    protected $render;

    /**
     * @var ILoader[]
     */
    protected $loaders = [];

    public function __construct(Render $render)
    {
        $this->render = $render;
    }

    /**
     * @param $name
     * @return [string, string]
     */
    protected function splitName($name)
    {
        if (isset($name[0]) && '@' == $name[0]) {
            if (false === $pos = strpos($name, '/')) {
                throw new \Twig_Error_Loader(sprintf('Malformed namespaced template name "%s" (expecting "@namespace/template_name").', $name));
            }

            $namespace = substr($name, 1, $pos - 1);
            $shortname = substr($name, $pos + 1);


            return [$namespace, $shortname];
        }

        if ($namespace = $this->render->getCurrentNamespace()) {
            return [$namespace, $name];
        }

        return [\Twig_Loader_Filesystem::MAIN_NAMESPACE, $name];
    }

    /**
     * Gets the source code of a template, given its name.
     *
     * @param string $name The name of the template to load
     *
     * @return string The template source code
     *
     * @throws Twig_Error_Loader When $name is not found
     */
    public function getSource($name)
    {
        list($namespace, $name) = $this->splitName($name);

        try {
            $return = $this->render->getTemplateLoader($namespace)->getSource($name)->getContents();
            
            return $return;
        } catch (\Exception $e) {
            throw new \Twig_Error_Loader($e->getMessage());
        }

    }

    /**
     * Gets the cache key to use for the cache for a given template name.
     *
     * @param string $name The name of the template to load
     *
     * @return string The cache key
     *
     * @throws Twig_Error_Loader When $name is not found
     */
    public function getCacheKey($name)
    {
        list($namespace, $name) = $this->splitName($name);

        $loader = $this->render->getTemplateLoader($namespace);
        $render = $this->render->getTemplateRender($namespace);

        return $namespace.'_'.md5(serialize($loader)).'_'.get_class($render).'_'.$name;
    }

    /**
     * Returns true if the template is still fresh.
     *
     * @param string $name The template name
     * @param int $time Timestamp of the last modification time of the
     *                     cached template
     *
     * @return bool true if the template is fresh, false otherwise
     *
     * @throws Twig_Error_Loader When $name is not found
     */
    public function isFresh($name, $time)
    {
        list($namespace, $name) = $this->splitName($name);

        $loader = $this->render->getTemplateLoader($namespace);

        return !$loader->isModifiedSince($name, $time);
    }
}
<?php
namespace project5\Template;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

/**
 * XHTML templating support
 *
 */
class Render implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private $renders = [];
    private $render;

    private $namespacesStack = [];

    public function __construct(ITemplater $render, ILoader $loader)
    {
        $this->render = [$render, $loader];
        $this->renders = [];
    }

    public function setTemplateRender($namespace, ITemplater $template, ILoader $loader)
    {
        $this->renders[$namespace] = [$template, $loader];

        return $this;
    }

    /**
     * @return array namespace, ITemplate, ILoader
     */
    public function getRenders()
    {
        $list = [['', $this->render[0], $this->render[1]]];
        foreach($this->renders as $namespace => $renders) {
            $list[] = [$namespace, $renders[0], $renders[1]];
        }

        return $list;
    }

    /**
     * @param $namespace
     * @return ITemplater
     */
    public function getTemplateRender($namespace)
    {
        if (isset($this->renders[$namespace])) {
            list($template, $loader) = $this->renders[$namespace];
        } else {
            list($template, $loader) = $this->render;

            $this->renders[$namespace] = [$template, $loader];
        }

        return $template;
    }

    /**
     * @param $namespace
     *
     * @return ILoader
     */
    public function getTemplateLoader($namespace)
    {
        if (isset($this->renders[$namespace])) {
            list($template, $loader) = $this->renders[$namespace];
        } else {
            list($template, $loader) = $this->render;

            $this->renders[$namespace] = [ $template, $loader];
        }

        return $loader;
    }

    /**
     * @return mixed|null
     */
    public function getCurrentNamespace()
    {
        if (!empty($this->namespacesStack)) {
            return $this->namespacesStack[count($this->namespacesStack) - 1];
        } else {
            return null;
        }
    }

    
    public function render($namespace, $templateName, array $arguments = [])
    {
        array_push($this->namespacesStack, $namespace);

        if (isset($this->renders[$namespace])) {
            list($templater, $loader) = $this->renders[$namespace];
        } else {
            list($templater, $loader) = $this->render;
        }
        /** @var $templater ITemplater */
        /** @var $loader ILoader */
        if (!is_array($templateName)) {
            $template_names = [(string)$templateName];
        } else $template_names = $templateName;

        foreach($template_names as $name) {
            try {
                $source = $loader->getSource($name);

                if ($this->logger) {
                    $this->logger->debug('Template loader "'.get_class($loader).'" found "'.$name.'" template. Loaded');
                }
            } catch (Loader\Exception\NotFoundException $e) {

                if ($this->logger) {
                    $this->logger->notice('Template loader "'.get_class($loader).'" does not found "'.$name.'" template. Skipped');
                }
                continue;
            }


            $fullname = ($namespace ? $namespace.':' : '').$name;

            $return = $templater->render($this, $source->getContents(), $fullname, $arguments);

            if ($this->logger) {
                $this->logger->debug('Template renderer "'.get_class($templater).'" render "'.$name.'" with '.mb_strlen($return, '8bit').' bytes');
            }

            array_pop($this->namespacesStack);

            return $return;
        }

        if (isset($e)) throw $e;

    }
}
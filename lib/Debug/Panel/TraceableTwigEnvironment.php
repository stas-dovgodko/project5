<?php
/*
* This file is part of the DebugBar package.
*
* (c) 2013 Maxime Bouroumeau-Fuseau
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace project5\Debug\Panel;

use DebugBar\DataCollector\TimeDataCollector;
use Twig_CompilerInterface;
use Twig_Environment;
use Twig_ExtensionInterface;
use Twig_LexerInterface;
use Twig_LoaderInterface;
use Twig_NodeInterface;
use Twig_NodeVisitorInterface;
use Twig_ParserInterface;
use Twig_TokenParserInterface;
use Twig_TokenStream;

/**
 * Wrapped a Twig Environment to provide profiling features
 */
class TraceableTwigEnvironment extends Twig_Environment
{
    protected $twig;

    protected $renderedTemplates = array();

    protected $timeDataCollector;

    /**
     * @param Twig_Environment $twig
     * @param TimeDataCollector $timeDataCollector
     */
    public function __construct(Twig_LoaderInterface $loader = null, $options = array())
    {
        parent::__construct($loader, $options);



    }

    public function getRenderedTemplates()
    {
        return $this->renderedTemplates;
    }

    public function addRenderedTemplate(array $info)
    {
        $this->renderedTemplates[] = $info;
    }

    public function setTimeDataCollector(TimeDataCollector $collector)
    {
        $this->timeDataCollector = $collector;
    }

    public function getTimeDataCollector()
    {
        return $this->timeDataCollector;
    }

    public function compileSource($source, $name = null)
    {
        $start = microtime(true);
        $return = parent::compileSource($source, $name);
        $end = microtime(true);

        if ($this->timeDataCollector) {
            if ($name) $name = sprintf("twig.compile(%s)", $name);
            elseif ($source instanceof \Twig_Source) {
                $name = sprintf("twig.compile(%s)", $source->getName());
            } else {
                $name = sprintf("twig.compile(%s)", $source);
            }

            $this->timeDataCollector->addMeasure($name, $start, $end);
        }

        return $return;
    }

    public function loadTemplate($name, $index = null)
    {
        $this->setBaseTemplateClass(TraceableTwigTemplate::class);

        return parent::loadTemplate($name, $index);
    }

}

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

use project5\Template\Templater\Twig\Template;

/**
 * Wraps a Twig_Template to add profiling features
 */
abstract class TraceableTwigTemplate extends Template
{
    public function display(array $context, array $blocks = array())
    {
        $start = microtime(true);
        parent::display($context, $blocks);
        $end = microtime(true);

        if ($this->env instanceof TraceableTwigEnvironment) {
            if ($timeDataCollector = $this->env->getTimeDataCollector()) {
                $name = sprintf("twig.render(%s)", $this->getTemplateName());
                $timeDataCollector->addMeasure($name, $start, $end);
            }

            $this->env->addRenderedTemplate(array(
                'name' => $this->getTemplateName(),
                'render_time' => $end - $start
            ));
        }
    }

    protected function loadTemplate($template, $templateName = null, $line = null, $index = null)
    {
        $start = microtime(true);
        $return =parent::loadTemplate($template, $templateName, $line, $index);
        $end = microtime(true);

        if (($this->env instanceof TraceableTwigEnvironment) && $timeDataCollector = $this->env->getTimeDataCollector()) {
            $name = sprintf("twig.load(%s)", $this->getTemplateName());
            $timeDataCollector->addMeasure($name, $start, $end);
        }

        return $return;
    }
}

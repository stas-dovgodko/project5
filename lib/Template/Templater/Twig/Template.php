<?php
namespace project5\Template\Templater\Twig;

use Twig_Template;

abstract class Template extends Twig_Template
{
    protected function resolveTemplateNames($template, $baseName)
    {
        if (is_string($template)) {
            if (isset($template[0]) && '@' == $template[0]) {
                // namespaced
                return $template;
            } else if (isset($baseName[0]) && '@' == $baseName[0]) {
                if (false === $pos = strpos($baseName, '/')) {
                    throw new \Twig_Error_Loader(sprintf('Malformed namespaced template name "%s" (expecting "@namespace/template_name").', $baseName));
                }

                $namespace = substr($baseName, 1, $pos - 1);

                return '@'.$namespace . '/' . $template;
            } else {
                return $template; // parent related too
            }
        } elseif ($template instanceof Twig_Template) {
            return $template;
        } elseif (is_array($template)) {
            $list = [];

            foreach ($template as $subtemplate) {
                $list[] = $this->resolveTemplateNames($subtemplate, $baseName);
            }

            return $list;
        } else {
            throw new \Twig_Error_Loader('Can\'t resolve '.$template.' template name');

        }
    }

    protected function loadTemplate($template, $templateName = null, $line = null, $index = null)
    {
        if ($templateName) {
            $template = $this->resolveTemplateNames($template, $templateName);
        }
        return parent::loadTemplate($template, $templateName, $line, $index);
    }
}
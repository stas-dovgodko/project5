<?php
namespace project5\Template\Templater\Twig\Extension;

use project5\Template\Templater\Twig\IExtension;
use Twig_Environment;

class Debug implements IExtension
{
    public function __construct()
    {

    }

    public function extendTwig(Twig_Environment $twig, array $options = [])
    {
        $twig->addFilter('dump', new \Twig_SimpleFilter('dump', function () {

            return array_reduce(func_get_args(), function ($output, $v) {
                $output .= '<pre>';

                $output .= var_export($v, true) . '</pre><br />';

                return $output;
            }, 'Dump: ');
        }));
    }
}

<?php
namespace project5\Template\Templater\Twig;

use Twig_Environment;

interface IExtension
{
        public function extendTwig(Twig_Environment $twig, array $options = []);
}

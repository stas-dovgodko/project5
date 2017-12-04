<?php
namespace project5\Nls;

use project5\Plugin as Project5Plugin;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class Plugin extends Project5Plugin
{
    /**
     * Init instance
     */
    protected function init()
    {
        $this->container->configure(__DIR__ . '/../config/nls.yml');

        /*$this->container->addTagsHandler('i18n', 'nls.i18n.translator', function(Definition $manager, Reference $translator, $options) {
            $manager->addMethodCall('addTranslator', [$translator, $options]);
        });*/
    }




    public function getSupportedLocales()
    {
        return (array)$this->container->getParameter('nls.locale.supported');
    }

    public function getDefaultLocale()
    {
        return $this->container->getParameter('nls.locale.default');
    }
}
<?php
namespace project5\Nls\I18n;

use project5\DI\Container;
use project5\DI\IContainer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class Translator implements IContainer
{
    private $_storages;

    public static function Setup(Container $container, $id)
    {

    }

    public static function Inject(Definition $definition, $config, ContainerBuilder $builder, $setupOptions = null)
    {

    }

    public function addBucket(IBucket $bucket, $domainOnly = null, $localeOnly = null)
    {
        if ($this->_storages === null) {
            $this->_storages = new \SplObjectStorage();
        }
        $this->_storages->attach($bucket, [
            'domain' => $domainOnly,
            'locale' => $localeOnly,
        ]);
    }

    /**
     * @param $locale
     * @param $key
     * @param null $domain
     * @return string|null
     */
    public function getTranslation($locale, $key, $domain = null)
    {
        if ($this->_storages) foreach($this->_storages as $bucket) { /** @var $bucket IBucket */
            $info = $this->_storages->getInfo();
            if (empty($info['locale']) || $info['locale'] === $locale) {
                if ($info['domain'] === $domain) {
                    if ($bucket->hasTranslation($locale, $key)) {
                        return $bucket->getTranslation($locale, $key, null);
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param $locale
     * @param $key
     * @param $number
     * @param null $domain
     * @return string|null Null if not specified
     */
    public function getPlural($locale, $key, $pluralKey, $number, $domain = null)
    {
        if ($this->_storages) foreach($this->_storages as $bucket) { /** @var $bucket IBucket */
            $info = $this->_storages->getInfo();
            if (empty($info['locale']) || $info['locale'] === $locale) {
                if ($info['domain'] === $domain) {
                    if ($bucket->hasTranslation($locale, $key)) {
                        return $bucket->getTranslation($locale, $key, $number);
                    }
                }
            }
        }

        return null;
    }
}
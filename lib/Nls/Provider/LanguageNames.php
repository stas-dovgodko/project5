<?php
namespace project5\Nls\Provider;

use project5\Provider\Field\String;
use project5\Provider\Map;
use Symfony\Component\Intl\Intl;

class LanguageNames extends Map
{
    public function __construct(array $locales)
    {
        $t1 = microtime(true);
        $map = ['title' => new String('title')];
        foreach($locales as $locale) {
            $map[$locale] = new String($locale);
        }

        $bundle = Intl::getLanguageBundle();

        $list = [];
        foreach($locales as $locale) {

            $int_locale_data = locale_parse($locale);

            foreach($bundle->getLanguageNames($locale) as $code => $name) {
                $list[$code][$locale] = $name;

                if (isset($int_locale_data['language']) && $code === $int_locale_data['language']) {
                    $list[$locale]['title'] = $name;
                }
            }
        }

        $t2 = microtime(true);

        //echo ($t2-$t1)."<br>";

        parent::__construct(
            $list,
            function($key, $data) {
                return $key;
            },
            $map
        );
    }

    /**
     * @param $uid
     * @return Entity
     */
    public function findByUid($uid)
    {
        $t1 = microtime(true);
        if (isset($this->data[$uid])) {
            $entity = $this->createEntity();
            $entity->setUid($uid);

            foreach($this->fields as $property_name => $field) {
                /** @var $field IField */
                if (is_string($property_name)) {
                    if (isset($this->data[$uid][$property_name])) {
                        $field->set($entity, $this->data[$uid][$property_name]);
                    }
                }
            }

        } else {
            $entity = parent::findByUid($uid);
        }



        $t2 = microtime(true);

        //echo 'uid:'.($t2-$t1)."<br>";

        return $entity;
    }
}
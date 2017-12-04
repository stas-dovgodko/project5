<?php
namespace project5\Nls\Provider;

use project5\Provider\Field\String;
use project5\Provider\Map;
use Symfony\Component\Intl\Intl;

class CountryNames extends Map
{
    protected $_limit = 1000;
    public function __construct(array $locales)
    {
        $map = [];
        foreach($locales as $locale) {
            $map[$locale] = new String($locale);
        }

        $list = [];


        $bundle = Intl::getRegionBundle();

        foreach ($locales as $locale) {
            foreach ($bundle->getCountryNames($locale) as $code => $name) {
                $list[$code][$locale] = $name;
            }
        }


        parent::__construct(
            $list,
            function($key, $data) {
                return $key;
            },
            $map
        );
    }
}
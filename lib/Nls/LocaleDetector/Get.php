<?php
namespace project5\Nls\LocaleDetector;

use project5\Nls\ILocaleDetector;
use project5\Web\Request as HttpRequest;

class Get implements ILocaleDetector
{
    const PARAM_NAME = 'locale';

    /**
     * @var HttpRequest
     */
    private $request;

    public function __construct(HttpRequest $request)
    {
        $this->request = $request;
    }

    /**
     * @param $locale
     * @return \StasDovgodko\Uri\Url
     */
    public function getUrl($locale)
    {
        $url = $this->request->getUrl(true);
        return $url->withQueryValue(self::PARAM_NAME, $locale);
    }

    /**
     * @return string[]|null
     */
    function determine()
    {
        $params = $this->request->getQueryParams();

        if (isset($params[self::PARAM_NAME])) {
            return [$params[self::PARAM_NAME]];
        }
        return null;
    }
}
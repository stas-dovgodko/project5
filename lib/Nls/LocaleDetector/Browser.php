<?php
namespace project5\Nls\LocaleDetector;

use project5\Nls\ILocaleDetector;
use project5\Web\Request as HttpRequest;

class Browser implements ILocaleDetector
{
    /**
     * @var HttpRequest
     */
    private $request;

    public function __construct(HttpRequest $request)
    {
        $this->request = $request;
    }

    /**
     * @return string[]|null
     */
    function determine()
    {
        if ($accept_language = $this->request->getHeader(HttpRequest::HEADER_ACCEPT_LANGUAGE)) {
            if (preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $accept_language, $lang_parse) && count($lang_parse[1])) {
                $langs = array_combine($lang_parse[1], $lang_parse[4]);
                foreach ($langs as $lang => $val) {
                    $langs[$lang] = ($val === '') ? 1 : $val;
                }
                arsort($langs, SORT_NUMERIC);

                return array_keys($langs);
            }
        }
        return null;
    }
}
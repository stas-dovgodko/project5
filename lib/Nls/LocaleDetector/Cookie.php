<?php
namespace project5\Nls\LocaleDetector;

use project5\Nls\ILocaleDetector;
use project5\Web\Request as HttpRequest;

class Cookie implements ILocaleDetector
{
    /**
     * @var HttpRequest
     */
    protected $request;
    protected $name;

    public function __construct(HttpRequest $request, $name)
    {
        $this->request = $request;
        $this->name = $name;
    }

    /**
     * @return string[]|null
     */
    public function determine()
    {
        $value = $this->request->getCookie($this->name, null);

        if ($value !== null) {
            return [$value];
        }
        return null;
    }
}
<?php
namespace project5\Web\Route;

use project5\Stream\IStreamable;
use project5\Web\Request;
use project5\Web\Response;
use project5\Web\Route;
use project5\Stream\File;

/**
 * Static file controller route
 *
 */
class Stream extends Route
{
    /**
     * @var IStreamable
     */
    private $_steamable;

    private $_contentType;

    public function __construct(IStreamable $steamable, $contentType = null)
    {
        $this->_steamable = $steamable;
        $this->_contentType = $contentType;
    }

    public static function ForFile($filename, $contentType = null)
    {
        return new Stream(new File($filename), $contentType);
    }

    /**
     * @TODO should be refactored to conditional fetch
     *
     * @param Request $request
     * @param Response $response
     * @param array $arguments
     * @return Response
     */
    protected function dispatchRoute(Request $request, Response $response, array $arguments = [])
    {

        $response = $response->withBody($this->_steamable);

        if ($this->_contentType) {
            $response = $response->withHeader(Response::HEADER_CONTENT_TYPE, $this->_contentType);
        }

        return $response;

    }
}

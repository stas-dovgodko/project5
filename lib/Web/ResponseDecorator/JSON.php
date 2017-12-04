<?php
namespace project5\Web\ResponseDecorator;


use project5\Web\IResponseDecorator;
use project5\Web\Response;
use project5\Stream\String as StringStream;

class JSON implements IResponseDecorator
{
    /**
     * @var callable|null
     */
    private $filter;

    public function __construct(callable $filter = null)
    {
        $this->filter = $filter;
    }
    /**
     * @param Response $response
     * @param array $arguments
     * @param mixed $return
     * @return Response
     */
    public function decorateResponse(Response $response, array $arguments, $return)
    {
        return $response
            ->withBody(new StringStream(
                json_encode($this->filter ? call_user_func($this->filter, $return) : $return, JSON_UNESCAPED_UNICODE)
            ))
            ->withHeader(Response::HEADER_CONTENT_TYPE, Response::CONTENT_TYPE_APPLICATION_JSON);
    }
}

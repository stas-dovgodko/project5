<?php
namespace project5\Web;


interface IResponseDecorator
{
    /**
     * @param Response $response
     * @param array $arguments
     * @param mixed $return
     * @return Response
     */
    public function decorateResponse(Response $response, array $arguments, $return);
}
<?php
namespace project5\Web;

interface IException {

    /**
     * Add extra staff with response
     *
     * @param Response $response
     * @param IResponseDecorator $decorator
     * @param array $arguments
     * @return Response
     */
    public function decorateResponse(Response $response, IResponseDecorator $decorator, array $arguments = []);
}
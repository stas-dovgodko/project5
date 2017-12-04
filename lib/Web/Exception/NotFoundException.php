<?php
namespace project5\Web\Exception;

use project5\Stream\String;
use project5\Web\IException;
use project5\Web\IResponseDecorator;
use project5\Web\Response;
use project5\Web\ResponseDecorator\HTML;

class NotFoundException extends \Exception implements IException
{
    use HttpException;

    protected function getStatus()
    {
        return Response::STATUS_NOT_FOUND;
    }


}
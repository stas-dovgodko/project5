<?php
namespace project5\Web\Exception;


use project5\Web\IException;
use project5\Web\Response;

class NotAllowedException extends \Exception implements IException
{
    use HttpException;

    protected function getStatus()
    {
        return Response::STATUS_FORBIDDEN;
    }
}
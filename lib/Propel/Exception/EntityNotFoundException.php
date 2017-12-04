<?php
namespace project5\Propel\Exception;

use project5\Web\Exception\HttpException;
use project5\Web\IException;
use project5\Web\Response;
use Propel\Runtime\Exception\EntityNotFoundException as SuperException;

class EntityNotFoundException extends SuperException implements IException {
    use HttpException;

    protected function getStatus()
    {
        return Response::STATUS_NOT_FOUND;
    }
}
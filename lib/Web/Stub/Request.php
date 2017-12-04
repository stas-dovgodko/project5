<?php
namespace project5\Web\Stub;

use project5\Web\Request as SuperRequest;
use project5\Web\Url;

class Request extends SuperRequest
{
    public function __construct() {
        parent::__construct(new Url('/'));
    }
}
<?php
namespace project5\Web\Stub;

use project5\Web\Request as SuperRequest;
use StasDovgodko\Uri\Url;

class Request extends SuperRequest
{
    public function __construct() {
        parent::__construct(new Url('/'));
    }
}
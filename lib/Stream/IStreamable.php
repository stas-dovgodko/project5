<?php
namespace project5\Stream;

use Psr\Http\Message\StreamInterface;

interface IStreamable extends StreamInterface
{
    /**
     * @return string
     */
    public function hashCode();
}
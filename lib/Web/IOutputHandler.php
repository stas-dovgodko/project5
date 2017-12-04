<?php
namespace project5\Web;


use Psr\Http\Message\StreamInterface;

interface IOutputHandler
{
    /**
     * @param string $html
     * @return Response|null
     */
    public function handleHtml(&$html, Response $response);

    /**
     * @param mixed $json
     * @return Response|null
     */
    public function handleJson(&$json, Response $response);

    /**
     * @param StreamInterface $stream
     * @return Response|null
     */
    public function handleBinary(StreamInterface $stream, Response $response);
}
<?php
namespace project5\Web\ResponseDecorator;


use project5\File;
use project5\Stream\Proxy;
use project5\Stream\String;
use project5\Template\ILoader;
use project5\Template\ITemplater;
use project5\Web\IResponseDecorator;
use project5\Web\Response;
use project5\Stream\String as StringStream;

use project5\Xml\Render;
use project5\Xml\Writer as XmlWriter;

class XML implements IResponseDecorator
{
    /**
     * @var ITemplate
     */
    private $render;

    /**
     * @var ILoader
     */
    private $loader;

    private $templateName, $templateNamespace;

    public function __construct(ITemplater $render, ILoader $loader)
    {
        $this->render = $render;
        $this->loader = $loader;
    }

    public function setTemplateName($name, $namespace = null)
    {
        $this->templateName = $name;
        $this->templateNamespace = $namespace;
    }



    /**
     * @param Response $response
     * @param array $arguments
     * @param mixed $return
     * @return Response
     */
    public function decorateResponse(Response $response, array $arguments, $return)
    {
        if ($return instanceof Render) {

            $render = $return;

            $output_stream = $response->getBody();
            if ($output_stream === null) {
                $output_stream = new StringStream();

                $response = $response->withBody($output_stream);
                //$output_stream = $response->getBody();
            }


            $writer = new \XMLWriter();

            $writer->openUri(Proxy::Wrap($output_stream));
            $writer->setIndent(true);
            $writer->startDocument();

            $template = $this->loader->getSource($this->templateName);

            $reader = new \XMLReader();
            $reader->open(($template instanceof File) ? $template->getFilename() : Proxy::Wrap($template));

            $render->render($reader, $writer);


            $writer->endDocument();
            $writer->flush(true);


            return $response->withBody(new String($output_stream->getContents()))->withHeader(Response::HEADER_CONTENT_TYPE, Response::CONTENT_TYPE_XML);
        } elseif (is_string($return)) {
            // just parse & render
            return $response->withBody(new String($return))->withHeader(Response::HEADER_CONTENT_TYPE, Response::CONTENT_TYPE_XML);
        } elseif (is_array($return)) {
            // render with raw render
            $render = new \project5\Template\Render($this->render, $this->loader);
            $content = $render->render($this->templateNamespace, $this->templateName, array_merge($arguments, $return));

            return $response
                ->withBody(new StringStream($content))
                ->withHeader(Response::HEADER_CONTENT_TYPE, Response::CONTENT_TYPE_XML);
        }
    }
}

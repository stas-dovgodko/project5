<?php
namespace project5\Web\ResponseDecorator;

use project5\DI\Container;
use project5\ITemplate;
use project5\Web\IResponseDecorator;
use project5\Web\Request;
use project5\Web\Response;

use project5\Template\Render;
use project5\Stream\String as StringStream;

use project5\Template as TemplateRender;

class HTML implements IResponseDecorator
{


    private $templateName = 'empty';
    private $templateNamespace = '';

    /**
     * @var ITemplate
     */
    private $template;

    public function __construct(Render $template)
    {
        $this->template = $template;
    }

    public function setRender(Render $template)
    {
        $this->template = $template;
    }

    public function setTemplateName($name, $namespace = null)
    {
        $this->templateName = $name;
        $this->templateNamespace = $namespace;
    }

    /**
     * @param Response $response
     * @param array $arguments
     * @param array $return
     * @return mixed
     */
    public function decorateResponse(Response $response, array $arguments, $return)
    {

        $template = $this->template;
        //$template->addTemplatesPath($this->templatePath);

        $return['request'] = $response->getRequest();
        $return['response'] = $response;

        $content = $template->render($this->templateNamespace, $this->templateName, array_merge($arguments, $return));

        return $response
            ->withBody(new StringStream($content))
            ->withHeader(Response::HEADER_CONTENT_TYPE, Response::CONTENT_TYPE_HTML);

    }
}

<?php
namespace project5\Web\Exception;

use project5\Web\IResponseDecorator;
use project5\Web\Response;
use project5\Web\ResponseDecorator\HTML;

trait HttpException // implements IException
{
    protected $httpError = 404;

    /**
     * Add extra staff with response
     *
     * @param Response $response
     * @param IResponseDecorator $decorator
     * @param array $arguments
     * @return Response
     */
    public function decorateResponse(Response $response, IResponseDecorator $decorator, array $arguments = [])
    {
        if ($decorator instanceof HTML) {

            list($template_name, $template_namespace) = $this->getTemplate();

            $decorator->setTemplateName($template_name, $template_namespace);
        }

        $status_info = $this->getStatus();
        if (is_array($status_info)) {
            list($code, $reason) = $status_info;
        } else {
            list($code, $reason) = [$status_info, null];
        }

        return $decorator
            ->decorateResponse($response, $arguments, [
                'exception' => $this,
                'exception-message' => $this->getMessage(),
            ])
            ->withStatus($code, $reason);
    }

    protected function getTemplate() {
        $status = $this->getStatus();
        if (is_array($status)) list($status, ) = $status;

        return [[(string)$status, '500'], 'error'];
    }

    /**
     * @return array|int
     */
    protected function getStatus()
    {
        return [Response::STATUS_SERVER_ERROR, null];
    }
}
<?php
namespace project5\Web\Response;

use project5\Web\Response;
use project5\Web\Url;

class Factory
{

    /**
     * @param Response $response
     * @param $url
     * @return Response
     */
    public static function Redirect(Response $response, $url)
    {
        $request = $response->getRequest();

        $url = is_string($url) ? new Url($url) : $url;

        $redirect_uri = $request->getBaseUrl()->resolve($url);

        if ($request->isPost() && $request->isXHR()) {
            /**
             * Add Ajax modifier
             */
            $params = $request->getQueryParams();
            $params['ajax'] = 'yes';
            $redirect_uri = $redirect_uri->withQuery(http_build_query($params));
        }

        return $response
            ->withStatus(Response::STATUS_FOUND)
            ->withHeader(Response::HEADER_LOCATION, $redirect_uri->__toString());
    }
}
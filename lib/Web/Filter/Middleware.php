<?php
namespace project5\Web\Filter;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use project5\Web\Filter;

use project5\Web\Request;
use project5\Web\Response;
use project5\Web\Route;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use project5\Stream\String;

/**
 * Middlewares support.
 * Filter can reuse any Symfony HttpKernelInterface middleware
 *
 */
class Middleware extends Filter
{
    /**
     * @var HttpKernelInterface|null
     */
    private $beforeMiddleware;

    /**
     * @var HttpKernelInterface|null
     */
    private $afterMiddleware;

    /**
     * @param HttpKernelInterface $beforeMiddleware or null
     * @param HttpKernelInterface $afterMiddleware or null
     */
    public function __construct(HttpKernelInterface $beforeMiddleware = null, HttpKernelInterface $afterMiddleware = null)
    {
        $this->beforeMiddleware = $beforeMiddleware;
        $this->afterMiddleware = $afterMiddleware;
    }

    /**
     * Pre filter method
     *
     * For process the rest of filter chain this code should be call inside:
     * <code>
     * $filterChain->doPreFilter ();
     * </code>
     *
     * @param Request $request
     * @param Response $response
     * @param Chain $filterChain
     * @return Response|null
     */
    public function doPreFilter(Request $request, Response $response, Chain $filterChain)
    {
        if ($this->beforeMiddleware !== null) {
            $symphony_response = $this->beforeMiddleware->handle($this->decorateRequest($request), HttpKernelInterface::MASTER_REQUEST, false);

            $response = $this->mergeResponse($response, $symphony_response);

            return $response;
        } else {
            return $filterChain->doPreFilter($request, $response);
        }
    }

    /**
     * Post filter method.
     * This should be redefined in post filter.
     *
     * For process the rest of filter chain this code should be call inside:
     * <code>
     * $filterChain->doPostFilter ();
     * </code>
     *
     * @param Request $request
     * @param Response $response
     * @param Chain $filterChain
     * @param bool $flag
     * @return Response|void
     */
    public function doPostFilter(Request $request, Response $response, Chain $filterChain, $flag = true)
    {
        if ($this->afterMiddleware !== null) {
            $symphony_response = $this->afterMiddleware->handle($this->decorateRequest($request), HttpKernelInterface::MASTER_REQUEST, false);

            $response = $this->mergeResponse($response, $symphony_response);

            return $response;
        } else {
            return $filterChain->doPostFilter($request, $response, $flag);
        }
    }

    /**
     * Decorate lib request to symfony request
     *
     * @param Request $request
     * @return SymfonyRequest
     */
    private function decorateRequest(Request $request)
    {
        $symfony_request = new SymfonyRequest($request->getQueryParams(), $request->getBodyParams(), $request->getAttributes(), $request->getCookies());

        return $symfony_request;
    }

    /**
     * Merge symfony response to lib response
     *
     * @param Response $response
     * @param SymfonyResponse $symfonyResponse
     * @return Response
     */
    private function mergeResponse(Response $response, SymfonyResponse $symfonyResponse)
    {
        foreach ($symfonyResponse->headers as $header => $line) {
            $response = $response->withHeader($header, $line);
        }
        $response = $response->withBody(new String($symfonyResponse->getContent()));
        return $response;
    }
}
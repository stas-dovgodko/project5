<?php
namespace project5\Web;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use project5\Web\Exception\RouteNotFoundException;


/**
 * This StackPHP middleware creates a middleware from a root router.
 * If no request is found, control is passed to the next middleware.
 *
 */
class Middleware implements HttpKernelInterface
{
    /**
     * @var HttpKernelInterface
     */
    private $next;

    /**
     * @var Router
     */
    private $router;

    /**
     *
     * @param HttpKernelInterface $next The next application the request will be forwarded to if not handled by this
     * @param Router $router Router to handle
     */
    public function __construct(HttpKernelInterface $next, Router $router)
    {
        $this->next = $next;
        $this->router = $router;
    }


    /**
     * Handles a Request to convert it to a Response.
     *
     * When $catch is true, the implementation must catch all exceptions
     * and do its best to convert them to a Response instance.
     *
     * @param SymfonyRequest $symfonyRequest A Request instance
     * @param int $type The type of the request
     *                         (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     * @param bool $catch Whether to catch exceptions or not
     *
     * @return SymfonyResponse A Response instance
     *
     * @throws \Exception When an Exception occurs during processing
     *
     * @api
     */
    public function handle(SymfonyRequest $symfonyRequest, $type = self::MASTER_REQUEST, $catch = true)
    {
        // create lib request from Symfony request
        $request = new Request(new Url($symfonyRequest->getRequestUri()), $symfonyRequest->getMethod());
        $request = $request->withBaseUrl(new Url($symfonyRequest->getBaseUrl()));
        $response = new Response($request);
        try {
            $response = $this->router->dispatch($request, $response);

            return new SymfonyResponse($response->getBody()->getContents(), $response->getStatusCode(), $response->getHeaders());

        } catch (RouteNotFoundException $e) {
            return $this->next->handle($symfonyRequest, $type, $catch);
        } catch (\Exception $e) {
            if ($catch) {
                // handle exception to response
            } else throw $e;
        }
    }
}
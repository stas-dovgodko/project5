<?php
namespace project5\Web\Filter;

use Doctrine\Common\Cache\Cache as DoctrineCache;
use project5\Stream\String;
use project5\Web\Exception\NotAllowedException;
use project5\Web\Filter;
use project5\Web\Request;
use project5\Web\Response;

class Cache extends Filter {
    /**
     * @var DoctrineCache
     */
    protected $cache;

    protected $prefix;

    protected $lifetime;
    
    //protected $incache;

    protected $cacheKey;

    public function __construct(DoctrineCache $cache, $key = '', $lifetime = 0)
    {
        $this->cache = $cache;
        $this->prefix = $key;
        $this->lifetime = $lifetime;
    }



    /**
     * Pre filter method
     *
     * For process the rest of filter chain this code should be call inside:
     * <code>
     * $filterChain->doPreFilter ();
     * </code>
     *
     * @throws NotAllowedException
     * @param Request $request
     * @param Response $response
     * @param Chain $filterChain
     * @return bool
     */
    protected function preFilter(Request $request, Response $response, Chain $filterChain)
    {
        $cache_key = $filterChain->getOption('prefix', $this->prefix) . $request->getMethod() . $request->getUrl(true);

        $names = $filterChain->getOption('request');
        if ($names && is_array($names)) {
            $cache_key .= \json_encode(array_map(function($v) {
                return (string)$v;
            }, $request->getParams($names)));
        }

        $names = $filterChain->getOption('attributes');
        if ($names && is_array($names)) {
            $route = $filterChain->getRoute();
            $cache_key .= \json_encode(array_map(function($v) {
                return (string)$v;
            }, $route->getProperties($names)));
        }

        if (is_array($incache = $this->cache->fetch($this->cacheKey = $cache_key))) {


            return $response->withBody(new String($incache['body']))->withHeaders($incache['headers'])->withHeader('X-Cache', 1);
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
        $return = $filterChain->doPostFilter($request, $response, $flag);

        $response2cache = ($return instanceof Response) ? $return : $response;

        $this->cache->save($this->cacheKey, [
            'body' => $response2cache->getBody()->getContents(),
            'headers' => $response2cache->getHeadersExcept('set-cookie')
        ], $filterChain->getOption('lifetime', $this->lifetime));

        return $response2cache->withHeader('X-Cache', 0);

    }
}
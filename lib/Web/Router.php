<?php
namespace project5\Web;

use Doctrine\Common\Cache\CacheProvider;

class Router extends Route
{
    const URI_DELIMITER = '/';
    const URL_VARIABLE_PATTERN = '\:(\(([^\)]+)\))?(\w+)';
    const DEFAULT_REGEX = '[^\/]+';

    protected $_names = [];
    protected $_routes;

    private $_initialized = false;
    private $_callback;

    private $childs = array();

    /**
     * @var CacheProvider
     */
    private $cache;

    protected function __construct(callable $callback = null)
    {
        parent::__construct();

        $this->_callback = $callback;
        $this->_routes = new \SplObjectStorage();

    }

    /**
     * @return CacheProvider
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param CacheProvider $cache
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }



    /**
     * Delayed route initilization
     *
     */
    protected function init()
    {
    }

    final protected function _initialize()
    {
        if (!$this->_initialized) {
            $this->init();



            if (($this->_callback !== null) && is_callable($this->_callback)) {


                $this->log('Initialize callback for '.get_class($this));
                if ($this->_callback instanceof \Closure) {
                    call_user_func($this->_callback->bindTo($this), $this);
                } else {
                    call_user_func($this->_callback, $this);
                }
            }


            $this->_initialized = true;
        }

        return $this;
    }

    /**
     * @param Route|string $route
     * @return bool
     */
    public function removeRoute($route)
    {
        $this->_initialize();

        $removed = false;
        if ($route instanceof Route) {
            if ($this->_routes->contains($route)) {
                $this->_routes->detach($route);
                $removed = true;
            }
        } elseif (is_string($route) && isset($this->_names[$route])) {
            $removed = $this->removeRoute($this->_names[$route]);
        }

        if ($removed) {
            $this->childs = [];
        }

        return $removed;
    }

    /**
     * Add route
     *
     * @param $pattern
     * @param Route $route
     * @param null $name
     * @param array $attributes
     * @return Route Added route fluent API support
     */
    public function addRoute($pattern, Route $route, $name = null, array $attributes = [])
    {
        if ($name && isset($this->_names[$name])) {
            throw new \OutOfBoundsException('Route with "' . $name . '" already exists');
        } elseif ($route instanceof Router) {
            $this->_routes->addAll($route->_routes);
            $route->_routes = $this->_routes;

            if ($this->cache && !$route->cache) {
                $route->cache = $this->cache;
            }
        }



        $this->_routes->attach($route, [
            'pattern' => $pattern,
            'name' => $name,
            'attributes' => $attributes,
            'router' => $this
        ]);

        if ($name) {
            $this->_names[$name] = $route;
        }

        if (!$route->logger && $this->logger) {
            $route->logger = $this->logger;
        }

        $this->childs = [];

        return $route;
    }

    /**
     * Get routing object by name. Look in overall tree
     *
     * @throws \OutOfBoundsException
     * @param string|array $nameOrNames
     * @return Route
     */
    public function getRoute($nameOrNames)
    {
        $this->_initialize();

        if (func_num_args() > 1) {
            $nameOrNames = func_get_args();
        }

        if (is_array($nameOrNames)) {
            $name = (string)array_shift($nameOrNames);
        } else {
            $name = (string)$nameOrNames;
            $nameOrNames = [];
        }

        $found_route = null;
        if (array_key_exists($name, $this->_names)) {
            $route = $this->_names[$name];
            if (empty($nameOrNames)) {
                $found_route = $route;
            } elseif ($route instanceof Router) {
                $found_route = $route->getRoute($nameOrNames);
            } else {
                throw new \OutOfBoundsException(sprintf("Route %s should be Router instance", $name));
            }
        } else {

            $normalized_name = strtolower($name);
            foreach($this->_names as $n => $candidate) {
                if (strtolower($n) === $normalized_name) {
                    if (empty($nameOrNames)) {
                        $found_route = $candidate;
                    } elseif ($candidate instanceof Router) {
                        $found_route = $candidate->getRoute($nameOrNames);
                    } else {
                        throw new \OutOfBoundsException(sprintf("Route %s should be Router instance", $name));
                    }
                    break;
                }
            }

            if (!$found_route) {
                foreach ($this->_routes as $route) {
                    if ($route instanceof Router) {
                        try {
                            $found_route = $route->getRoute([$name] + $nameOrNames);
                        } catch (\OutOfBoundsException $e) {
                            continue;
                        }
                    }
                }
            }

            if (!$found_route) {
                throw new \OutOfBoundsException(sprintf("Route %s not found in tree", $name));
            }
        }

        $this->log('Got route "'.get_class($found_route).'" for "'.$name.'" name');

        return $found_route;
    }

    /**
     * @param Route $child
     * @return null|string
     */
    public function getPattern(Route $child)
    {
        if ($this->_initialize()->_routes->contains($child)) {
            $info = $this->_routes->offsetGet($child);


            return $info['pattern'];
        }


        return null;
    }

    /**
     * @param string $pattern_candidate
     * @return bool
     */
    public function hasPattern($pattern_candidate)
    {
        foreach ($this->_initialize()->_routes as $route) {
            $info = $this->_routes->getInfo();
            if (($info['router'] === $this) && $info['pattern'] === $pattern_candidate) {
                return true;
            }
        }


        return false;
    }

    /**
     * @param Route $child
     * @return null|string
     */
    public function getName(Route $child)
    {
        if ($this->_initialize()->_routes->contains($child)) {

            $data = $this->_initialize()->_routes->offsetGet($child);
            $router = $data['router'];

            return array_search($child, $router->_names, true);
        }

        return null;
    }

    /**
     * @param Route $child
     * @return mixed[]
     */
    public function getAttributes(Route $child)
    {
        if ($this->_initialize()->_routes->contains($child)) {

            $data = $this->_routes->offsetGet($child);
            $arguments = $data['attributes'];

            return $arguments;
        }

        return [];
    }

    /**
     * Check if route is matched to pattern
     *
     * @param $pattern
     * @param $uri
     * @param array $params
     * @return bool|string
     */
    protected static function _IsMatch($pattern, $uri, &$params = array())
    {
        $url = trim((string)$uri, self::URI_DELIMITER);
        $pattern = trim($pattern, self::URI_DELIMITER);

        $variable_preg_pattern = '/^' . self::URL_VARIABLE_PATTERN . '/u';

        while ($pattern) {
            $first_char = substr($pattern, 0, 1);
            if ($first_char === ':' && preg_match($variable_preg_pattern, $pattern, $matches)) {
                list($part, , $reg, $key) = $matches;

                if ($reg) {
                    $uri_pattern = $reg . '$';
                } else {
                    $uri_pattern = self::DEFAULT_REGEX;
                }



                if (preg_match('/^' . $uri_pattern . '/ui', $url, $url_matches)) {
                    $params[$key] = ($variable_value = $url_matches[0]);
                    $url = (string)substr($url, strlen($variable_value));
                } elseif (!$url && isset($params[$key])) {
                    $url = (string)substr($url, strlen($params[$key]));
                } else {
                    return false;
                }

                $pattern = (string)substr($pattern, strlen($part));
            } elseif ($first_char === self::URI_DELIMITER) {
                $pattern = substr($pattern, 1);

                if (substr($url, 0, 1) === self::URI_DELIMITER) {
                    $url = (string)substr($url, 1);
                } elseif ($url) {
                    return false;
                }
            } else {
                $static = (($pos = strpos($pattern, self::URI_DELIMITER)) !== false) ? substr($pattern, 0,
                    $pos) : $pattern;

                if ($static === substr($url, 0, $len = strlen($static))) {
                    $pattern = (string)substr($pattern, $len);
                    $url = (string)substr($url, $len);
                } else {
                    return false;
                }
            }
        }



        return (!$pattern) ? $url : false;
    }

    /**
     * Get matched route or null
     *
     * @param Url|string $uri
     * @param array $params
     * @param Url|null $matched
     * @return Route|null
     * @throws \Exception
     */
    public function getChild($uri, &$params = array(), &$matched = null)
    {
        $key = md5((string)$uri . var_export($params, true));

        if (isset($this->_initialize()->childs[$key])) {
            list($return, $found_params, $matched) = $this->childs[$key];

            foreach ($found_params as $n => $v) {
                $params[$n] = $v;
            }

            return $return;
        }

        foreach ($this->_routes as $route) {
            $data = $this->_routes->getInfo();

            if ($data['router'] === $this) {
                /** @var $route Route */
                $match_params = array_merge($route->getDefault(), $data['attributes']);

                if (($suburl = self::_IsMatch($data['pattern'], $uri, $match_params)) !== false) {
                    $params = array_merge($params, $match_params);

                    if (($suburl === '') || ($route instanceof self)) {

                        $parsed = $this->parseRule($pattern = $data['pattern']);

                        $matched = self::BuildUriFromParsed($parsed, $pattern, $match_params, $data['attributes']);

                        $this->childs[$key] = array($route, &$params, $matched);

                        return $route;
                    }
                }
            }
        }


        $this->childs[$key] = [null, &$params, $matched];

        return null;
    }

    const TOKEN_VARIABLE = 1;
    const TOKEN_PATH = 2;
    const TOKEN_STATIC = 2;

    private function parseRule($rule)
    {

        if ($this->cache !== null) {
            $cache_key = 'router_'.md5($rule);

            $parsed = $this->cache->fetch($cache_key);
            if ($parsed !== false) {
                return $parsed;
            }
        }

        $new_parsed = array();

        $token = null; $data = null; $variable_preg_pattern = '/^' . self::URL_VARIABLE_PATTERN . '/u';
        while ($rule) {
            $first_char = substr($rule, 0, 1);

            if ($first_char === ':' && preg_match($variable_preg_pattern, $rule, $matches)) {
                list($part, , , $data) = $matches; $token = self::TOKEN_VARIABLE;

                $rule = substr($rule, strlen($part));
            } elseif ($first_char === self::URI_DELIMITER) {
                $rule = substr($rule, 1);
                $token = self::TOKEN_PATH; $data = self::URI_DELIMITER;

            } else {
                $data = (($pos = strpos($rule, self::URI_DELIMITER)) !== false) ? substr($rule, 0,
                    $pos) : $rule;

                $token = self::TOKEN_STATIC;

                $rule = substr($rule, strlen($data));
            }

            $new_parsed[] = [$token, $data];
        }

        if ($this->cache !== null && isset($cache_key)) {
            $this->cache->save($cache_key, $new_parsed);
        }

        return $new_parsed;
    }

    /**
     * @param array $parsed
     * @param $rule
     * @param array $params
     * @param array $arguments
     * @param bool|true $addMissedToQuery
     * @return Url
     * @throws \Exception
     */
    private static function BuildUriFromParsed(array $parsed, $rule, $params = [], $arguments = [], $addMissedToQuery = true)
    {

        $parts = [];
        $missed = $params;

        foreach ($parsed as list($type, $part)) {

            if ($type === 1) {
                if (isset($params[$part])) {
                    $parts[] = urlencode($params[$part]);
                    if (isset($missed[$part])) {
                        unset($missed[$part]);
                    }
                } elseif (isset($arguments[$part])) {
                    $parts[] = urlencode($arguments[$part]);
                } else {
                    throw new \Exception(sprintf('Varible "%s" not specified for url pattern "%s"', $part, $rule));
                }
            } elseif ($type === 2) {
                $parts[] = $part;
            }
        }

        $uri = new Url(implode('', $parts));

        if ((sizeof($missed) > 0) && $addMissedToQuery) {
            $uri = $uri->withQueryValues($missed);
        }

        return $uri;
    }

    public function getMatched($uri, &$params = array())
    {
        $route = $this->getChild($uri, $params, $matched);

        if ($route instanceof Router) {
            $uri_obj = ($uri instanceof Url) ? $uri : new Url($uri);
            return $route->getMatched($uri_obj->getRelated($matched), $params);
        }
        return $route;

    }

    /**
     * @return \SplObjectStorage
     */
    public function getRoutes()
    {
        return $this->_initialize()->_routes;
    }

    public function hasRoute($name)
    {
        $this->_initialize();

        $route = isset($this->_names[$name]) ? $this->_names[$name] : null;

        if (!$route) {
            $normalized_name = strtolower($name);
            foreach($this->_names as $n => $candidate) {
                if (strtolower($n) === $normalized_name) {
                    $route = $candidate;
                    break;
                }
            }
        }

        return ($route && $this->_routes->contains($route));
    }

    protected function dispatchRoute(Request $request, Response $response, array $arguments = [])
    {
        $subrequest = $request;
        foreach ($this->properties as $name => $value) {
            $subrequest = $subrequest->withAttribute($name, $value);
        }
        foreach ($arguments as $name => $value) {
            $subrequest = $subrequest->withAttribute($name, $value);
        }


        $matched = null; $params = [];
        if (($route = $this->getChild($request->getUrlPath(), $params, $matched)) && $matched) {

                $subrequest = $subrequest->withBaseUrl($matched);

                if($route_name = $this->getName($route)) {
                    $subrequest  = $subrequest->withAttribute('route_name', $route_name);
                }

                foreach ($params as $name => $value) {
                    $subrequest = $subrequest->withAttribute($name, $value);
                }


                if ($route instanceof Router) {
                    $route->_initialize();
                }

                return $route->dispatch($subrequest, $response);
        } else {

            $look_for = $request->getUrl(false);

            $patterns = array();
            foreach ($this->_initialize()->_routes as $route) {
                $info = $this->_routes->getInfo();

                if ($info['router'] === $this) {
                    $patterns[] = $info['pattern'];
                }
            }

            throw new Exception\RouteNotFoundException(sprintf('No matched route found for %s app[%s], "%s" existed',
                $look_for, get_class($this),
                implode(', ', $patterns)));
        }
    }

    public function urlByName($name, $params = [])
    {
        $route = $this->getRoute($name);

        if ($route instanceof Route) {
            return $this->uri($route, $params)->__toString();
        } else {
            return $name;
        }
    }

    public function url($uri)
    {
        $params = array();
        $route = $this->getMatched($uri, $params);

        if ($route instanceof Route) {
            $url = $this->uri($route, $params)->__toString();
            return $url;
        } else {
            return $uri;
        }
    }

    protected function find(callable $callback)
    {
        foreach ($this->_names as $route_name => $route) {
            if ($callback($route)) {
                yield $route;
            }
        }
    }


    /**
     * Build route uri
     *
     * @param Route $route
     * @param array $params
     * @param bool $addMissedToQuery
     * @return Url
     * @throws \Exception
     */
    public function uri(Route $route, $params = array(), $addMissedToQuery = true)
    {
        while ($this->_routes->contains($route)) {
            $info = $this->_routes->offsetGet($route);
            $pattern = $info['pattern'];

            $params = array_merge($route->default, (array)$params);

            $parsed = $this->parseRule($pattern);

            $route_uri = self::BuildUriFromParsed($parsed, $pattern, array_merge($route->default, (array)$params), $info['attributes'], $addMissedToQuery);



            if (isset($uri)) {
                $uri = $route_uri->resolve($uri);
            } else {
                $uri = $route_uri;
            }


            $route = $info['router'];
        }

        if (isset($uri)) {
            return $uri;
        } else {
            return new Url('/');
        }
    }

    /**
     * @return Router
     */
    public function getRoot()
    {
        $route = $this;
        while ($this->_routes->contains($route)) {
            $info = $this->_routes->offsetGet($route);
            $route = $info['router'];
        }

        return $route;
    }

    /**
     * @return Router|null
     */
    public function getRouter()
    {
        if ($this->_routes->contains($this)) {
            $info = $this->_routes->offsetGet($this);
            return $info['router'];
        }

        return null;
    }
}

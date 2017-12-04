<?php
namespace project5\Web;

use project5\Session;
use project5\Stream\File;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class Request implements ServerRequestInterface
{
    const VERSION_1_0 = 1.0;
    const VERSION_1_1 = 1.1;
    const VERSION_0_9 = 0.9;

    private $attributes = [];

    const EVENT_START_SESSION = 'start-session';
    const SESSION_NAMESPACE_PERSIST = 'persist';

    const METHOD_HEAD = 'HEAD';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    const HEADER_USER_AGENT = 'User-Agent';
    const HEADER_RANGE = 'Range';
    const HEADER_ACCEPT_ENCODING = 'Accept-Encoding';
    const HEADER_CONTENT_TYPE = 'Content-Type';
    const HEADER_ACCEPT_LANGUAGE = 'Accept-Language';
    const HEADER_IF_MODIFIED_SINCE = 'If-Modified-Since';
    const HEADER_IF_NONE_MATCH = 'If-None-Match';
    const HEADER_AUTHORIZATION = 'Authorization';
    const HEADER_HOST = 'Host';

    const HEADER_X_REQUESTED_WITH = 'X-Requested-With';

    /**
     * Request headers
     *
     * @var string[]
     */
    private $headers;

    /**
     * Request URL
     *
     * @var Url
     */
    protected $url;

    /**
     * Request method
     *
     * @var string
     */
    private $method;

    /**
     * Request version
     *
     * @var float
     */
    private $version;

    private $params;

    /**
     * @var Request\UploadedFile[]
     */
    private $files;

    /**
     * Request base URI
     *
     * @var Url
     */
    protected $baseUrl;

    private $input;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var array
     */
    protected $cookies;

    /**
     * @var array
     */
    private $parsedBody;
    /**
     * @var array
     */
    private $queryParams = [];

    private $hasSession = null;

    protected $server = [];


    /**
     * @param Url $url
     * @param string $method
     * @param array $params
     * @param float $version
     */
    public function __construct(
        Url $url,
        $method = self::METHOD_GET,
        $params = [],
        $version = self::VERSION_1_1, array $server = []
    )
    {
        $this->baseUrl = new Url('');
        $this->url = $url;
        $this->method = $method;
        $this->version = (float)$version;

        $this->headers = [];
        $this->params = $params;

        $this->cookies = [];

        $this->files = [];

        $this->input = '';
        $this->server = $server;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface|null Returns the body, or null if not set.
     */
    public function getBody()
    {
        return new File('php://input');
    }


    /**
     * @return Session|null
     */
    public function getSession()
    {
        if ($this->hasSession === null) {
            $this->hasSession = ($this->session && $this->session->isStarted());
        }
        return $this->session;
    }

    /**
     * Check if request has started session
     *
     * @return bool
     */
    public function hasSession()
    {
        if ($this->hasSession === null) {
            $this->hasSession = ($this->session && $this->session->isStarted());
        }

        if ($this->session) {
            return $this->session->isStarted();
        } else {
            return false;
        }
    }

    public function getCookie($name, $default = null)
    {
        if (isset($this->cookies[$name])) {
            return $this->cookies[$name];
        } else {
            return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
        }
    }

    public function getCookieParams()
    {
        $return = [];
        foreach ($_COOKIE as $name => $value) {
            $return[$name] = $value;
        }
        foreach ($this->cookies as $name => $value) {
            $return[$name] = $value;
        }
        return $return;
    }

    /**
     * @param Session $session
     * @return Request
     */
    public function withSession(Session $session)
    {
        $request = clone $this;
        $request->session = $session;
        $request->hasSession = true;

        return $request;
    }


    public function getUserAgent()
    {
        return $this->getHeader(self::HEADER_USER_AGENT);
    }

    public function setInput($input)
    {
        $this->input = $input;

        return $this;
    }

    public function getInput()
    {
        if (is_resource($this->input)) {
            $input = '';
            while (!feof($this->input)) {
                $input .= fgets($this->input, 4096);
            }
            fclose($this->input);

            $this->input = $input;
        }

        return $this->input;
    }

    /**
     * @param Url $base
     *
     * @return Request
     */
    public function withBaseUrl(Url $base)
    {
        $url = $this->url->getRelated($base);
        $base = $this->baseUrl->resolve($base);

        $request = clone $this;
        $request->url = $url;
        $request->baseUrl = $base;
        $request->session = &$this->session;
        $request->cookies = &$this->cookies;

        return $request;
    }



    public function isXHR()
    {
        if ($this->hasHeader(self::HEADER_X_REQUESTED_WITH)) {
            $value = $this->getHeader(self::HEADER_X_REQUESTED_WITH);

            return strtolower($value) === 'xmlhttprequest';
        } elseif ($this->hasParam('ajax') && $this->getParam('ajax')) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Get request cookie values
     *
     * @param array $names
     * @return array
     */
    public function getCookies($names = [])
    {
        if (!empty($names)) {
            $return = [];
            foreach ($names as $name) {
                $return[] = $this->getCookie($name);
            }
            return $return;
        } else {
            $return = [];
            foreach ($this->cookies as $name => $value) {
                $return[$name] = $value;
            }
            return $return;
        }
    }


    /**
     * @return Url
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Get header string or NULL if not specified
     * Name is case-insensitive
     *
     * @param string $name
     * @return null|string
     */
    public function getHeader($name)
    {
        $lines = $this->getHeaderLines($name);

        if (!empty($lines)) {
            return implode(', ', $lines);
        } else {
            return null;
        }

    }

    /**
     * Get Content-type header string or NULL if not specified
     *
     * @return null|string
     */
    public function getContentType()
    {
        return $this->getHeader(self::HEADER_CONTENT_TYPE);
    }

    /**
     * Check if header specified for request
     * Name is case-insensitive
     *
     * @param string $name
     * @return bool
     */
    public function hasHeader($name)
    {
        foreach ($this->headers as $k => $v) {
            if (strtolower($k) === strtolower($name)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string[]
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param bool $full
     * @return Url
     */
    public function getUrl($full = false)
    {
        if ($full) {
            return $this->url->__toString() ? $this->baseUrl->resolve($this->url) : $this->baseUrl;
        }
        return $this->url;
    }

    /**
     * @return string
     */
    public function getUrlPath()
    {
        return $this->url->getPath();
    }

    /**
     * @deprecated
     * @return Url
     */
    public function getFullUri()
    {
        return $this->getUrl(true);
    }

    /**
     * @return Url
     */
    public function getFullUrl()
    {
        return $this->getUrl(true);
    }

    /**
     * @return array
     */
    public function getParams()
    {
        $args = func_get_args();
        $names = array();
        foreach ($args as $arg) {
            if (is_array($arg)) {
                $names = array_merge($names, $arg);
            } else {
                if ($arg !== null) {
                    $names[] = $arg;
                }
            }
        }

        if (!empty($names)) {
            $return = [];
            foreach ($names as $name) {
                $return[] = $this->getParam($name);
            }
            return $return;
        } else {
            return $this->params;
        }
    }

    /**
     * Check if request method is POST
     *
     * @return boolean
     */
    public function isPost()
    {
        return (strtoupper($this->method) === self::METHOD_POST);
    }

    /**
     * Get requested param
     * Session and Cookie vars look too
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        } else {
            return $default;
        }
    }

    /**
     * Check requested param if exists
     * Session and Cookie vars look too
     *
     * @param string $name
     *
     * @return boolean
     */
    public function hasParam($name)
    {
        return (isset($this->params[$name]));
    }

    /**
     * Check for IF_MODIFIED_SINCE
     *
     * @param int $time
     * @return boolean True If resources should be processed again
     */
    public function isCheckModifiedSince($time)
    {
        if (isset($this->headers[self::HEADER_IF_MODIFIED_SINCE])) {
            $since = trim($this->headers[self::HEADER_IF_MODIFIED_SINCE]);

            if ($pos = strpos($since, ';')) {
                $since = substr($since, 0, $pos);
            }

            $since = strtotime($since);

            return ($time != $since);
        }

        return true;
    }

    /**
     * Check for IF_NONE_MATCH
     *
     * @param string $hash
     * @return boolean True If resources should be processed again
     */
    public function isCheckNotContain($hash)
    {
        if (isset($this->headers[self::HEADER_IF_NONE_MATCH])) {
            $inm = explode(",", $this->headers[self::HEADER_IF_NONE_MATCH]);

            foreach ($inm as $i) {
                if (trim($i) === $hash) {
                    return false;
                }
            }
        }

        return true;
    }

    public function __toString()
    {
        return (string)$this->baseUrl->resolve($this->url);
    }


    /**
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return array
     */
    public function getServerParams()
    {
        return $this->server;
    }

    /**
     * Get request client ip server tells
     * 
     * @return string|null
     */
    public function getClientIp()
    {
        if (array_key_exists('HTTP_CLIENT_IP', $this->server)) {
            return $this->server['HTTP_CLIENT_IP'];
        } elseif (array_key_exists('HTTP_X_FORWARDED_FOR', $this->server)) {
            return $this->server['HTTP_X_FORWARDED_FOR'];
        } elseif (array_key_exists('REMOTE_ADDR', $this->server)) {
            return $this->server['REMOTE_ADDR'];
        } else {
            return null;
        }
    }

    public function isSecure() {
        return
            (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off')
            || $this->server['SERVER_PORT'] == 443;
    }

    /**
     * @return Url
     */
    public function getAbsoluteBaseUrl()
    {
        $url = $this->baseUrl->withHost($this->getServerHost())->withScheme($this->isSecure() ? 'https' : 'http');

        if ($port = $this->getServerPort()) {
            $url = $url->withPort($port);
        }

        return $url;
    }

    /**
     * @return int|null
     */
    public function getServerPort()
    {
        return (array_key_exists('SERVER_PORT', $this->server)) ? $this->server['SERVER_PORT'] : null;
    }

    public function getServerHost()
    {
        $possibleHostSources = array('HTTP_X_FORWARDED_HOST', 'HTTP_HOST', 'SERVER_NAME', 'SERVER_ADDR');
        $sourceTransformations = array(
            "HTTP_X_FORWARDED_HOST" => function($value) {
                $elements = explode(',', $value);
                return trim(end($elements));
            }
        );
        $host = '';
        foreach ($possibleHostSources as $source)
        {
            if (!empty($host)) break;
            if (array_key_exists($source, $this->server)) {
                $host = $this->server[$source];
                if (array_key_exists($source, $sourceTransformations))
                {
                    $host = $sourceTransformations[$source]($host);
                }
            }

        }

        // Remove port number from host
        $host = preg_replace('/:\d+$/', '', $host);

        return trim($host);
    }

    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP's `parse_str()` would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * @return array
     */
    public function getQueryParams()
    {
        $query = $this->getUrl(true)->getQuery();
        $params = [];
        if ($query) {
            parse_str($query, $params);
        }
        return array_merge($params, $this->queryParams);
    }



    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request body can be deserialized to an array, this method MAY be
     * used to retrieve them. These MAY be injected during instantiation from
     * PHP's $_POST superglobal. The data IS NOT REQUIRED to come from $_POST,
     * but MUST be an array.
     *
     * @return array The deserialized body parameters, if any.
     */
    public function getBodyParams()
    {
        // TODO: Implement getBodyParams() method.
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return array Attributes derived from the request.
     */
    public function getAttributes()
    {
        $args = func_get_args();
        $names = array();
        foreach ($args as $arg) {
            if (is_array($arg)) {
                $names = array_merge($names, $arg);
            } else {
                if ($arg !== null) {
                    $names[] = $arg;
                }
            }
        }

        if (!empty($names)) {
            $return = [];
            foreach ($names as $name) {
                $return[] = $this->getAttribute($name);
            }
            return $return;
        } else {
            return $this->attributes;
        }
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * @see getAttributes()
     * @param string $attribute Attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($attribute, $default = null)
    {
        if (isset($this->attributes[$attribute])) {
            return $this->attributes[$attribute];
        } else {
            return $default;
        }
    }

    /**
     * Check a single derived request attribute.
     *
     * Check for exists a single derived request attribute as described in
     * getAttributes().
     *
     * @see getAttributes()
     * @param string $attribute Attribute name.
     * @return bool
     */
    public function hasAttribute($attribute)
    {
        return (isset($this->attributes[$attribute]));
    }

    public function persistParam($name, $default = null, $ns = null)
    {
        if ($ns === null) {
            $ns = md5((string)$this->getFullUri() . 'v2');
        }

        $persistent_name = $ns . ':' . $name;

        $session = $this->getSession();

        $value = $this->getParam($name, $session ? $session->get(self::SESSION_NAMESPACE_PERSIST, $persistent_name, $default) : null);

        if ($session) {
            $session->set(self::SESSION_NAMESPACE_PERSIST, $persistent_name, $value);
        }

        return $value;
    }

    /**
     * Create a new instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * changed request method.
     *
     * @param string $method Case-insensitive method.
     * @return self
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method)
    {

        $request = clone $this;
        $request->method = $method;

        return $request;
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriTargetInterface instance
     *     representing the URI of the request, if any.
     */
    public function getUri()
    {
        return $this->getUrl();
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     * @return self
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        if ($uri instanceof Url) {
            $url = clone $uri;
        } else {
            $url = new Url((string)$uri);
        }

        $request = clone $this;
        $request->baseUrl = null;
        $request->url = $url;

        return $request;
    }

    /**
     * Create a new instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new protocol version.
     *
     * @param string $version HTTP protocol version
     * @return self
     */
    public function withProtocolVersion($version)
    {
        $request = clone $this;
        $request->version = (float)$version;

        return $request;
    }

    /**
     * Gets the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion()
    {
        return sprintf('%01.1F', $this->version);
    }

    /**
     * Retrieves a header by the given case-insensitive name as an array of strings.
     *
     * @param string $header Case-insensitive header name.
     * @return string[]
     */
    public function getHeaderLines($header)
    {
        $lines = [];
        foreach ($this->headers as $name => $headers) {
            if (strtolower($name) == strtolower($header)) {
                $lines = array_merge($lines, $headers);
            }
        }
        return $lines;
    }

    /**
     * Create a new instance with the provided header, replacing any existing
     * values of any headers with the same case-insensitive name.
     *
     * The header name is case-insensitive. The header values MUST be a string
     * or an array of strings.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new and/or updated header and value.
     *
     * @param string $header Header name
     * @param string|string[] $value Header value(s).
     * @return Request
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($header, $value)
    {
        $request = clone $this;
        $keep_headers = [];
        $found = false;
        foreach ($request->headers as $name => $headers) {
            if (strtolower($name) !== strtolower($header)) {
                $keep_headers[$name] = $headers;
            } else {
                $keep_headers[$name] = is_array($value) ? $value : [(string)$value];
                $found = true;
            }
        }
        if (!$found) {
            $keep_headers[$header] = is_array($value) ? $value : [(string)$value];
        }
        $request->headers = $keep_headers;

        return $request;
    }

    /**
     * Creates a new instance, with the specified header appended with the
     * given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new header and/or value.
     *
     * @param string $header Header name to add
     * @param string|string[] $value Header value(s).
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($header, $value)
    {
        $request = clone $this;
        $keep_headers = [];
        $found = false;
        foreach ($request->headers as $name => $headers) {
            if (strtolower($name) !== strtolower($header)) {
                $keep_headers[$name] = $headers;
            } else {
                $keep_headers[$name] = array_merge($request->headers[$name], is_array($value) ? $value : [(string)$value]);
                $found = true;
            }
        }
        if (!$found) {
            $keep_headers[$header] = is_array($value) ? $value : [(string)$value];
        }
        $request->headers = $keep_headers;

        return $request;
    }

    /**
     * Creates a new instance, without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that removes
     * the named header.
     *
     * @param string $header HTTP header to remove
     * @return self
     */
    public function withoutHeader($header)
    {

    }

    /**
     * Return an instance with the specified message body.
     *
     * The body MUST be a StreamInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param StreamInterface $body Body.
     * @return self
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body)
    {

    }


    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    public function getRequestTarget()
    {
        $url = $this->getUrl(true);

        return ($path = $url->getPath()) ? $path : '/';
    }

    /**
     * Create a new instance with a specific request-target.
     *
     * If the request needs a non-origin-form request-target � e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form �
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-2.7 (for the various
     *     request-target forms allowed in request messages)
     * @param mixed $requestTarget
     * @return self
     */
    public function withRequestTarget($requestTarget)
    {
        $request = clone $this;
        $request->url = new Url($requestTarget);

        return $request;
    }

    /**
     * Create a new instance with the specified cookies.
     *
     * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
     * be compatible with the structure of $_COOKIE. Typically, this data will
     * be injected at instantiation.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated cookie values.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return Request
     */
    public function withCookieParams(array $cookies)
    {
        $request = clone $this;
        $request->cookies = array_merge_recursive($request->cookies, $cookies);

        return $request;
    }

    /**
     * Create a new instance with the specified query string arguments.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP's parse_str() would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * Setting query string arguments MUST NOT change the URL stored by the
     * request, nor the values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated query string arguments.
     *
     * @param array $query Array of query string arguments, typically from
     *     $_GET.
     * @return self
     */
    public function withQueryParams(array $query)
    {
        $request = clone $this;
        $request->queryParams = $query;

        return $request;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is application/x-www-form-urlencoded and the
     * request method is POST, this method MUST return the contents of $_POST.
     *
     * Otherwise, this method may return any results of deserializing
     * the request body content; as parsing returns structured content, the
     * potential types MUST be arrays or objects only. A null value indicates
     * the absence of body content.
     *
     * @return null|array|object The deserialized body parameters, if any.
     *     These will typically be an array or object.
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * Create a new instance with the specified body parameters.
     *
     * These MAY be injected during instantiation.
     *
     * If the request Content-Type is application/x-www-form-urlencoded and the
     * request method is POST, use this method ONLY to inject the contents of
     * $_POST.
     *
     * The data IS NOT REQUIRED to come from $_POST, but MUST be the results of
     * deserializing the request body content. Deserialization/parsing returns
     * structured data, and, as such, this method ONLY accepts arrays or objects,
     * or a null value if nothing was available to parse.
     *
     * As an example, if content negotiation determines that the request data
     * is a JSON payload, this method could be used to create a request
     * instance with the deserialized parameters.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated body parameters.
     *
     * @param null|array|object $data The deserialized body data. This will
     *     typically be in an array or object.
     * @return self
     */
    public function withParsedBody($data)
    {
        $request = clone $this;
        $request->parsedBody = $data;
        return $request;
    }

    /**
     * Create a new instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return self
     */
    public function withAttribute($name, $value)
    {
        $request = clone $this;
        $request->attributes[$name] = $value;

        return $request;
    }

    /**
     * Create a new instance that removes the specified derived request
     * attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return self
     */
    public function withoutAttribute($name)
    {
        $request = clone $this;

        if (isset($request->attributes[$name])) {
            unset($request->attributes[$name]);
        }

        return $request;
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine($name)
    {
        // TODO: Implement getHeaderLine() method.
    }

    /**
     * Retrieve normalized file upload data.
     *
     * This method returns upload metadata in a normalized tree, with each leaf
     * an instance of Psr\Http\Message\UploadedFileInterface.
     *
     * These values MAY be prepared from $_FILES or the message body during
     * instantiation, or MAY be injected via withUploadedFiles().
     *
     * @return array An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     */
    public function getUploadedFiles()
    {
        return $this->files;
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param array An array tree of UploadedFileInterface instances.
     * @return self
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $request = clone $this;
        $request->files = $uploadedFiles;

        return $request;
    }
}
<?php
namespace project5\Web;

use project5\Session;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * velocity response object
 *
 * @see velocity_request
 * @package velocity
 */
class Response implements ResponseInterface
{
    const SESSION_NAMESPACE_MESSAGES = 'messages';

    const CONTENT_TYPE_HTML = 'text/html';
    const CONTENT_TYPE_XML = 'text/xml';
    const CONTENT_TYPE_APPLICATION_JSON = 'application/json';


    const EVENT_FLUSH = 'flush';

    const STATUS_OK = 200;
    const STATUS_FOUND = 302;
    const STATUS_NOT_MODIFIED = 304;

    const STATUS_BAD_REQUEST = 400;
    const STATUS_UNAUTHORIZED = 401;
    const STATUS_FORBIDDEN = 403;
    const STATUS_NOT_FOUND = 404;
    const STATUS_SERVER_ERROR = 500;

    const HEADER_CONTENT_ENCODING = 'Content-Encoding';
    const HEADER_CONTENT_TYPE = 'Content-Type';
    const HEADER_CONTENT_LENGTH = 'Content-Length';
    const HEADER_CONTENT_LANGUAGE = 'Content-Language';
    const HEADER_LOCATION = 'Location';
    const HEADER_ETAG = 'ETag';
    const HEADER_EXPIRES = 'Expires';
    const HEADER_LAST_MOFIFIED = 'Last-Modified';
    const HEADER_CACHE_CONTROL = 'Cache-Control';
    const HEADER_PRAGMA = 'Pragma';

    /**
     * @var int
     */
    private $_status;

    /**
     * @var string
     */
    private $_statusMessage;

    private static $_StatusMessages = array(
        self::STATUS_OK => 'OK',
        self::STATUS_FOUND => 'Found',
        self::STATUS_NOT_MODIFIED => 'Not Modified',
        self::STATUS_BAD_REQUEST => 'Bad request',
        self::STATUS_UNAUTHORIZED => 'Unauthorized',
        self::STATUS_FORBIDDEN => 'Forbidden',
        self::STATUS_NOT_FOUND => 'Not Found',
        self::STATUS_SERVER_ERROR => 'Server Error'
    );

    /**
     * Context request
     *
     * @var Request
     */
    private $_request;

    private $_version;

    /**
     *
     * @var string[]
     */
    private $_headers = array();

    /**
     * @var StreamInterface
     */
    private $_stream;

    private $_cookies = array();

    private $_sessions = array();

    private $_jss = array(), $_css = array();

    /**
     * @var Session
     */
    private $_session;

    public function __construct(Request $request)
    {
        $this->_request = $request;

        // import errors and notices from request session
        //$this->_sessions = null;//$request->getSessions();

        //if (!isset($this->sessions['velocity.errors'])) $this->sessions['velocity.errors'] = $request->getSession('velocity.errors');
        //if (!isset($this->sessions['velocity.notices'])) $this->sessions['velocity.notices'] = $request->getSession('velocity.notices');

        $this->_status = self::STATUS_OK;
        $this->_statusMessage = self::$_StatusMessages[$this->_status];

        $this->_headers = array(self::HEADER_CONTENT_TYPE => [sprintf('%s; charset=utf-8', self::CONTENT_TYPE_HTML)]);

    }

    /**
     * @return Session
     */
    public function getSession()
    {
        if ($this->_session === null) {
            $this->_session = $this->_request->getSession();
        }

        return $this->_session;
    }


    /**
     * @return array
     */
    public function getCookies()
    {
        return $this->_cookies;
    }

    /**
     * @param array $cookies
     */
    public function setCookies(array $cookies)
    {
        $this->_cookies = $cookies;
    }

    /**
     * @param array $sessions
     */
    public function setSessions($sessions)
    {
        $this->_sessions = $sessions;
    }


    public function addCss($css)
    {
        $this->_css[] = $css;

        return $this;
    }

    public function addJs($js)
    {
        $this->_jss[] = $js;

        return $this;
    }


    /**
     * Set response cookie
     * @deprecated use withCookie
     * @param string $name
     * @param mixed $value
     * @param int $expire
     * @param string $path
     * @return Response
     */
    public function setCookie($name, $value, $expire = null, $path = '/')
    {
        $this->_cookies[$name] = array($value, $expire, $path);

        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @param null $expire
     * @param string $path
     * @return $this New response instance
     */
    public function withCookie($name, $value, $expire = null, $path = '/')
    {
        $response = clone $this;

        $response->_cookies[$name] = array($value, $expire, $path);

        return $response;
    }

    /**
     * Add error message to response
     *
     * @deprecated
     * @param string $error_message
     * @return Response Fluent API support
     */
    public function addError($error_message)
    {
        $this->_sessions['velocity.errors'][] = $error_message;

        return $this;
    }

    public function fetchErrors()
    {
        $errors = (isset($this->_sessions['velocity.errors'])) ? $this->_sessions['velocity.errors'] : $this->_request->getSession('velocity.errors',
            array());


        $this->_sessions['velocity.errors'] = array();

        return $errors;
    }

    public function fetchNotices()
    {
        $notices = (isset($this->_sessions['velocity.notices'])) ? $this->_sessions['velocity.notices'] : $this->_request->getSession('velocity.notices',
            array());

        $this->_sessions['velocity.notices'] = array();

        return $notices;
    }

    /**
     * Add notice message to response
     *
     * @param string $notice_message
     * @return Response Fluent API support
     */
    public function addNotice($notice_message)
    {
        $session = $this->getSession();
        $notices = $session->get(self::SESSION_NAMESPACE_MESSAGES, 'notices', []);
        $notices[] = $notice_message;
        $session->set(self::SESSION_NAMESPACE_MESSAGES, 'notices', $notices);

        return $this;
    }

    /**
     * Gets the response Status-Code.
     *
     * The Status-Code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return integer Status code.
     */
    public function getStatusCode()
    {
        return (int)$this->_status;
    }

    /**
     * Gets the response Reason-Phrase, a short textual description of the Status-Code.
     *
     * Because a Reason-Phrase is not a required element in a response
     * Status-Line, the Reason-Phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * Status-Code.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string|null Reason phrase, or null if unknown.
     */
    public function getReasonPhrase()
    {
        $code = (string)$this->getStatusCode();

        if (isset(self::$_StatusMessages[$code])) {
            return self::$_StatusMessages[$code];
        } else {
            return null;
        }
    }

    public function isSuccess()
    {
        return (($status = $this->getStatusCode()) >= 200 && $status < 300);
    }

    /**
     * Check if response is HTML
     *
     * @return boolean
     */
    public function isHtml()
    {
        $content_type = $this->getContentType();

        return (stripos($content_type, self::CONTENT_TYPE_HTML) !== false);
    }

    /**
     * Check if response is JSON encoded
     *
     * @return boolean
     */
    public function isJson()
    {
        $content_type = $this->getContentType();

        return (stripos($content_type, self::CONTENT_TYPE_APPLICATION_JSON) !== false);
    }

    public function isRedirect()
    {
        return (($status = $this->getStatusCode()) >= 300 && $status < 400);
    }

    public function isError()
    {
        return (!$this->isSuccess() && !$this->isRedirect());
    }

    /**
     * @return string[]
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * @return string[]
     */
    public function getHeadersExcept()
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

        $names = array_map('strtolower', $names);

        $headers = [];
        foreach($this->_headers as $k => $v) {
            if (!in_array(strtolower($k), $names)) {
                $headers[$k] = $v;
            }
        }

        return $headers;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Alias to get content-type header
     *
     * @return string Content type
     */
    public function getContentType()
    {
        return $this->getHeader(Response::HEADER_CONTENT_TYPE);
    }

    /**
     * Get response header
     *
     * @param string $name header name
     *
     * @return string $value header value, null if not found
     */
    public function getHeader($name)
    {
        return implode(', ', $this->getHeaderLines($name));
    }

    /**
     * Check response header
     *
     * @param string $name header name
     *
     * @return boolean
     */
    public function hasHeader($name)
    {
        return isset($this->_headers[$name]);
    }


    /**
     * Return response in raw
     *
     * @return string
     */
    function __toString()
    {
        $has_content_length = false;

        $response = sprintf("HTTP/%01.1F %s %s", $this->getProtocolVersion(), $this->getStatusCode(),
                $this->getReasonPhrase()) . "\r\n";
        foreach ($this->_headers as $name => $value) {
            $response .= sprintf("%s: %s", $name, $value) . "\r\n";

            if (!$has_content_length && (strtolower($name) === strtolower(self::HEADER_CONTENT_LENGTH))) {
                $has_content_length = true;
            }
        }
        if (!$has_content_length && $this->_stream) {
            $response .= sprintf("%s: %s", self::HEADER_CONTENT_LENGTH, $this->_stream->getSize()) . "\r\n";
        }

        if ($this->_stream) {
            try {
                $response .= "\r\n" . ($this->_stream ? $this->_stream->getContents() : null);
            } catch (\Exception $e) {
                $response .= "\r\n".'error: '.$e->getMessage();
            }
        } else {
            $response .= "\r\n";
        }



        return $response;

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
        return $this->_version ?: $this->_request->getProtocolVersion();
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface|null Returns the body, or null if not set.
     */
    public function getBody()
    {
        return $this->_stream;
    }

    /**
     * Create a new instance with the specified status code, and optionally
     * reason phrase, for the response.
     *
     * If no Reason-Phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * Status-Code.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated status and reason phrase.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param integer $code The 3-digit integer result code to set.
     * @param null|string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * @return self
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = null)
    {
        $response = clone $this;
        $response->_status = $code;
        if (!$reasonPhrase) {
            $reasonPhrase = isset(self::$_StatusMessages[$code]) ? self::$_StatusMessages[$code] : '';
        }
        $response->_statusMessage = $reasonPhrase;

        return $response;
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
        $response = clone $this;
        $response->_version = $version;

        return $response;
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
        foreach ($this->_headers as $name => $headers) {
            if (strtolower($name) == strtolower($header)) {
                $lines = $headers;
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
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeaders(array $headersMap)
    {
        $response = clone $this;

        $keep_headers = [];

        foreach($headersMap as $header => $value)
        {

            $found = false;
            foreach ($response->_headers as $name => $headers) {
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
        }
        $response->_headers = $keep_headers;

        return $response;
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
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($header, $value)
    {
        $response = clone $this;

        $keep_headers = [];
        $found = false;
        foreach ($response->_headers as $name => $headers) {
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
        $response->_headers = $keep_headers;

        return $response;
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
        $response = clone $this;

        $keep_headers = [];
        foreach ($response->_headers as $name => $headers) {
            if (strtolower($name) !== strtolower($header)) {
                $keep_headers[$name] = $headers;
            } else {
                $keep_headers[$name] = array_merge($headers, is_array($value) ? $value : [(string)$value]);
            }
        }
        $response->_headers = $keep_headers;

        return $response;
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
        $response = clone $this;

        $keep_headers = [];
        foreach ($response->_headers as $name => $headers) {
            if (strtolower($name) !== strtolower($header)) {
                $keep_headers[$name] = $headers;
            }
        }
        $response->_headers = $keep_headers;

        return $response;
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
        $response = clone $this;
        $response->_stream = $body;

        return $response;
    }

    /**
     * Create a new instance, without the specified message body.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @return self
     */
    public function withoutBody()
    {
        $response = clone $this;
        $response->_stream = null;

        return $response;
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
}

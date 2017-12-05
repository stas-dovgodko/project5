<?php
/**
 * Created by PhpStorm.
 * User: Стас
 * Date: 01.01.15
 * Time: 15:16
 */
namespace project5\Web;


use project5\DI\IContainer;
use project5\DI\Container;
use project5\Session\Storage\Native;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

use project5\Session;
use project5\Stream\String;

use project5\Application;
use StasDovgodko\Uri\Url;


class Front extends Group implements IContainer
{
    const SESSION_COOKIE_NAME = 'xsid';

    /**
     * @var IOutputHandler[]
     */
    private $outputCallbacks = [];

    private $_sections = [];

    private $_displayErrors = false;

    protected $decorators = [];

    /**
     * @var Request|null
     */
    protected $request;

    public function __construct(Request $request, $baseUrl = null)
    {
        if ($baseUrl) {
            $baseUrl = new Url($baseUrl);
            $request = $request->withBaseUrl($baseUrl);
        } else {
            $baseUrl = null;
        }
        parent::__construct(null, $baseUrl);

        $this->request = $request;
    }

    public function setDecorator($type, IResponseDecorator $decorator)
    {
        $this->decorators[$type] = $decorator;
    }

    /**
     * @param $type
     * @return IResponseDecorator|null
     */
    public function getDecorator($type)
    {
        return array_key_exists($type, $this->decorators) ? $this->decorators[$type] : null;
    }

    public function setDisplayErrors($state = null){
        static $whoops = null; /** @var $whoops \Whoops\Run */

        if ($state === null) {
            $this->setDisplayErrors(in_array(strtolower(ini_get('display_errors')), ['1', 'stdout', 'on', 'true'], true));
        } elseif ($state === true) {
            if ($whoops === null) {
                $whoops = new \Whoops\Run;
                $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
            }
            $whoops->register();
        } elseif ($whoops) {
            $whoops->unregister();
        }


        $this->_displayErrors = $state;
    }

    public function urlByName($name, $params = [])
    {
        $route = $this->getRoute($name);

        if ($route instanceof Route) {
            return $this->uri($route, $params)->__toString();
        } else {
            return null;
        }
    }

    public function attachOutputCallback(IOutputHandler $callback)
    {
        $this->outputCallbacks[] = $callback;

        return $this;
    }

    public function addSection($pattern, $name)
    {
        $this->_sections[$pattern] = $name;
    }


    /**
     *
     */
    public function configure(Url $baseUrl)
    {
        /*$this->_app->addExtension('web', function (Container $container, $config) use ($baseUrl) {
            $this->_info = $config;
            $this->baseUrl = $baseUrl;
        });

        if ($this->_sections) {
            foreach ($this->_sections as $pattern => $name) {
                $this->_app->addExtension($name, function (Container $container, $config) use ($pattern, $name, $baseUrl) {

                    $subgroup = $container->get('web.group');
                    $subgroup->_info = $config;

                    $this->addRoute($pattern, $subgroup, $name);
                });
            }
        }*/

        //$this->_app->configure();

        $this->_initialize();
    }

    /**
     * Get front request instance
     *
     * @return Request
     */
    public static function CreateRequest(\project5\Session\IStorage $session_storage = null)
    {

        $url = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '/';
        list($request_url_string, $request_url_query) = (strpos($url, '?') !== false) ? explode('?', $url, 2) : array($url, '');

        $request_uri = new Url($request_url_string);

        $base_request_uri = new Url('');


        $baseUrl = new Url('');
        $url = $request_uri->getRelated($base_request_uri)->withQuery($request_url_query);
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : Request::METHOD_GET;
        $version = Request::VERSION_1_1;

        $params = array_merge($_GET, $_POST);



        //$url = $url->getRelated($base_request_uri);
        $baseUrl = $baseUrl->resolve($base_request_uri);




        $request = new Request($url, $method, $params, $version, $_SERVER);
        $request = $request->withBaseUrl($baseUrl);

        if (function_exists('apache_request_headers'))
        {
            foreach(apache_request_headers() as $name => $value) {
                $request = $request->withHeader($name, $value);
            }
        }
        else {
            $request_reflection = new \ReflectionClass(Request::CLASS);
            foreach ($request_reflection->getConstants() as $c) {
                $server_var = 'HTTP_' . strtoupper(str_replace('-', '_', $c));

                if (isset($_SERVER[$server_var])) {
                    $request = $request->withHeader($c, $_SERVER[$server_var]);
                }
            }
        }

        $user_salt = substr(md5($request->getUserAgent()), 0, 5);
        $sid = null;
        if (!empty($_COOKIE)) {
            $request = $request->withCookieParams($_COOKIE);


            // look for session
            $sid = $request->getCookie(self::SESSION_COOKIE_NAME);

            if ($sid) {
                // validate
                if (strpos($sid, $user_salt) === false) {
                    $sid = null;
                }
            }
        }
        $reinit_session = false;
        if (!$sid) {
            $sid = $user_salt . md5(microtime(true).rand(0, PHP_INT_MAX));
            $reinit_session = true;
        }
        $request = $request->withSession($session = new Session($sid, $session_storage ? $session_storage : new Native()));


        // inject upload
        if ($method === 'POST' && !empty($_FILES)) {
            $uploaded = [];
            foreach($_FILES as $name => $data) {
                $uploaded[$name] = new Request\UploadedFile($data);
            }
            if (count($uploaded) > 0) $request = $request->withUploadedFiles($uploaded);
        }


        return $request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param null|Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }



    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Application $app, Request $request = null)
    {
        if ($env = $app->isConfigured()) {
            if ($request === null) {
                $request = $this->request;
            }

            $response = $this->_prepareResponse($request);


            /*$app
                ->inject('web.request', $request)
                ->inject('web.response', $response);
    */



            try {
                $this->configure($request->getBaseUrl());

                $response = $this->dispatch($request, $response);


                $body = $response->getBody();
                if ($body && $response->getStatusCode() === 200) {
                    if ($request->getMethod() === Request::METHOD_HEAD) {
                        $response = $response
                            ->withHeader('Accept-Ranges', 'bytes')
                            ->withHeader('Content-Length', $body->getSize())
                            ->withoutBody();
                    } elseif ($request->getMethod() === Request::METHOD_GET && $request->hasHeader('Range')) {
                        $range_values = $request->getHeader('Range');

                    }
                }


            } catch (\Exception $e) {

                if ($this->_displayErrors) throw $e; // should be displayed with Whoops
                else {

                    // the developer asked for a specific status code
                    if ($response->hasHeader('X-Status-Code')) {
                        $response = $response->withStatus($response->getHeader('X-Status-Code'))->withoutHeader('X-Status-Code');
                    } elseif (!$response->isError() && !$response->isRedirect()) {
                        // ensure that we actually have an error response
                        $response = $response->withStatus(Response::STATUS_SERVER_ERROR);
                    }
                }
            }

            return $this->_flush($request, $response);
        } else {
            throw new \DomainException('App not configured yet!');
        }

    }

    public function handleException(\Exception $e, Request $request, Response $response, IResponseDecorator $decorator)
    {
        if ($e instanceof IException) {
            return $e->decorateResponse($response, $decorator);
        } elseif (!$this->_displayErrors) {

            return $decorator->decorateResponse($response, $request->getAttributes(), [
                'exception' => $e,
                'exception-message' => $e->getMessage(),
            ]);
        } else {
            throw $e;
        }
    }


    /**
     * @param Request $request
     * @return Response
     */
    public function _prepareResponse(Request $request)
    {
        $response = new Response($request);

        return $response;
    }

    protected function _flush(Request $request, Response $response)
    {
        if ($request->hasSession()) {
            $session = $request->getSession();

            setcookie(self::SESSION_COOKIE_NAME, $session->getId(), null, '/');
        }


        foreach ($response->getCookies() as $name => $cookie_info)
        {
            list($value, $expire, $path) = $cookie_info;

            setcookie($name, $value, $expire, $path);
        }


        $body = $response->getBody();

        $output_length = null;
        if ($this->outputCallbacks) {
            if ($body) {

                foreach ($this->outputCallbacks as $handler) {
                    /** @var $handler IOutputHandler */
                    $return_response = null;
                    if ($response->isHtml()) {
                        $html = $body->getContents();
                        $return_response = $handler->handleHtml($html, $response);

                        if ($return_response instanceof Response) {
                            $response = $return_response->withBody(new String($html));
                        } else {
                            $response = $response->withBody(new String($html));
                        }
                    } elseif ($response->isJson()) {
                        $data = @json_decode($body->getContents());
                        $return_response = $handler->handleJson($data, $response);

                        if ($return_response instanceof Response) {
                            $response = $return_response->withBody(new String(json_encode($data, JSON_UNESCAPED_UNICODE)));
                        } else {
                            $response = $response->withBody(new String(json_encode($data, JSON_UNESCAPED_UNICODE)));
                        }
                    } else {
                        $return_response = $handler->handleBinary($body, $response);

                        if ($return_response instanceof Response) {
                            $response = $return_response->withBody($body);
                        } else {
                            $response = $response->withBody($body);
                        }
                    }
                }

                $output_length = $response->getBody()->getSize();
            }
        } else {
            $output_length = $body ? $body->getSize() : null;
        }

        if (!headers_sent())
        {
            $has_content_length = false;

            header(sprintf("HTTP/%s %s %s", $request->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase()));
            foreach ($response->getHeaders() as $name => $values)
            {
                foreach($values as $value) {
                    if (!$has_content_length && (strtolower($name) === strtolower(Response::HEADER_CONTENT_LENGTH))) {
                        $has_content_length = true;
                    }
                    header(sprintf("%s: %s", $name, $value));
                }
            }

            if (!$has_content_length && $output_length)
            {
                header(sprintf("%s: %s", Response::HEADER_CONTENT_LENGTH, $output_length));
            }
        }


        $body = $response->getBody();

        if ($body) {
            $body->rewind();
            while(!$body->eof()) {
                echo $body->read(8192);
            }
        }
        //ob_end_flush();

        if (function_exists('fastcgi_finish_request'))
        {
            fastcgi_finish_request();
        }
    }

    public static function Setup(Container $container, $id)
    {
        return [
            'html_decorator' => $container->getDefinition('web.response.decorator.html'),
            'json_decorator' => $container->getDefinition('web.response.decorator.json'),
            'xml_decorator'  => $container->getDefinition('web.response.decorator.xml'),
            'container' => $container,
            'id' => $id,
        ];
    }

    public static function Inject(Definition $definition, $config, ContainerBuilder $builder, $setupOptions = null)
    {
        $container = $setupOptions['container']; /** @var $container Container */
        $id = $setupOptions['id'];
        $configurators = [];
        if ($existed_configurator = $definition->getConfigurator()) {
            $configurators[] = $existed_configurator;
        }
        $definition->setConfigurator(function(Front $front) use(&$configurators) {
            foreach($configurators as $configurator) {
                call_user_func_array($configurator, [$front]);
            }
        });
        if (isset($config['filters'])) {
            foreach($config['filters'] as $filter) {

                $arguments = [];
                if (is_array($filter)) {
                    if (sizeof($filter) === 2) list($filter, $arguments) = $filter;
                    else $filter = $filter[0];
                }

                $definition->addMethodCall('addFilter', [$filter, $arguments]);
                /*
                $filter = $container->resolveServices($filter_data);


                if ($filter instanceof Filter) {
                    $this->addFilter($filter, $arguments);
                }*/
            }
        }

        if (isset($config['routes'])) {
            foreach ($config['routes'] as $pattern => $route_info) {

                $route_definition = null;

                if (array_key_exists('controller', $route_info)) {
                    $controller_class = $route_info['controller'];

                    $route_definition = new Definition(ControllerManager::class, [$container, $controller_class]);
                    $route_definition->setLazy(true);

                    if (array_key_exists('html', $route_info)) { // html
                        if (($pos = strrpos($controller_class, '\\')) !== false) {
                            $class = substr($controller_class, $pos + 1);
                        } else {
                            $class = $controller_class;
                        }

                        if (substr($class, -10) === 'Controller') {
                            $template_name = strtolower(substr($class, 0, -10));
                        } else {
                            $template_name = $class;
                        }

                        if (is_array($route_info['html'])) {
                            list($action, $extra_arguments) = $route_info['html'];
                        } else {
                            $action = $route_info['html'];
                            $extra_arguments = [];
                        }

                        $template_name .= DIRECTORY_SEPARATOR . $action;



                        $response_decorator_definition = clone $setupOptions['html_decorator'];
                        $response_decorator_definition->addMethodCall('setTemplateName', [$template_name]);

                        $route_definition->addMethodCall('addMap', ['html', $action, $response_decorator_definition, $extra_arguments]);
                    }

                    if (array_key_exists('post', $route_info)) { // html
                        if (is_array($route_info['post'])) {
                            list($action, $extra_arguments) = $route_info['post'];
                        } else {
                            $action = $route_info['post'];
                            $extra_arguments = [];
                        }

                        $route_definition->addMethodCall('addMap', ['post', $action, null, $extra_arguments]);
                    }

                    if (array_key_exists('json', $route_info)) { // html
                        if (is_array($route_info['json'])) {
                            list($action, $extra_arguments) = $route_info['json'];
                        } else {
                            $action = $route_info['json'];
                            $extra_arguments = [];
                        }

                        $response_decorator_definition = clone $setupOptions['json_decorator'];

                        $route_definition->addMethodCall('addMap', ['json', $action, $response_decorator_definition, $extra_arguments]);
                    }

                    if (array_key_exists('binary', $route_info)) { // html
                        if (is_array($route_info['binary'])) {
                            list($action, $extra_arguments) = $route_info['binary'];
                        } else {
                            $action = $route_info['binary'];
                            $extra_arguments = [];
                        }

                        $route_definition->addMethodCall('addMap', ['binary', $action, null, $extra_arguments]);
                    }

                    if (array_key_exists('xml', $route_info)) { // html
                        if (($pos = strrpos($controller_class, '\\')) !== false) {
                            $class = substr($controller_class, $pos + 1);
                        } else {
                            $class = $controller_class;
                        }

                        if (substr($class, -10) === 'Controller') {
                            $template_name = strtolower(substr($class, 0, -10));
                        } else {
                            $template_name = $class;
                        }

                        if (is_array($route_info['xml'])) {
                            list($action, $extra_arguments) = $route_info['xml'];
                        } else {
                            $action = $route_info['xml'];
                            $extra_arguments = [];
                        }

                        $template_name .= DIRECTORY_SEPARATOR . $action;

                        $response_decorator_definition = clone $setupOptions['xml_decorator'];
                        $response_decorator_definition->addMethodCall('setTemplateName', [$template_name]);

                        $route_definition->addMethodCall('addMap', ['xml', $action, $response_decorator_definition, $extra_arguments]);
                    }
                } elseif (array_key_exists('route', $route_info)) {
                    $route_definition = clone $container->findDefinition($route_info['route']);
                }

                if ($route_definition) {

                    if (array_key_exists('filters', $route_info)) {
                        foreach ($route_info['filters'] as $filter_data) {

                            if (is_array($filter_data)) {
                                if (sizeof($filter_data) >= 2) {
                                    list($filter, $filter_options) = $filter_data;
                                } else {
                                    $filter = $filter_data[0];
                                    $filter_options = [];
                                }
                            } else {
                                $filter = $filter_data;
                                $filter_options = [];
                            }

                            $route_definition->addMethodCall('addFilter', [$filter, $filter_options]);
                        }
                    }

                    $arguments = [];
                    if (isset($route_info['attrs'])) {
                        $arguments = array_merge($arguments, $route_info['attrs']);


                    }
                    if (isset($route_info['defaults'])) {
                        $arguments = array_merge($route_info['defaults'], $arguments);


                    }


                    $definition->addMethodCall('addRoute', [$pattern, $route_definition, isset($route_info['name']) ? $route_info['name'] : null, $arguments]);
                }


                /*
                $is_handle = array_key_exists('handle', $route_info);
                $is_html = array_key_exists('html', $route_info);
                $is_json = array_key_exists('json', $route_info);

                // create route definition
                if ($is_handle || $is_html || $is_json) {
                    if (isset($route_info['html'])) {
                        list($class_method, $arguments) = $route_info['html'];
                    } elseif (isset($route_info['json'])) {
                        list($class_method, $arguments) = $route_info['json'];
                    } else {
                        list($class_method, $arguments) = $route_info['handle'];
                    }

                    if (is_array($class_method)) {
                        list($class, $method) = $class_method;
                    } elseif ($pos = strpos($class_method, ':')) {
                        list($class, $method) = explode(':', $class_method);
                    } else {
                        $class = $class_method; $method = null;
                    }

                    $controller_definition = new Definition($class);

                    $route_definition = new Definition(ControllerManager::class, [$setupOptions['container'], $controller_definition, $method]);
                    $route_definition->setLazy(true);

                    if ($is_html) {
                        if (($pos = strrpos($class, '\\')) !== false) {
                            $class = substr($class, $pos + 1);
                        }

                        if (substr($class, -10) === 'Controller') {
                            $template_name = strtolower(substr($class, 0, -10));
                        } else {
                            $template_name = $class;
                        }

                        if ($method) {
                            $template_name .= DIRECTORY_SEPARATOR . $method;
                        }


                        $response_decorator_definition = clone $setupOptions['html_decorator'];
                        $response_decorator_definition->addMethodCall('setTemplateName', [$template_name, 'controller']);

                        $route_definition->addMethodCall('setResponseDecorator', [$response_decorator_definition]);
                    } elseif ($is_json) {
                        $response_decorator_definition = clone $setupOptions['json_decorator'];

                        $route_definition->addMethodCall('setResponseDecorator', [$response_decorator_definition]);
                    }



                    /*
                    $decorator = $this->_container->get('web.response.decorator.html');
                    if ($decorator instanceof HTML) {
                        $decorator->setTemplateName($template_name, 'controller');
                    }
                    $route->setResponseDecorator($decorator);*/
            }

            /*
            $arguments = [];
            if (isset($route_info['group'])) {
                $data = $route_info['group'];
                if (isset($data[0], $data[1])) {
                    $info = $data[0]; $arguments = $data[1];
                } else {
                    $info = $data;
                }

                $route = new Group($builder);
                $route->_info = array('routes' => $info);
            } elseif (isset($route_info['route'])) {
                if (is_array($route_info['route']) && isset($route_info['route']['class'])) {
                    $class = $route_info['route']['class'];

                    $args = [];
                    if (isset($route_info['route']['properties'])) {
                        foreach ($route_info['route']['properties'] as $name => $param_value) {
                            $values = $this->_container->resolveServices($param_value);
                            $args[$name] = $values;
                        }
                    }

                    $reflection = new \ReflectionClass($class);
                    $route = $reflection->newInstanceArgs($args);
                } else {
                    $route = $this->_container->resolveServices($route_info['route']);
                    if (!($route instanceof Route)) {
                        throw new \DomainException('"route" section should be Route instance or has "class"');
                    }
                }
            } elseif (isset($route_info['crud'])) {
                $data = $route_info['crud'];

                if (is_array($data)) {
                    list($class, $arguments) = $data;
                } else {
                    $class = $data;
                }

                $route = new $class($this->_container);




            } elseif (isset($route_info['controller'])) {
                list($class_method, $arguments) = $route_info['controller'];

                if (is_array($class_method)) {
                    list($class, $method) = $class_method;
                } elseif ($pos = strpos($class_method, ':')) {
                    list($class, $method) = explode(':', $class_method);
                } else {
                    $class = $class_method; $method = null;
                }
                $object = new $class;
                $route = new ControllerManager($this->_container, $object, $method);


                if (($pos = strrpos($class, '\\')) !== false) {
                    $class = substr($class, $pos+1);
                }

                if (substr($class, -10) === 'Controller') {
                    $template_name = strtolower(substr($class, 0, -10));
                } else {
                    $template_name = $class;
                }

                if ($method) {
                    $template_name .= DIRECTORY_SEPARATOR.$method;
                }
                $decorator = $this->_container->get('web.response.decorator.html');
                if ($decorator instanceof HTML) {
                    $decorator->setTemplateName($template_name, 'controller');
                }
                $route->setResponseDecorator($decorator);

            } elseif (isset($route_info['json-controller'])) {
                list($class_method, $arguments) = $route_info['json-controller'];

                if (is_array($class_method)) {
                    list($class, $method) = $class_method;
                } elseif ($pos = strpos($class_method, ':')) {
                    list($class, $method) = explode(':', $class_method);
                } else {
                    $class = $class_method; $method = null;
                }
                $object = new $class;
                $route = new ControllerManager($this->_container, $object, $method);
                $route->setResponseDecorator($this->_container->get('web.response.decorator.json'));
            } else {
                throw new \DomainException('Wrong format ' . json_encode($route_info));
            }


            if (isset($route_info['attrs'])) {
                $arguments = array_merge($arguments, $route_info['attrs']);
            }

            if ($arguments) {
                $arguments = array_map(function($value) use($container) {
                    return $container->resolveServices($value);
                }, $arguments);
            }





            if (isset($route_info['filters'])) {
                foreach($route_info['filters'] as $filter_data) {
                    $filter = $container->resolveServices($filter_data);
                    $filter_arguments = [];
                    if (is_array($filter)) {
                        list($filter, $filter_arguments) = $filter;
                    }
                    if ($filter instanceof Filter) {
                        $route->addFilter($filter, $filter_arguments);
                    } else {
                        die("??");
                    }
                }
            }

            if ($route instanceof Group) {
                $route->baseUrl = $this->baseUrl;
            }

            if (isset($route_info['name'])) {
                $arguments['route_name'] = $route_info['name'];
            }

            $this->addRoute($pattern, $route, isset($route_info['name']) ? $route_info['name'] : null, $arguments);
            */

        }
        if (array_key_exists('index', $config)) {
            $index_name = $config['index'];
            $configurators[] = function(Front $front) use($index_name) {
                $front->setIndex($front->getRoute($index_name));
            };
            //$definition->se('setIndex', [new Expression('this.getRoute("'.$config['index'].'"")')]);
        }

        if (array_key_exists('error', $config)) {
            $index_name = $config['error'];
            $configurators[] = function(Front $front) use($index_name) {
                $front->setError($front->getRoute($index_name));
            };
            //$definition->se('setIndex', [new Expression('this.getRoute("'.$config['index'].'"")')]);
        }


        if (array_key_exists('attrs', $config)) {
            $arguments = $config['attrs'];

            $arguments = array_map(function($value) use($container) {
                return $container->resolveServices($value);
            }, $arguments);

            $configurators[] = function(Front $front) use($arguments) {

                foreach($arguments as $name => $value) {
                    $front->addProperty($name, $value);
                }
            };
        }

        $configurators[] = function(Front $front) use($container) {

            if (!$front->error) {

                $front->error = new ControllerManager($container, $front);
                foreach (['html', 'json', 'xml'] as $type) {
                    $decorator = $front->getDecorator($type);

                    if ($decorator) {
                        $decorator = clone $decorator;

                        $front->error->addMap($type, 'handleException', $decorator, ['decorator' => $decorator]);
                    }
                }
            }
        };

    }
}
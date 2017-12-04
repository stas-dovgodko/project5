<?php
namespace project5\Web;

use project5\Exception\Unsupported;
use project5\Web\Exception\NotAllowedException;
use project5\Web\Exception\RouteNotFoundException;
use project5\Web\ResponseDecorator\HTML;
use project5\Web\ResponseDecorator\JSON;
use project5\Web\ResponseDecorator\XML;
use ReflectionParameter;
use Sphinx\SphinxClient;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use project5\Web\Exception\PropertyNotFoundException;
use project5\Web\Request as ControllerRequest; //?
use project5\Web\Route\Controller;

/**
 * Application
 *
 */
class ControllerManager extends Route
{
    const MAP_POST = 'post';
    const MAP_JSON = 'json';
    const MAP_XML = 'xml';
    const MAP_HTML = 'html';


    const RETURN_RELOAD = 1;
    const RETURN_NOT_FOUND = 3;
    const RETURN_NOT_ALLOWED = 4;

    /**
     * @var string
     */
    protected $controller;

    /**
     * @var ContainerBuilder
     */
    protected $container;

    protected $map = [];

    public function __construct(ContainerBuilder $container, $controller, array $map = [])
    {
        parent::__construct();
        $this->container = $container;
        $this->controller = $controller;
        $this->map = $map;
    }


    public function addMap($type, $action, IResponseDecorator $decorator = null, array $extra_params = [])
    {
        $this->map[$type] = [$action, $decorator, $extra_params];
    }

    protected function methodArguments(
        \ReflectionMethod $method,
        Request $request,
        Response $response,
        array $arguments = []
    ) {
        /**
         * Create view
         */
        $signature = [];

        foreach ($method->getParameters() as $param) {
            $param_name = $param->getName();
            $param_class = $param->getClass();

            $value = null;
            /* @var $param \ReflectionParameter */
            $prev_exception = null;
            if ($param_class) {
                if ($param_class->isInstance($this)) {
                    $value = $this;
                } elseif ($param_class->isInstance($response)) {
                    $value = $response;
                } elseif ($param_class->isInstance($request)) {
                    $value = $request;
                } else {
                    $delayed_candidates = [];
                    foreach ($this->container->getDefinitions() as $id => $definition) {
                        $class = $definition->getClass();

                        if ($class) {
                            if ($class === $param_class->getName()) {
                                $value = $this->container->get($id);
                            } else {
                                $delayed_candidates[$id] = $definition;
                            }
                        }
                    }

                    if ($value === null) {
                        $param_class_name = $param_class->getName();

                        foreach($delayed_candidates as $id => $definition) {
                            $class = $definition->getClass();


                            if ($class) {
                                try {
                                    $reflection = new \ReflectionClass($class);

                                    if ($param_class_name === $reflection->getName() || $reflection->isSubclassOf($param_class)) {
                                        $value = $this->container->get($id);
                                        break;
                                    }
                                } catch (\ReflectionException $e) {
                                    // just ignore but track
                                    $prev_exception = $e;
                                }
                            }
                        }
                    }
                }
            }
            if ($value === null) {

                if (array_key_exists($param_name, $arguments)) {
                    $value = $arguments[$param_name];
                } elseif (array_key_exists($param_name, $this->properties)) {
                    $value = $this->properties[$param_name];
                } elseif ($request->hasAttribute($param_name)) {
                    $value = $request->getAttribute($param_name);
                } elseif ($this->container->hasParameter($param_name)) {
                    $container_param = $this->container->getParameter($param_name);

                    $value = $this->container->resolveServices($container_param);
                } elseif ($request->hasParam($param_name)) {
                    $value = $request->getParam($param_name);
                } elseif ($param->isDefaultValueAvailable()) {
                    $value = $param->getDefaultValue();
                } else {
                    throw new PropertyNotFoundException("Controller argument $param_name not found".($prev_exception ? '. Possible reason - '.$prev_exception->getMessage():''), 0, $prev_exception);
                }

                if ($param->isOptional() && ($default = $param->getDefaultValue()) !== null) {
                    if (is_int($default)) {
                        $value = (int)($value);
                    } elseif (is_bool($default)) {
                        $value = (bool)($value);
                    }
                }
            }

            if ($param_class && $param->isOptional() && ($param->getDefaultValue() === null) && is_object($value)) {
                $reflection = new \ReflectionObject($value);

                if ($param_class->getName() !== $reflection->getName() && !$reflection->isSubclassOf($param_class)) {



                    $value = $default;
                }
            }

            $signature[$param_name] = $value;
        }

        return $signature;
    }

    public function mapRequest(Request $request)
    {
        if (array_key_exists(self::MAP_POST, $this->map) && $request->isPost()) {
            return $this->map[self::MAP_POST];
        }

        if (array_key_exists(self::MAP_JSON, $this->map)
            && ($request->isXHR() || !array_key_exists(self::MAP_HTML, $this->map))) {
            // json
            $request_type = self::MAP_JSON;
        } elseif (array_key_exists(self::MAP_XML, $this->map)
            && ($request->isXHR() || !array_key_exists(self::MAP_HTML, $this->map))) {
            // xml
            $request_type = self::MAP_XML;
        } elseif (array_key_exists(self::MAP_HTML, $this->map)) {
            // xml
            $request_type = self::MAP_HTML;
        } elseif (count($this->map) > 0) {
            // get first found map
            reset($this->map);
            $request_type = key($this->map);

        } else {
            throw new Unsupported('Can\'t map request type');
        }

        return $this->map[$request_type];
    }

    protected function dispatchRoute(Request $request, Response $response, array $arguments = [])
    {
        list($action, $response_decorator, $extra_arguments) = $this->mapRequest($request);

        if ($extra_arguments) {
            $arguments = array_merge($arguments, $extra_arguments);
        }

        if (is_object($this->controller)) {
            $controller_object = $this->controller;
            $reflection = new \ReflectionObject($controller_object);
        } else {
            $reflection = new \ReflectionClass($this->controller);
            if ($constructor = $reflection->getConstructor()) {
                $constructor_signature = $this->methodArguments($constructor, $request, $response, $arguments);

                $controller_object = $reflection->newInstanceArgs($constructor_signature);
            } else {
                $controller_object = $reflection->newInstanceWithoutConstructor();
            }
        }

        if ($reflection->hasMethod($action)) {
            $action_reflection = $reflection->getMethod($action);
            $action_signature = $this->methodArguments($action_reflection, $request, $response, $arguments);

            $return = $action_reflection->invokeArgs($controller_object, $action_signature);

            if ($return instanceof Response) {
                return $return;
            } elseif ($return === self::RETURN_RELOAD) {
                return Response\Factory::Redirect($response, $request->getUrl(true));
            } elseif ($return === self::RETURN_NOT_FOUND) {
                throw new RouteNotFoundException();
            } elseif ($return === self::RETURN_NOT_ALLOWED) {
                throw new NotAllowedException();
            } elseif ($response_decorator) {
                // decorate
                return $response_decorator->decorateResponse(
                    $response,
                    array_merge(
                        [
                            'controller' => $controller_object
                        ],
                        $request->getAttributes(),
                        $arguments
                    ),
                    $return ? (array)$return : null
                );
            } else {
                throw new Unsupported('Controller should return response or be decorated');
            }
        } else {
            throw new Unsupported(sprintf('Missed %s action in %s controller', $action, $this->controller));
        }
    }
}

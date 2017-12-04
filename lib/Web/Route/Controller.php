<?php
namespace project5\Web\Route;

use project5\Web\Exception\NotAllowedException;
use project5\Web\Exception\NotFoundException;
use project5\Web\Exception\PropertyNotFoundException;
use project5\Web\Exception\RouteNotFoundException;
use project5\Web\IResponseDecorator;
use project5\Web\Request;
use project5\Web\Response;
use project5\Web\Route;

/**
 * MVC controller route
 *
 */
class Controller extends Route
{
    const SKIP_RENDER = 2;
    const RELOAD = 1;
    const NOT_FOUND = 3;
    const NOT_ALLOWED = 4;

    /**
     * @var IResponseDecorator
     */
    protected $_responseDecorator;

    /**
     * Controller action
     *
     * @var string
     */
    private $action;

    /**
     * Reflection method
     *
     * @var \ReflectionMethod
     */
    private $reflectionMethod;

    /**
     * @var object
     */
    protected $controller;

    public function __construct($controller = null, $action = null)
    {
        parent::__construct('');

        if ($action === null && is_array($controller)) {
            list($controller, $action) = $controller;
        }

        $this->action = $action;
        $this->controller = $controller ? $controller : $this;
    }

    public function setResponseDecorator(IResponseDecorator $decorator)
    {
        $this->_responseDecorator = $decorator;
    }

    /**
     * @return IResponseDecorator|null
     */
    public function getResponseDecorator()
    {
        return $this->_responseDecorator;
    }

    /**
     * Initilize method reflection
     *
     * @throws \Exception something wrong
     * @return \ReflectionMethod
     */
    protected function initialize()
    {
        if ($this->reflectionMethod === null) {
            $reflection = new \ReflectionObject($this->controller);


            $method_name = 'handle' . ucfirst($this->action);
            if ($reflection->hasMethod($method_name)) {

                $this->reflectionMethod = $reflection->getMethod($method_name);

                foreach ($this->reflectionMethod->getParameters() as $param) {
                    /* @var $param \ReflectionParameter */

                    if ($param->isOptional() && ($default = $param->getDefaultValue()) !== null) {
                        $this->default[$param->getName()] = $default;
                    }
                }

            } else {
                throw new \Exception("Action method $method_name not found");
            }

        }

        return $this->reflectionMethod;
    }

    public function __destruct()
    {
        unset($this->reflectionMethod);
    }


    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @return object|null
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param object $controller
     */
    public function setController($controller)
    {

        $this->controller = $controller;

        $this->reflectionMethod = null;
    }

    protected function dispatchRoute(Request $request, Response $response, array $arguments = [])
    {
        $this->initialize();

        if ($request->isPost()) {
            // tries to found POST handler
            $method_name = 'post' . $this->action;
            $reflection = new \ReflectionObject($this->controller);
            if ($reflection->hasMethod($method_name)) {
                $post_return = $this->_dispatchMethod(
                    $reflection->getMethod($method_name),
                    $request,
                    $response,
                    $arguments
                );
                if ($post_return === self::RELOAD) {
                    return Response\Factory::Redirect($response, $request->getUrl(true));
                }
            }
        }

        $return = $this->_dispatchMethod($this->reflectionMethod, $request, $response, $arguments);

        if ($return instanceof Response) {
            return $return;
        } elseif ($return instanceof Url) {
            return Response\Factory::Redirect($response, $return);
        } elseif ($return === self::RELOAD) {
            return Response\Factory::Redirect($response, $request->getUrl(true));
        } elseif ($return === self::NOT_FOUND) {
            throw new RouteNotFoundException();
        } elseif ($return === self::NOT_ALLOWED) {
            throw new NotAllowedException();
        } elseif ($return !== self::SKIP_RENDER) {

            $response = $this->_decorateResponse(
                $response,
                array_merge($request->getAttributes(), $arguments),
                $return ? (array)$return : []
            );

            return $response;
        }
    }

    protected function _decorateResponse(Response $response, $attributes, $params)
    {
        if ($this->_responseDecorator) {
            return $this->_responseDecorator->decorateResponse($response, $attributes, $params);
        } else {
            return $response;
        }
    }

    /**
     * @param \ReflectionParameter $param
     * @param Request $request
     * @param array $arguments
     * @return mixed
     * @throws PropertyNotFoundException
     */
    protected function _prepareMethodParam(
        \ReflectionParameter $param,
        Request $request,
        array $arguments = []
    )
    {
        $param_name = $param->getName();

        if (isset($arguments[$param_name])) {
            return $arguments[$param_name];
        } elseif (isset($this->properties[$param_name])) {
            return $this->properties[$param_name];
        } elseif ($request->hasAttribute($param_name)) {
            return $request->getAttribute($param_name);
        } elseif ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        } else {
            throw new PropertyNotFoundException("Controller action argument $param_name not found");
        }
    }

    /**
     * @param \ReflectionClass $expectedClass
     * @return Object|null
     */
    protected function injectObjectByClass(\ReflectionClass $expectedClass)
    {
        if ($expectedClass->isInstance($this)) {
            return $this;
        } else {
            return null;
        }
    }

    protected function _dispatchMethod(
        \ReflectionMethod $method,
        Request $request,
        Response $response,
        array $arguments = []
    ) {
        /**
         * Create view
         */
        $signature = array();

        foreach ($method->getParameters() as $param) {
            $param_name = $param->getName();

            $value = null;
            /* @var $param \ReflectionParameter */
            if ($param_class = $param->getClass()) {
                if ($param_class->isInstance($response)) {
                    $value = $response;
                } elseif ($param_class->isInstance($request)) {
                    $value = $request;
                } else {
                    $value = $this->injectObjectByClass($param_class);
                }
            }
            if ($value === null) {
                try {
                    $value = $this->_prepareMethodParam($param, $request, $arguments);
                } catch (PropertyNotFoundException $e) {
                    if ($param->isOptional()) {
                        $value = $param->getDefaultValue();
                    } else {
                        throw $e;
                    }
                }

                if ($param->isOptional() && ($default = $param->getDefaultValue()) !== null) {
                    if (is_int($default)) {
                        $value = (int)($value);
                    } elseif (is_bool($default)) {
                        $value = (bool)($value);
                    }
                }
            }

            $signature[$param_name] = $value;
        }

        return $method->invokeArgs($this->controller, $signature);
    }
}

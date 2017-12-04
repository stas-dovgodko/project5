<?php
namespace project5\Web\Filter;

use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use project5\Web\Filter;
use project5\Web\Request;
use project5\Web\Response;
use project5\Web\Route;

/**
 * Filter chain support.
 * Filter chain can be applicable to project? application and route
 *
 */
class Chain implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Route
     */
    private $_route;

    private $_position = 0;

    /**
     * Filters chain
     *
     * @var array
     */
    private $_filters;

    private $_arguments = [];

    private $options;

    public function __construct(Route $route)
    {
        $this->_position = 0;
        $this->_route = $route;
        $this->logger = $route->getLogger();
        $this->_filters = [];

        $this->options = [];
    }

    /**
     * Current filter options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed
     */
    public function getOption($name, $default = null)
    {
        return array_key_exists($name, $this->options) ? $this->options[$name] : $default;
    }

    /**
     * @return Route
     */
    public function getRoute()
    {
        return $this->_route;
    }

    /**
     * Reset internal filter position
     *
     * @return Chain Fluent API support
     */
    public function resetPosition()
    {
        $this->_position = 0;

        return $this;
    }


    /**
     * @deprecated
     *
     * @param $name
     * @param $value
     */
    public function setArgument($name, $value)
    {
        $this->_arguments[$name] = $value;
    }

    /**
     * @deprecated
     *
     * Get chain arguments
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->_arguments;
    }

    /**
     * Add filter
     *
     * @param Filter $filter
     * @param array $options
     * @return Chain
     */
    public function addFilter(Filter $filter, $options = [])
    {
        $this->_filters[] = [$filter, (array)$options];

        if ($this->logger) {
            $filter->setLogger($this->logger);
        }

        return $this;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param Exception $e
     * @return Response|void
     * @throws Exception
     */
    public function catchException(Request $request, Response $response, Exception $e)
    {
        if (isset($this->_filters[$this->_position-1])) {
            list($filter, $options) = $this->_filters[$this->_position-1];
            $this->options = $options;

            /** @var $filter Filter */

            $filter_response = $filter->catchException($e, $request, $response, $this);

            if ($filter_response instanceof  Response) return $filter_response;
        } else {
            throw $e;
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param bool $flag
     * @return Response|void
     */
    public function doPostFilter(Request $request, Response $response, $flag = true)
    {
        $this->_position--;

        if (isset($this->_filters[$this->_position])) {
            list($filter, $options) = $this->_filters[$this->_position];
            $this->options = $options;

            /** @var $filter Filter */

            $filter_response = $filter->doPostFilter($request, $response, $this, $flag);

            if ($filter_response instanceof  Response) return $filter_response;
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response|void
     */
    public function doPreFilter(Request $request, Response $response)
    {
        if (isset($this->_filters[$this->_position])) {
            list($filter, $options) = $this->_filters[$this->_position++];
            $this->options = $options;

            /** @var $filter Filter */

            return $filter->doPreFilter($request, $response, $this);
        } else {
            return null;
        }

    }

    public function __toString()
    {
        $stack = array();
        foreach ($this->_filters as list($filter, $arguments)) {
            $stack[] = (string)$filter;
        }

        return (string)implode(' > ', $stack);
    }

    /**
     * Logs the method call or the executed SQL statement.
     *
     * @param string $msg Message to log.
     */
    protected function log($msg)
    {
        if ($msg && $this->logger) {
            $backtrace = debug_backtrace();


            $i = 1;
            $stackSize = count($backtrace);
            do {
                $callingMethod = $backtrace[$i]['function'];
                $i++;
            } while ($callingMethod == "log" && $i < $stackSize);

            $this->logger->info('[' . $callingMethod . '] ' . $msg);
        }
    }
}

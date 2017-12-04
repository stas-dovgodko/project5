<?php
    namespace project5\Web;


    use project5\Web\Exception\NotFoundException;
    use project5\Web\ResponseDecorator\HTML;
    use project5\Web\ResponseDecorator\JSON;

    use Symfony\Component\DependencyInjection\ContainerBuilder;

    /**
     * Application
     *
     * @package velocity
     */
    class Group extends Router
    {
        /**
         * @var Route|null Index route
         */
        protected $error;

        /**
         * @var Route|null Index route
         */
        protected $index;

        protected $_info;

        protected $baseUrl;

        protected function __construct(callable $callback = null, Url $baseUrl = null)
        {
            parent::__construct($callback);

            $this->baseUrl = $baseUrl;
        }

        /**
         * @return Url|null
         */
        public function getBaseUrl()
        {
            return $this->baseUrl;
        }

        /**
         * @return null|Route
         */
        public function getIndex()
        {
            return $this->index;
        }

        /**
         * @param null|Route $index
         */
        public function setIndex(Route $index)
        {
            $this->index = $index;
        }

        /**
         * @return null|Route
         */
        public function getError()
        {
            return $this->error;
        }

        /**
         * @param null|Route $index
         */
        public function setError(Route $error)
        {
            $this->error = $error;
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
        public function uri(Route $route, $params = [], $addMissedToQuery = true)
        {
            $uri = parent::uri($route, $params, $addMissedToQuery);

            if ($this->baseUrl !== null) {

                return $this->baseUrl->resolve($uri);
            }
            else return $uri;
        }

        protected function init()
        {
            
        }

        public function dispatch(Request $request, Response $response, array $arguments = [])
        {
            try {
                return parent::dispatch($request, $response, $arguments);
            } catch (\Exception $e) {

                if ($this->error) {

                    return $this->error->dispatchRoute($request, $response, array_merge($arguments, ['e' => $e]));
                } elseif ($this->index && ($e instanceof NotFoundException)) {
                    return $this->index->dispatchRoute($request, $response, array_merge($arguments, ['e' => $e]));
                } else {
                    throw $e;
                }
            }
        }
    }


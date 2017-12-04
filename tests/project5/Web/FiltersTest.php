<?php




use project5\Web\Filter;
use project5\Web\Filter\Chain;
use project5\Web\Request;
use project5\Web\Response;
use project5\Web\Route;
use project5\Web\Url;

class RouteMock extends Route
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function dispatchRoute(Request $request, Response $response, array $arguments = [])
    {

    }


}

class FilterMock extends Filter
{
    public static $FILTERS = [];
    public $id;
    public $last;

    public function __construct($id, $last = false)
    {
        $this->id = $id;
        $this->last = $last;
    }

    public function doPreFilter(Request $request, Response $response, Chain $filterChain)
    {
        self::$FILTERS[] = 'pre:' . $this->id;

        if (!$this->last) {
            return $filterChain->doPreFilter($request, $response);
        }

        return null;
    }

    public function doPostFilter(Request $request, Response $response, Chain $filterChain, $flag = true)
    {
        self::$FILTERS[] = 'post:' . $this->id;
        return $filterChain->doPostFilter($request, $response, $flag);
    }
}


class FiltersTest extends \PHPUnit_Framework_TestCase
{

    public function testChain()
    {
        FilterMock::$FILTERS = [];
        $route = new RouteMock();
        $chain = new Filter\Chain($route);
        $chain->addFilter(new FilterMock(1));
        $chain->addFilter(new FilterMock(2));
        $chain->addFilter(new FilterMock(3));

        $r = new Request(new Url('/'));

        $chain->doPreFilter($r, new Response($r));
        $chain->doPostFilter($r, new Response($r));

        $this->assertEquals(['pre:1', 'pre:2', 'pre:3', 'post:3', 'post:2', 'post:1'], FilterMock::$FILTERS);
    }

    public function testChainSkeep()
    {
        FilterMock::$FILTERS = [];
        $route = new RouteMock();
        $chain = new Filter\Chain($route);
        $chain->addFilter(new FilterMock(1));
        $chain->addFilter(new FilterMock(2, true));
        $chain->addFilter(new FilterMock(3));

        $r = new Request(new Url('/'));

        $chain->doPreFilter($r, new Response($r));
        $chain->doPostFilter($r, new Response($r));

        $this->assertEquals(['pre:1', 'pre:2', 'post:2', 'post:1'], FilterMock::$FILTERS);
    }

    public function testChainRoute()
    {
        FilterMock::$FILTERS = [];
        $route = new RouteMock();
        $route->addFilter(new FilterMock(1));
        $route->addFilter(new FilterMock(2));
        $route->addFilter(new FilterMock(3));

        $r = new Request(new Url('/'));

        $route->dispatch($r, new Response($r));

        $this->assertEquals(['pre:1', 'pre:2', 'pre:3', 'post:3', 'post:2', 'post:1'], FilterMock::$FILTERS);
    }
}
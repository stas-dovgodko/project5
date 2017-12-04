<?php


use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\PhpFileCache;
use project5\Web\Request;
use project5\Web\Response;
use project5\Web\Route;
use project5\Web\Router;
use project5\Web\Url;

class LeafRouteMock extends Route
{
    public $id;

    public function __construct($id)
    {
        parent::__construct();

        $this->id = $id;
    }

    protected function dispatchRoute(Request $request, Response $response, array $arguments = [])
    {

    }
}


class RootRouterMock extends Router
{
    public $id;

    public function __construct($id = null, callable $callback = null)
    {
        parent::__construct($callback ? $callback : function () {
            $this->addRoute('path1', new LeafRouteMock('l1'), 'name1');
            $this->addRoute('path2', new LeafRouteMock('l2'), 'name2');
            $this->addRoute('path/path3', new LeafRouteMock('l3'), 'name3');

            $this->addRoute('path3/', new RootRouterMock('r4', function () {
                $this->addRoute('path1', new LeafRouteMock('l4_1'), 'name4_1');
                $this->addRoute('path2/:k2', new LeafRouteMock('l4_2'), 'name4_2')->addDefault('k2', 'v2');
            }), 'name4');
        });

        $this->id = $id;

        $this->setCache(new PhpFileCache(sys_get_temp_dir()));
    }

    /**
     * @return \SplObjectStorage
     */
    public function getRoutesStorage()
    {
        return $this->_routes;
    }

    public function getNames()
    {
        return $this->_names;
    }
}

class WebRouterTest extends \PHPUnit_Framework_TestCase
{

    public function testInit()
    {
        $root = new RootRouterMock();
        $this->assertEquals(0, $root->getRoutesStorage()->count()); // should not be loaded before dispatch!
    }

    public function testGetChild()
    {
        $root = new RootRouterMock();
        $this->assertEquals('l2', $root->getChild(new Url('path2'))->id);

        $this->assertEquals(4, $root->getRoutesStorage()->count()); // inited!
    }

    public function testGetChildNotFound()
    {
        $root = new RootRouterMock();
        $this->assertEquals(null, $root->getChild(new Url('path2missed')));

        $this->assertEquals(4, $root->getRoutesStorage()->count()); // inited!
    }

    public function testGetChildInTree()
    {
        $params = [];
        $matched = null;
        $root = new RootRouterMock();
        $this->assertEquals('r4', $root->getChild('path3/path1', $params, $matched)->id); // actually returns child but not last matched leaf

        $this->assertEquals('path3/', (string)$matched);

        $this->assertEquals(4, $root->getRoutesStorage()->count()); // inited!
    }

    public function testGetRouteByName()
    {
        $root = new RootRouterMock('root');
        $this->assertEquals('l4_2', $root->getRoute('name4', 'name4_2')->id);
        $this->assertEquals('l4_2', $root->getRoute(['name4', 'name4_2'])->id);


        $this->assertEquals(6, $root->getRoutesStorage()->count()); // ro to shilds deep
    }

    public function testGetRouteByName2()
    {
        $root = new RootRouterMock('root');
        $this->assertEquals('l4_2', $root->getRoute('name4_2')->id);

        $this->assertEquals(6, $root->getRoutesStorage()->count()); // ro to shilds deep
    }


    public function testUri()
    {
        $root = new RootRouterMock('root');
        $route = $root->getRoute('name4_2');


        $this->assertEquals('path3/path2/v2', (string)$root->uri($route)); // ro to shilds deep
    }

    public function testUrl()
    {
        $root = new RootRouterMock('root');


        $this->assertEquals('path3/path2/v2', (string)$root->url('path3/path2'));
    }

    public function testHasRoute()
    {
        $root = new RootRouterMock('root');


        $this->assertEquals(true, $root->hasRoute('name2'));
        $this->assertEquals(false, $root->hasRoute('name_x'));
        $this->assertEquals(false, $root->hasRoute('name4_1'));
    }
}
<?php



use project5\Web\Request;
use project5\Session;
use project5\Session\Storage\ArrayMap;
use project5\Session\Storage\Native;
use project5\Web\Url;


class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Request
     */
    private $request;

    public function setUp()
    {
        parent::setUp();

        $this->request = new Request(new Url('http://example.com/path/path2/path3.ext?x1=y1&x2=y2'), Request::METHOD_GET, ['a1'=>'b1','a2'=>'b2','a3'=>'b3','a4'=>'b4','a5'=>'b5','a6'=>'b6']);
        $this->request = $this->request->withHeader(Request::HEADER_CONTENT_TYPE, 'text/html');
        $this->request = $this->request->withHeader(Request::HEADER_USER_AGENT, 'test user agent');

    }
    public function testProtocol()
    {
        $request = new Request(new Url('http://example.com'), Request::METHOD_GET, []);

        $this->assertEquals('1.1', $request->getProtocolVersion());

        $request = new Request( new Url('http://example.com'), Request::METHOD_GET, [], 1);

        $this->assertEquals('1.0', $request->getProtocolVersion());

        $request = new Request( new Url('http://example.com'), Request::METHOD_GET, [], 0.911);

        $this->assertEquals('0.9', $request->getProtocolVersion());

        $request = new Request( new Url('http://example.com'), Request::METHOD_GET, [], 1.1);

        $this->assertEquals('1.1', $request->getProtocolVersion());

        $request = new Request( new Url('http://example.com'), Request::METHOD_GET, [], 2);

        $this->assertEquals('2.0', $request->getProtocolVersion());
    }

    public function testWithBaseUrl()
    {
        $request = new Request(new Url('http://example.com/path/path2/path3.ext'), Request::METHOD_GET, []);
        $subrequest = $request->withBaseUrl(new Url('/path/'));

        $this->assertEquals('path2/path3.ext', $subrequest->getUrlPath());
    }

    public function testGetParams()
    {
        list($a2, $ax, $a4) = $this->request->getParams('a2', 'aX', 'a4');
        $this->assertEquals('b2', $a2);
        $this->assertEquals(null, $ax);
        $this->assertEquals('b4', $a4);
    }

    public function testGetQueryParams()
    {
        $request = new Request(new Url('http://example.com/path/path2/path3.ext?x1=y1&x2=y2'), Request::METHOD_GET, ['a1' => 'b1']);

        $params = $request->getQueryParams();
        $this->assertEquals(['x1' => 'y1', 'x2' => 'y2'], $params);
    }

    public function testGetHeaderLines()
    {
        $request = new Request(new Url('http://example.com/path/path2/path3.ext?x1=y1&x2=y2'), Request::METHOD_GET, ['a1' => 'b1']);
        $request = $request->withHeader('X-Test2', 'V0')->withAddedHeader('X-Test', 'V1')->withAddedHeader('X-Test', 'V2');
        $headers = $request->getHeaderLines('X-Test');
        $this->assertEquals(['V1', 'V2'], $headers);

        $request = new Request(new Url('http://example.com/path/path2/path3.ext?x1=y1&x2=y2'), Request::METHOD_GET, ['a1' => 'b1']);
        $request = $request->withHeader('X-Test2', 'V0')->withHeader('X-Test', 'V1')->withHeader('X-Test', 'V2');
        $headers = $request->getHeaderLines('X-Test');
        $this->assertEquals(['V2'], $headers);
    }

    public function testSession()
    {
        $sid = microtime();


        $test = $this;

        $session = new Session($sid, new ArrayMap(function($id) use($test, $sid) {
            $test->assertEquals($sid, $id);
            return ['test:k2' => 'v2'];
        }, function($id, array $data) use($test, $sid) {
            $test->assertEquals($sid, $id);
            $test->assertEquals(['test:k1' => 'v1', 'test:k2' => 'v2'], $data);
        }));

        $request = $this->request;
        $this->assertNull($request->getSession());
        $this->assertFalse($request->hasSession());


        $request = $request->withSession($session);

        $this->assertInstanceOf(Session::CLASS, $request->getSession());
        $this->assertFalse($request->hasSession()); // not started yed

        $session->set('test', 'k1', 'v1');
        $this->assertTrue($request->hasSession()); // started after set
    }

    public function testBaseUrl()
    {
        $this->assertEquals('', $this->request->getBaseUrl());

        $subrequest = $this->request->withBaseUrl(new Url('/path/'));

        $this->assertEquals('/path/', $subrequest->getBaseUrl());
    }

    public function testUserAgent()
    {
        $this->assertEquals('test user agent', $this->request->getUserAgent());
    }

    public function testContentType()
    {
        $this->assertEquals('text/html', $this->request->getContentType());
    }

    public function testHasHeader()
    {
        $this->assertTrue($this->request->hasHeader(Request::HEADER_USER_AGENT));
        $this->assertTrue($this->request->hasHeader('user-agent'));
        $this->assertTrue($this->request->hasHeader('uSeR-aGeNt'));
        $this->assertFalse($this->request->hasHeader('x-uSeR-aGeNt'));
    }

    public function testGetHeaders()
    {
        $headers = $this->request->getHeaders();
        $this->assertTrue(is_array($headers));
        foreach($headers as $name => $data) {
            $this->assertTrue(is_array($data));
        }

    }

    public function testGetUri()
    {
        $this->assertInstanceOf(Url::CLASS, $this->request->getUri());
        $this->assertEquals('http://example.com/path/path2/path3.ext?x1=y1&x2=y2', $this->request->getUri());
    }

    public function testWithUri()
    {
        $request = $this->request->withUri(new Url('http://example2.com/path/path12/path13.ext?x1=y1&x2=y2'));

        $this->assertInstanceOf(Url::CLASS, $request->getUri());
        $this->assertEquals('http://example2.com/path/path12/path13.ext?x1=y1&x2=y2', $request->getUri());
    }

    /**
     * @covers wmlib\controller\Request::getSession
     * @todo   Implement testGetSession().
     */
    public function testGetSession()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::hasSession
     * @todo   Implement testHasSession().
     */
    public function testHasSession()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::withSession
     * @todo   Implement testWithSession().
     */
    public function testWithSession()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getUserAgent
     * @todo   Implement testGetUserAgent().
     */
    public function testGetUserAgent()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::setInput
     * @todo   Implement testSetInput().
     */
    public function testSetInput()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getInput
     * @todo   Implement testGetInput().
     */
    public function testGetInput()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getCookie
     * @todo   Implement testGetCookie().
     */
    public function testGetCookie()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::isXHR
     * @todo   Implement testIsXHR().
     */
    public function testIsXHR()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getCookies
     * @todo   Implement testGetCookies().
     */
    public function testGetCookies()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getBaseUrl
     * @todo   Implement testGetBaseUrl().
     */
    public function testGetBaseUrl()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getHeader
     * @todo   Implement testGetHeader().
     */
    public function testGetHeader()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getContentType
     * @todo   Implement testGetContentType().
     */
    public function testGetContentType()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getMethod
     * @todo   Implement testGetMethod().
     */
    public function testGetMethod()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getUrl
     * @todo   Implement testGetUrl().
     */
    public function testGetUrl()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getUrlPath
     * @todo   Implement testGetUrlPath().
     */
    public function testGetUrlPath()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getFullUri
     * @todo   Implement testGetFullUri().
     */
    public function testGetFullUri()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::isPost
     * @todo   Implement testIsPost().
     */
    public function testIsPost()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getParam
     * @todo   Implement testGetParam().
     */
    public function testGetParam()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::hasParam
     * @todo   Implement testHasParam().
     */
    public function testHasParam()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::isCheckModifiedSince
     * @todo   Implement testIsCheckModifiedSince().
     */
    public function testIsCheckModifiedSince()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::isCheckNotContain
     * @todo   Implement testIsCheckNotContain().
     */
    public function testIsCheckNotContain()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::__toString
     * @todo   Implement test__toString().
     */
    public function test__toString()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getServerParams
     * @todo   Implement testGetServerParams().
     */
    public function testGetServerParams()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getCookieParams
     * @todo   Implement testGetCookieParams().
     */
    public function testGetCookieParams()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }


    /**
     * @covers wmlib\controller\Request::getBodyParams
     * @todo   Implement testGetBodyParams().
     */
    public function testGetBodyParams()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getAttributes
     * @todo   Implement testGetAttributes().
     */
    public function testGetAttributes()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getAttribute
     * @todo   Implement testGetAttribute().
     */
    public function testGetAttribute()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::hasAttribute
     * @todo   Implement testHasAttribute().
     */
    public function testHasAttribute()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getBody
     * @todo   Implement testGetBody().
     */
    public function testGetBody()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::persistParam
     * @todo   Implement testPersistParam().
     */
    public function testPersistParam()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::withMethod
     * @todo   Implement testWithMethod().
     */
    public function testWithMethod()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::withProtocolVersion
     * @todo   Implement testWithProtocolVersion().
     */
    public function testWithProtocolVersion()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getProtocolVersion
     * @todo   Implement testGetProtocolVersion().
     */
    public function testGetProtocolVersion()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::withHeader
     * @todo   Implement testWithHeader().
     */
    public function testWithHeader()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::withAddedHeader
     * @todo   Implement testWithAddedHeader().
     */
    public function testWithAddedHeader()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::withoutHeader
     * @todo   Implement testWithoutHeader().
     */
    public function testWithoutHeader()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::withBody
     * @todo   Implement testWithBody().
     */
    public function testWithBody()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getRequestTarget
     * @todo   Implement testGetRequestTarget().
     */
    public function testGetRequestTarget()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::withRequestTarget
     * @todo   Implement testWithRequestTarget().
     */
    public function testWithRequestTarget()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::withCookieParams
     * @todo   Implement testWithCookieParams().
     */
    public function testWithCookieParams()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::withQueryParams
     * @todo   Implement testWithQueryParams().
     */
    public function testWithQueryParams()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::getParsedBody
     * @todo   Implement testGetParsedBody().
     */
    public function testGetParsedBody()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::withParsedBody
     * @todo   Implement testWithParsedBody().
     */
    public function testWithParsedBody()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::withAttribute
     * @todo   Implement testWithAttribute().
     */
    public function testWithAttribute()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers wmlib\controller\Request::withoutAttribute
     * @todo   Implement testWithoutAttribute().
     */
    public function testWithoutAttribute()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
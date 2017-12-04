<?php



use project5\Web\Url;


class UrlTest extends \PHPUnit_Framework_TestCase
{

    public function testWithQueryValue()
    {
        $url = new Url('http://example.com/path/path2/path3.ext?x1=y1&x2=y2');
        $url2 = $url->withQueryValue('x3', 'v3');

        $this->assertEquals('http://example.com/path/path2/path3.ext?x1=y1&x2=y2&x3=v3', $url2);
        $this->assertInstanceOf(Url::CLASS, $url2);
        $this->assertNotSame($url, $url2);
    }


    public function testWithQueryValues()
    {
        $url = new Url('http://example.com/path/path2/path3.ext?x1=y1&x2=y2');
        $url2 = $url->withQueryValues(['x2' => 'y21','x3' => 'v3', 'x4' => 'v4']);

        $this->assertEquals('http://example.com/path/path2/path3.ext?x1=y1&x2=y21&x3=v3&x4=v4', $url2);
        $this->assertInstanceOf(Url::CLASS, $url2);
        $this->assertNotSame($url, $url2);
    }

    public function testWithQuerySlug()
    {
        $url = new Url('http://example.com/path/path2/path3.ext?x1=y1&x2=y2');

        $value = ' J\'étudie le français ';

        $url2 = $url->withQuerySlug('x3', $value);

        $this->assertEquals('jetudie-le-francais', $value);

        $this->assertEquals('http://example.com/path/path2/path3.ext?x1=y1&x2=y2&x3=jetudie-le-francais', $url2);
        $this->assertInstanceOf(Url::CLASS, $url);
        $this->assertNotSame($url, $url2);
    }

    public function testWithQuerySlugCallback()
    {
        $url = new Url('http://example.com/path/path2/path3.ext?x1=y1&x2=y2');

        $value = ' J\'étudie le français ';

        $url2 = $url->withQuerySlug('x3', $value, function($candidate) {
            return ($candidate === 'jetudie-le-francais');
        });

        $this->assertNotEquals('jetudie-le-francais', $value);

        $this->assertEquals('http://example.com/path/path2/path3.ext?x1=y1&x2=y2&x3='.$value, $url2);
        $this->assertInstanceOf(Url::CLASS, $url);
        $this->assertNotSame($url, $url2);
    }
}
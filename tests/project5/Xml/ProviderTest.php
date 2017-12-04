<?php
/**
 * Created by PhpStorm.
 * User: Стас
 * Date: 21.09.14
 * Time: 19:41
 */

namespace tests\project5\Xml;

use project5\Xml\Provider;


class ProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $_dataDir;
    protected function setUp()
    {
        $this->_dataDir = __DIR__ . '/../../data/xml/';
    }


    public function testFields1()
    {
        $provider = new Provider($this->_dataDir . '1.xml', 'items/item');
        $fields = $provider->getFields();

        $this->assertCount(3, $fields);

        list($f1, $f2, $f3) = $fields;

        $this->assertEquals('a', $f1->getName());
        $this->assertEquals('b', $f2->getName());
        $this->assertEquals('c', $f3->getName());
    }

    public function testFieldsIssues()
    {
        $provider = new Provider($this->_dataDir . 'issues_raw.xml', 'issues/issue');
        $fields = $provider->getFields();

        $this->assertCount(16, $fields);
    }

    public function testIterate1()
    {
        $provider = new Provider($this->_dataDir . '1.xml', 'items/item');

        $c = 0;
        foreach($provider->iterate() as $entity) {
            $c++;
        }
        $this->assertEquals(min($provider->getPagerLimit(), 2), $c);
    }

    public function testIterateIssues()
    {
        $provider = new Provider($this->_dataDir . 'issues.xml', 'issues/issue');
        $provider->setPagerLimit(8);
        $provider->setPagerOffset(0);

        $ids = [];
        $expected = [
            '12559',
            '12558',
            '12557',
            '12556',
            '12553',
            '12551',
            '12548',
            '12547',
        ];
        foreach($provider->iterate() as $entity) {
            $ids[] = $entity['id'];
        }
        $next = $provider->getNextPageOffset();
        $this->assertEquals(8, $next);
        $this->assertCount(8, $ids);
        $this->assertEquals($expected, $ids);

        $provider->setPagerOffset(8);

        $expected = [
            '12544',
            '12543',
            '12542',
            '12541',
            '12540',
            '12538',
            '12535',
            '12532',
        ];
        $ids = [];
        foreach($provider->iterate() as $entity) {
            $ids[] = $entity['id'];
        }
        $next = $provider->getNextPageOffset();
        $this->assertEquals(16, $next);
        $this->assertCount(8, $ids);
        $this->assertEquals($expected, $ids);
    }


}
 
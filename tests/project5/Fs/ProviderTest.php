<?php
/**
 * Created by PhpStorm.
 * User: Стас
 * Date: 21.09.14
 * Time: 19:41
 */

namespace tests\project5\Fs;



use project5\Fs\File;
use project5\Fs\Provider;

class ProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $_dataDir;
    protected function setUp()
    {
        $this->_dataDir = __DIR__ . '/../../data/';
    }


    public function testIterate()
    {
        $provider = Provider::Scan($this->_dataDir);

        foreach($provider->iterate() as $item)
        {
            $this->assertEquals(File::TYPE_FILE, $item['type']);
        }
    }


}
 
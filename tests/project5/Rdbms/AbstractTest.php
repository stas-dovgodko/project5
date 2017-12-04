<?php
/**
 * Created by PhpStorm.
 * User: Стас
 * Date: 21.09.14
 * Time: 19:41
 */

namespace tests\project5\Rdbms;


use PHPUnit_Framework_Exception;
use PHPUnit_Util_InvalidArgumentHelper;

use project5\Autoload;
use project5\Rdbms\Provider;
use Doctrine\Tests\Mocks\DriverMock;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\MySqlSchemaManager;

abstract class AbstractTest extends \PHPUnit_Extensions_Database_TestCase
{
    /**
     * @var Provider
     */
    private $_view;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_connection;

    private $_pdo;
    private $_phpunitconn;

    private $_sqls = [];

    protected function tearDown()
    {
        if ($this->_sqls) {
            rsort($this->_sqls);

            foreach($this->_sqls as $name) {
                $this->_sql($name . '/_teardown', true);
            }
        }

        parent::tearDown();
    }

    protected function getSetUpOperation() {
        return new \PHPUnit_Extensions_Database_Operation_Composite(array(
            \PHPUnit_Extensions_Database_Operation_Factory::DELETE_ALL(),
            \PHPUnit_Extensions_Database_Operation_Factory::INSERT()
        ));
    }

    protected function _getPdo()
    {
        if ($this->_pdo === null) {
            $this->_pdo = new \PDO( $GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD'] );
        }

        return $this->_pdo;
    }

    final public function getConnection()
    {
        if ($this->_phpunitconn === null) {
            $this->_phpunitconn = $this->createDefaultDBConnection($this->_getPdo(), $GLOBALS['DB_DBNAME']);
        }

        return $this->_phpunitconn;
    }

    protected function sql($name)
    {
        $this->_sqls[] = $name;

        $this->_sql($name . '/_setup');
    }

    protected function _sql($name, $reverse = false)
    {
        $dir = __DIR__ . '/../../data/rdbms/'.$name.'/';

        assert('is_dir($dir)');

        $list = [];
        foreach (glob($dir . "*.sql") as $filename) {
            $list[] = $filename;
        }

        if ($reverse) rsort($list);
        else sort($list);

        foreach($list as $filename) {
            $this->_getPdo()->exec(file_get_contents($filename));
        }
    }

    protected function setUp()
    {
        if (!extension_loaded('pdo_mysql')) $this->markTestSkipped();
        elseif (!isset($GLOBALS['DB_DSN'])) $this->markTestSkipped();

        $this->_sqls = [];

        $config = new \Doctrine\DBAL\Configuration();
        $this->_connection = \Doctrine\DBAL\DriverManager::getConnection(['pdo' => $this->_getPdo()], $config);

        $this->sql('_mysql');






        parent::setUp();
    }


    /**
     * Returns the test dataset.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/../../data/rdbms/dataset.xml');
    }
}
 
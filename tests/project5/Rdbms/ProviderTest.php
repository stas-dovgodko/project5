<?php
/**
 * Created by PhpStorm.
 * User: Стас
 * Date: 21.09.14
 * Time: 19:41
 */

namespace tests\project5\Rdbms;

require_once __DIR__ . '/AbstractTest.php';


use project5\Rdbms\Provider;
use project5\Rdbms\Entity;

use Doctrine\DBAL\Query\QueryBuilder;

class ProviderTest extends AbstractTest
{



    public function testGetFields()
    {
        $query_builder = new QueryBuilder($this->_connection);
        $query_builder->from('student', 's');
        $query_builder->select('s.fio, s.gender');

        $fields = (new Provider($query_builder))->getFields();
        $this->assertCount(2, $fields);
        list($a_field, $b_field) = $fields;

        $this->assertEquals('fio', $a_field->getName());
        $this->assertEquals('gender', $b_field->getName());
    }

    public function testGetFields2()
    {
        $query_builder = new QueryBuilder($this->_connection);
        $query_builder->from('student', 's');
        $query_builder->select('s.fio, s.gender, CONCAT(s.fio, s.gender) as d');

        $fields = (new Provider($query_builder))->getFields();
        $this->assertCount(3, $fields);
        list($a_field, $b_field, $d_field) = $fields;

        $this->assertEquals('fio', $a_field->getName());
        $this->assertEquals('gender', $b_field->getName());
        $this->assertEquals('d', $d_field->getName());
    }

    public function testGetFieldsRelation()
    {
        $query_builder = new QueryBuilder($this->_connection);
        $query_builder->from('student', 's');
        $query_builder->select('s.fio, s.gender, s.degree, s.letter');

        $view = new Provider($query_builder);
        $fields = $view->getFields();
        $this->assertCount(3, $fields);

        list($f1, $f2, $relation) = $fields;

        $this->assertEquals('fio', $f1->getName());
        $this->assertEquals('gender', $f2->getName());
        $this->assertEquals('class', $relation->getName());

        $this->assertInstanceOf(\project5\Rdbms\Field\Relation::CLASS, $relation); /** @var $relation \project5\Rdbms\Field\Relation */

        $c = 0;
        foreach($view->iterate() as $row) {
            $c++;
        }
        $this->assertEquals($c, $view->getCount());
    }

    public function testCount()
    {
        $query_builder = new QueryBuilder($this->_connection);
        $query_builder->from('student', 's');
        $query_builder->select('s.fio, s.gender, s.degree, s.letter');

        $view = new Provider($query_builder);
        $this->assertEquals(3, $view->getCount());
    }

    public function testCountWithWhere()
    {
        $query_builder = new QueryBuilder($this->_connection);
        $query_builder->from('student', 's');
        $query_builder->select('s.fio, s.gender, s.degree, s.letter');
        $query_builder->where('s.degree = 1');

        $view = new Provider($query_builder);
        $this->assertEquals(2, $view->getCount());
    }

    public function testSave()
    {
        $query_builder = new QueryBuilder($this->_connection);
        $query_builder->from('student', 's');
        $query_builder->select('s.fio, s.gender, s.degree, s.letter');

        $view = new Provider($query_builder);

        $entity = new Entity();
        $entity->fio = 'Test save';
        $entity->gender = 1;
        $entity->degree = 1;
        $entity->letter = 'A';

        $this->assertTrue($view->saveEntity($entity));

        $this->assertEquals(4, $view->getCount());
    }

    public function testMissedSave()
    {
        $query_builder = new QueryBuilder($this->_connection);
        $query_builder->from('student', 's');
        $query_builder->select('s.fio, s.gender,s.letter');

        $view = new Provider($query_builder);

        $entity = new Entity();
        $entity->fio = 'Test save';
        $entity->gender = 1;
        $entity->letter = 'A';

        $this->setExpectedException(\project5\Exception\Unsupported::CLASS);
        $view->saveEntity($entity);
    }
}
 
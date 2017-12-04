<?php
/**
 * Created by PhpStorm.
 * User: Стас
 * Date: 20.07.14
 * Time: 15:49
 */
namespace project5\Ldap;

use project5\Provider\Entity;
use project5\Provider\IEntity;
use project5\IProvider;

use project5\Provider\ICanBePaged;
use project5\Provider\ICanBeFiltered;
use project5\Provider\Methods;

use project5\Provider\Field\String;
use project5\Provider\IComparer;

class Provider implements IProvider, ICanBePaged, ICanBeFiltered
{
    use Methods;

    /**
     * @var IDriver
     */
    private $_driver;

    private $_filter = '';

    private $_fieldNames;
    private $_fields;


    public function __construct(IDriver $driver)
    {
        $this->_driver = $driver;
    }

    public function checkPassword($password, $attribute)
    {
        if (preg_match('/^\{(.+)\}(.*)$/', $attribute, $matches) && isset($matches[1], $matches[2])) {
            $alg = $matches[1]; $hash = $matches[2];
        } else {
            $alg = ''; $hash = $attribute;
        }

        switch($alg) {
            case 'MD5':
                return (md5($password) == $hash);
            default:
                return ($password == $hash);
        }
        return false;
    }

    public function addFieldName($name, $title = '')
    {
        $this->_fieldNames[$name] = $title ? $title : $name;
        $this->_fields = null;
    }



    /**
     * List of IField's
     *
     * @param callable $filter
     * @return \Traversable
     */
    public function getFields(callable $filter = null)
    {
        if ($this->_fields) {
            return $this->_fields;
        }


        $field = new String('dn');
        $field->setSurrorate(true);

        $this->_fields = [$field];

        foreach($this->_fieldNames as $name => $title) {
            $field = new String(strtolower($name), $title);

            $this->_fields[] = $field;
        }

        return $this->_fields;
    }

    /**
     * @param $uid
     * @return IEntity
     */
    public function findByUid($uid)
    {
        $matrix = \ldap_explode_dn($uid, 0); $base_matrix = \ldap_explode_dn($this->_driver->getDn(), 0);

        $matrix = array_diff($matrix, $base_matrix);
        if (sizeof($matrix) > 1 && isset($matrix['count'])) {
            unset($matrix['count']);
            $filter = '(&('.implode(')(', $matrix).'))';
        } else {
            throw new \Exception('Can\'t build filter');
        }

        $justthese = [];
        foreach($this->getFields() as $field) $justthese[] = $field->getName();

        $this->_driver->filter($filter, $justthese);

        $data = null; $index = null;
        foreach($this->_driver->filter($filter, $justthese) as $ds => $row) {
            if ($data === null) {
                $index = $ds;
                $data = $row;
            } else {
                throw new \Exception('Found more than one records');
            }
        }

        if ($data !== null) {
            $entity = $this->createEntity();
            if ($index !== null) $entity->setUid($index);
            $entity->exchangeArray($data);

            return $entity;
        } else {
            throw new \Exception('Not found');
        }
    }

    /**
     * IEntity's
     *
     * @return \Traversable
     */
    public function iterate()
    {
        $justthese = [];
        foreach($this->getFields() as $field) $justthese[] = $field->getName();

        foreach($this->_driver->filter($this->_filter ? $this->_filter : 'objectClass=*', $justthese) as $index => $row) {
            $entity = $this->createEntity();
            if ($index !== null) $entity->setUid($index);
            $entity->exchangeArray($row);

            yield $entity;
        }
    }

    /**
     * Create empty entity
     *
     * @return Entity
     */
    public function createEntity()
    {
        return new Entity();
    }

    /**
     * @param string $offser
     * @return void
     */
    public function setPagerOffset($offser)
    {
        // TODO: Implement setPagerOffset() method.
    }

    /**
     * @param int $limit
     * @return void
     */
    public function setPagerLimit($limit)
    {
        // TODO: Implement setPagerLimit() method.
    }

    /**
     * @return int
     */
    public function getPagerLimit()
    {
        return 10;
    }

    /**
     * @return int
     */
    public function getPagerOffset()
    {
        return 0;
    }

    /**
     * @return bool|null
     */
    public function hasNextPage()
    {
        return false;
    }

    /**
     * @return bool|null
     */
    public function hasPrevPage()
    {
        return false;
    }

    /**
     * @return string|null
     */
    public function getNextPageOffset()
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function getPrevPageOffset()
    {
        return null;
    }

    /**
     * @param IEntity $entity
     * @return void
     */
    /*public function filterBy(IEntity $entity)
    {
        $parts = array();
        foreach($this->getFields() as $field) {
            $name = $field->getName();
            $value = $field->get($entity);

            if ($value) $parts[] = sprintf('(%s=%s)', $name, $value);
        }

        if ($parts) $this->_filter = '(&'.implode('', $parts).')';
    }*/

    /**
     * @param callable $filter
     * @return void
     */
    public function setFilter(callable $filter)
    {

    }

    /**
     * @param $name
     * @param $value
     * @param int $compare
     * @return ICanBeFiltered
     */
    public function filter(IField $field, IComparer $comparer)
    {
        return $this;
    }

    /**
     * @return ICanBeFiltered
     */
    public function resetFilter()
    {


        return $this;
    }


}
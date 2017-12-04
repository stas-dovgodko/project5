<?php
namespace project5\Rdbms\Field;

use \Doctrine\DBAL\Connection;

use project5\Provider\IField,
    project5\Provider\IEntity;

use project5\Rdbms\Provider;
use project5\Provider\Field\Relation as SuperRelation;

class Relation extends SuperRelation
{
    /**
     * @var IField|null
     */
    private $_titleField;

    /**
     * @var \project5\Rdbms\Connection
     */
    private $_connection;

    /**
     * @var string
     */
    private $_localTableAlias;
    private $_foreignTableName;
    private $_relations = [];

    private $_titleAlias;

    public function __construct($name, Connection $connection, $foreignTable, $localTableAlias, array $relations, IField $titleField = null, $title = null)
    {
        $this->_name = $name;
        $this->_title = $title ? $title : $foreignTable;

        $this->_titleField = $titleField;


        $this->_connection = $connection;
        $this->_relations = $relations;
        $this->_foreignTableName = $foreignTable;
        $this->_localTableAlias = $localTableAlias;

        $this->_titleField = $titleField;


    }

    /**
     * @return string
     */
    public function getLocalTableAlias()
    {
        return $this->_localTableAlias;
    }



    public function getForeignEntityString(IEntity $entity)
    {
        $value = self::_GetEntityFieldValue($entity, $this->getUniqueTitleAlias(), $found);

        if ($found) {
            return (string)$value;
        } else return parent::getForeignEntityString($entity);
    }

    public function getForeignField()
    {
        return $this->getTitleField();
    }

    /**
     * @return array
     */
    public function getRelations()
    {
        return $this->_relations;
    }

    /**
     * @return \project5\IProvider|Provider
     */
    public function getForeignProvider()
    {
        $query_builder = $this->_connection->createQueryBuilder()->from($this->getForeignTableName(), 'f');

        foreach ($this->_relations as $field_name) {
            $select[] = 'f.' . $field_name;
        }

        if ($title_field = $this->getTitleField()) {
            $select[] = 'f.' . $title_field->getName();
        }

        $query_builder->select(implode(', ', $select));

        return new Provider($query_builder);
    }


    public function getUniqueTitleAlias()
    {
        if (!$this->_titleAlias) {
            static $i;

            if (!$i) $i=1; $i++;



            $this->_titleAlias = 'f'.substr(md5(spl_object_hash($this)),0, 3).$i;
            if ($title_field = $this->getTitleField()) {
                $this->_titleAlias .= '_'.$title_field->getName();
            }
        }
        return $this->_titleAlias;
    }

    /**
     * @return string
     */
    public function getForeignTableName()
    {
       return $this->_foreignTableName;
    }

    public function getTitleField()
    {
        if ($this->_titleField) {
            return $this->_titleField;
        } else {

            $query_builder = $this->_connection->createQueryBuilder()->from($this->getForeignTableName(), 'f')->select('f.*');

            $fields = (new Provider($query_builder))->getFields();

            foreach($fields as $field) {
                if (!in_array($field->getName(), $this->_relations)) return $field;
            }
        }
    }



    /**
     * @param IEntity $object
     * @return mixed[]
     */
    public function getLocal(IEntity $object)
    {
        $value = [];
        foreach($this->_relations as $local_name => $foreign_name) {

            $found = false;
            $value[$local_name] = self::_GetEntityFieldValue($object, $local_name, $found);
        }
        return $value;
    }


    public function set(IEntity $object, $value)
    {
        if (!is_array($value)) $value = [$value];

        reset($value);
        foreach($this->_relations as $local_name => $foreign_name) {

            $local_value = current($value);

            self::_SetEntityFieldValue($object, $local_name, $local_value);
            next($value);
        }
    }


    /**
     * @param IEntity $object
     * @return mixed|null
     */
    public function get(IEntity $object)
    {
        list($get, ) = $this->_callbacks;

        if ($get) {
            $value = call_user_func_array($get, array($object));
        } else {

            $value = $this->getLocal($object);
            if (sizeof($value) === 1) {
                $value = array_values($value);
                return $value[0];
            }
        }
        return $value;
    }
}
<?php
namespace project5\Propel\Field;

use project5\Propel\Provider as PropelProvider;

use project5\Provider\IField,
    project5\Provider\IEntity;


use project5\Provider\Field\Relation as SuperRelation;

class Relation extends SuperRelation
{

    private $_relations = [];

    private $_foreignProvider;

    public function __construct($name, PropelProvider $foreignProvider, array $relations, IField $titleField = null, $title = null)
    {
        $this->_name = $name;
        $this->_foreignProvider = $foreignProvider;
        $this->_relations = $relations;

        parent::__construct($name, array_keys($relations), $foreignProvider, null, $title);
    }


}
<?php
namespace project5\Rdbms\Field;

use project5\Provider\Field\String as StringField;



class String extends StringField
{
    private $_tableAlias;
    public function __construct($tableAlias, $name, $title = null)
    {
        parent::__construct($name, $title);
        $this->_tableAlias = $tableAlias;
    }

    public function getTableAlias()
    {
        return $this->_tableAlias;
    }
}
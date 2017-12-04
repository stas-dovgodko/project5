<?php
namespace project5\Crud\Plot;

use project5\IProvider;
use project5\Provider\IField;
use project5\Provider\Field\Relation;

class Pie
{
    public $title;

    private $_provider;
    private $_titleField, $_valueField;
    public function __construct(IProvider $provider, IField $titleField, IField $valueField)
    {
        $this->_provider = $provider;
        $this->_titleField = $titleField;
        $this->_valueField = $valueField;
    }

    public function getData()
    {
        $data = [];
        foreach($this->_provider->iterate() as $entity) {
            $data[] = [
                'label' => ($this->_titleField instanceof Relation) ? $this->_titleField->getForeignEntityString($entity) :$this->_titleField->get($entity),
                'value' => $this->_valueField->get($entity),
            ];
        }
        return $data;
    }
}
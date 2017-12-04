<?php
namespace project5\Crud\Plot;

use project5\IProvider;
use project5\Provider\IField;
use project5\Provider\Field\Relation;

class Bar
{
    public $title;

    private $_provider;
    private $_titleField;
    private $_valueFields;

    public function __construct(IProvider $provider, IField $titleField, $valueFields = [])
    {
        $this->_provider = $provider;
        $this->_titleField = $titleField;
        $this->_valueFields = $valueFields;
    }

    public function getData()
    {
        $data_fields = $this->_valueFields ? $this->_valueFields : $this->_provider->getFields();

        $data = [];
        foreach($this->_provider->iterate() as $entity) {
            $row = [];
            $row[$this->_titleField->getName()] = 1*(($this->_titleField instanceof Relation) ? $this->_titleField->getForeignEntityString($entity) :$this->_titleField->get($entity));
            foreach($data_fields as $field) {
                $row[$field->getName()] = 1*(($field instanceof Relation) ? $field->getForeignEntityString($entity) :$field->get($entity));
            }
            $data[] = $row;
        }
        return $data;
    }

    public function getXkey()
    {
        return $this->_titleField->getName();
    }

    public function getYkeys()
    {
        $data_fields = $this->_valueFields ? $this->_valueFields : $this->_provider->getFields();

        $names = [];
        foreach($data_fields as $field) {
            if ($field !== $this->_titleField) $names[] = $field->getName();
        }

        return $names;
    }

    public function getLabels()
    {
        $data_fields = $this->_valueFields ? $this->_valueFields : $this->_provider->getFields();

        $titles = [];
        foreach($data_fields as $field) {
            if ($field !== $this->_titleField) $titles[] = $field->getTitle();
        }

        return $titles;
    }
}
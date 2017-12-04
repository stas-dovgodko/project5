<?php
namespace project5\Crud\Decorator;

use project5\Crud\Decorator;
use project5\Form\Checkbox;
use project5\Provider\IEntity;
use project5\Provider\IField;

class Switcher extends Decorator
{
    public function __construct(IField $field)
    {
        parent::__construct($field);
    }

    public function isOn(IEntity $data)
    {
        $value = $this->field->get($data);

        return (bool)$value;
    }

    protected function buildFormField()
    {
        $field = new Checkbox($this->field->getName(), $this->field->getTitle());

        return $field;
    }
}
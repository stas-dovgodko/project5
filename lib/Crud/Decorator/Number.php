<?php
namespace project5\Crud\Decorator;

use project5\Crud\Decorator;
use project5\Form\Date;
use project5\Form\Field;


class Number extends Decorator
{


    protected function buildFormField()
    {
        $formField = new Field($this->field->getName(), $this->field->getTitle());
        $formField->setType(Field::TYPE_NUMBER);


        return $formField;
    }
}
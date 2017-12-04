<?php
namespace project5\Crud\Decorator;

use project5\Crud\Decorator;
use project5\Form\Date;


class Datetime extends Decorator
{


    protected function buildFormField()
    {
        $formField = new Date($this->field->getName(), $this->field->getTitle());


        return $formField;
    }
}
<?php
namespace project5\Crud\Decorator;

use project5\Crud\Decorator;
use project5\Form\Password as FieldPassword;
use project5\Provider\IEntity;


class Password extends Decorator {

    protected function buildFormField()
    {
        $field = new FieldPassword($this->field->getName(), $this->field->getTitle());

        return $field;
    }


    

}
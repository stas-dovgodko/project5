<?php
namespace project5\Crud\Decorator;

use project5\Crud\Decorator;
use project5\Form\Richedit as FieldRichedit;

class Richedit extends Clob {

    protected function buildFormField()
    {
        $field = new FieldRichedit($this->field->getName(), $this->field->getTitle());

        return $field;
    }
}
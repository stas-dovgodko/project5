<?php
namespace project5\Crud\Decorator;

use project5\Crud\Decorator;
use project5\Form\Upload as FieldUpload;


class Readonly extends Decorator {

    protected function buildFormField()
    {
        $field = new FieldUpload($this->field->getName(), $this->handler);

        return $field;
    }
}
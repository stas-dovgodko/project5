<?php
namespace project5\Crud\Decorator;

use project5\Crud\Decorator;
use project5\Form\Textarea;
use project5\Provider\IField;


class Clob extends Decorator {

    public function __construct(IField $field)
    {
        parent::__construct($field);

        $this->setHtmlCallback(function() {
            return '<span class="glyphicon glyphicon-file" ></span>';
        });
    }

    protected function buildFormField()
    {
        $field = new Textarea($this->field->getName(), $this->field->getTitle());

        return $field;
    }
}
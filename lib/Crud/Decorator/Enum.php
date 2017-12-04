<?php
namespace project5\Crud\Decorator;

use project5\Crud\Decorator;
use project5\Form\Select;
use project5\Provider\IEntity;
use project5\Provider\IField;


class Enum extends Decorator
{
    protected $options;

    protected $formField;

    public function __construct(IField $field, array $options)
    {
        parent::__construct($field);

        $this->options = $options;
    }

    public function getValueTitle(IEntity $data)
    {
        $value = $this->field->get($data);

        return isset($this->options[$value]) ? $this->options[$value] : null;
    }

    protected function buildFormField()
    {
        $formField = new Select($this->field->getName(), $this->options, $this->field->getTitle());


        return $formField;
    }
}
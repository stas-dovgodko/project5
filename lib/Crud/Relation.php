<?php
namespace project5\Crud;

use project5\Form\Field;
use project5\Provider\ICanBeCounted;
use project5\Provider\ICanBeFiltered;
use project5\Provider\ICanBePaged;
use project5\Provider\ICanSearch;
use project5\Provider\IEntity;

class Relation extends Field
{

    protected $field;

    public function __construct(\project5\Provider\Field\Relation $field, $title = null)
    {
        parent::__construct($field->getName(), $title ? $title : $field->getTitle());

        $this->field = $field;
    }

    public function getEntityTitle(IEntity $entity)
    {
        return (string)$entity;
        if ($rel_field = $this->field->getForeignField()) {
            return (string)$rel_field->get($entity);
        } else {
            return (string)$entity;
        }
    }

    public function getValueTitle($value)
    {
        $entity = $this->field->getForeignProvider()->findByUid($value);

        return (string)$this->getEntityTitle($entity);
    }

    public function getData(Page $crud, $page = 1, $page_limit = 10, $search = null)
    {
        $relation = $this->field;

        $rel_provider = $relation->getForeignProvider();
        if ($search) {
            if ($rel_provider instanceof ICanSearch) {
                $rel_provider->setSearchTerm($search);
            }
        }

        $data = array('results' => array());

        if ($rel_provider instanceof ICanBePaged) {
            $rel_provider->setPagerOffset(($page - 1) * $page_limit);
            $rel_provider->setPagerLimit($page_limit);
            $data['more'] = $rel_provider->hasNextPage();
        }


        foreach ($rel_provider->iterate() as $object) {
            /** @var $object IEntity */
            $data['results'][] = array('id' => $crud->encodeUid($object), 'text' => $this->getEntityTitle($object));
        }

        if (($rel_provider instanceof ICanBeCounted) && $rel_provider->canBeCounted()) {
            $data['total'] = $rel_provider->getCount();

        }

        return $data;
    }
}
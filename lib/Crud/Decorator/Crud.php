<?php
namespace project5\Crud\Decorator;

use project5\Crud\Decorator;
use project5\Crud\Page;

use project5\Provider\Field\Relation;
use project5\Provider\IEntity;

class Crud extends Decorator {

    /**
     * @var Page
     */
    protected $page;

    public function __construct(Relation $field, Page $page = null)
    {
        parent::__construct($field);

        $this->page = $page;
        $this->title = $page ? $page->getTitle() : $field->getTitle();
    }

    /**
     * @return Page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param Page $page
     */
    public function setPage($page)
    {
        $this->page = $page;
    }



    public function getValueTitle(IEntity $data)
    {
        return $this->field->getForeignEntityString($data);
    }

    public function getUrl(IEntity $data)
    {
        $uid = $this->field->get($data);

        return $this->page ? $this->page->urlView(['id' => $this->page->encodeUid($uid)]) : null;
    }

    public function hasUrl()
    {
        return ($this->page !== null);
    }



    protected function buildFormField()
    {
        return new \project5\Crud\Relation($this->field, $this->getTitle());
    }
}
<?php
namespace project5\Crud\Decorator;

use project5\Crud\Decorator;
use project5\DI\Container;
use project5\Provider\IEntity;
use project5\Provider\IField;
use project5\Upload\Handler;
use project5\Form\Upload as FieldUpload;
use project5\Upload\IStorage;
use StasDovgodko\Uri;

class Upload extends Decorator {

    /**
     * @var Handler
     */
    protected $handler;

    public function __construct(IField $field, Handler $handler)
    {
        parent::__construct($field);

        $this->handler = $handler;
    }

    public static function ImageUpload(IField $field, Container $container)
    {
        $decorator = new \project5\Crud\Decorator\Upload($field, $uploader = $container->get('upload.handler.images'));

        $decorator->setHtmlTemplate(
            '<a href="{{decorator.getUrl(entity)}}" target="_blank" >
                <span class="glyphicon glyphicon-camera" aria-hidden="true"></span>
                {% if decorator.getSize(entity) %} <span class="badge">{{decorator.getSize(entity)|size}}</span>{% endif %}
            </a>',
            $container->get('template.render.twig')
        );

        return $decorator;
    }

    public function getSize(IEntity $data)
    {
        $storage = $this->handler->getStorage();
        if ($storage->isSupportedProperty(IStorage::PROPERTY_SIZE)) {

            $uri = new Uri($this->field->get($data));

            return $storage->getProperty($uri, IStorage::PROPERTY_SIZE);
        } else {
            return null;
        }
    }

    public function hasUrl()
    {
        return true;
    }

    public function getUrl(IEntity $data)
    {
        $uri = new Uri($this->field->get($data));
        return $this->handler->getStorage()->uri($uri);
    }

    protected function buildFormField()
    {
        $field = new FieldUpload($this->field->getName(), $this->handler, $this->getTitle());

        return $field;
    }
}
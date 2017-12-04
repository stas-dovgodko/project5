<?php
namespace project5\Provider;

use project5\IProvider;
use project5\Provider\Compare\E\UnsupportedException;
use project5\Provider\Entity;
use project5\Provider\Field\String;
use project5\Provider\ICanBeFiltered;
use project5\Provider\ICanBePaged;
use project5\Provider\IComparer;
use project5\Provider\IEntity;
use project5\Provider\IField;
use project5\Provider\Methods;
use project5\Provider\SyncIteratorMethods;

class Map implements IProvider, ICanBeCounted {
    use Methods, SyncIteratorMethods;

    protected $data;
    protected $fields;
    protected $uidCallback;

    public function __construct($data, callable $uidCallback, array $map = [])
    {
        if (($data instanceof \Iterator) || is_array($data)) {
            $this->data = $data;
        } else {
            throw new \InvalidArgumentException('Data should be array or iterator implementation');
        }

        $this->fields = [];
        foreach($map as $property_name => $callbackOrFieldOrString)
        {
            if ($callbackOrFieldOrString instanceof IField) {
                $field = clone $callbackOrFieldOrString;
            } elseif (is_callable($callbackOrFieldOrString)) {
                $field = new String($property_name);
                $field->setCallback($callbackOrFieldOrString);
                $field->setSurrorate(true);
            } elseif (is_string($callbackOrFieldOrString)) {
                $field = new String($property_name);
                $field->setCallback(
                    function (IEntity $entity) use($callbackOrFieldOrString) {
                        return $entity->offsetGet($callbackOrFieldOrString);
                    },
                    function (IEntity $entity, $value) use($callbackOrFieldOrString) {
                        $entity->offsetSet($callbackOrFieldOrString, $value);
                    }
                );
            } else {
                throw new \InvalidArgumentException('Unexpected Map element');
            }

            $this->fields[$property_name] = $field;
        }
        $this->uidCallback = $uidCallback;
    }

    public function canBeCounted()
    {
        return true;
    }

    /**
     * List of IField's
     *
     * @param callable $filter
     * @return \Traversable|IField[]
     */
    public function getFields(callable $filter = null)
    {
        return array_values($this->fields);
    }

    /**
     * Create empty entity
     *
     * @return IEntity
     */
    public function createEntity()
    {
        return new Entity();
    }

    /**
     * @param $cursor
     * @param array $extra
     * @return int
     */
    protected function _reset($cursor, array $extra = [])
    {
        reset($this->data);

        return 0;
    }

    protected function _next(array &$extra)
    {
        $row = current($this->data);
        if ($row !== false) {
            $entity = $this->createEntity();
            $entity->setUid(call_user_func($this->uidCallback, key($this->data), $row));

            foreach($this->fields as $property_name => $field) {
                /** @var $field IField */
                if (is_string($property_name)) {
                    if (isset($row[$property_name])) {
                        $field->set($entity, $row[$property_name]);
                    }
                } else {
                    $field->set($entity, $row);
                }
            }

            next($this->data);
            return $entity;
        }
    }
}
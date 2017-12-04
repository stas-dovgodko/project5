<?php
/**
 * Created by PhpStorm.
 * User: Стас
 * Date: 30.07.14
 * Time: 22:39
 */
namespace project5\Rdbms;

use project5\Provider\Entity as SuperEntity;

class Entity extends SuperEntity
{
    public function __construct()
    {

    }

    public function __set($name, $value)
    {
        parent::offsetSet($name, $value);
    }

    public function __toString()
    {
        if ($this->offsetExists('title')) return (string)$this->offsetGet('title');
        elseif ($this->offsetExists('name')) return (string)$this->offsetGet('name');
        else return var_export($this->getUid(), true);
    }
}
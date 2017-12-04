<?php
namespace project5\Propel;

use project5\Provider\IEntity;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Map\TableMap;

class Entity implements IEntity
{
    /**
     * @var \Propel\Runtime\Map\TableMap
     */
    private $_map;

    /**
     * @var \Propel\Runtime\ActiveRecord\ActiveRecordInterface
     */
    private $_record;

    public function __construct(TableMap $map, ActiveRecordInterface $record)
    {
        $this->_record = $record;
        $this->_map = $map;
    }

    /**
     * @return ActiveRecordInterface
     */
    public function getRecord()
    {
        return $this->_record;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        try {
            $column_name = TableMap::translateFieldnameForClass(get_class($this->_record), $offset, TableMap::TYPE_FIELDNAME, TableMap::TYPE_COLNAME);

            return $this->_map->hasColumn($column_name);
        } catch (\Propel\Runtime\Exception\PropelException $e) {
            return false;
        }
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        $pos = TableMap::translateFieldnameForClass(get_class($this->_record), $offset, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM);

        return $this->_record->getByPosition($pos);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $pos = TableMap::translateFieldnameForClass(get_class($this->_record), $offset, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM);

        return $this->_record->setByPosition($pos, $value);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        $column_name = TableMap::translateFieldnameForClass(get_class($this->_record), $offset, TableMap::TYPE_FIELDNAME, TableMap::TYPE_COLNAME);
        $pos = TableMap::translateFieldnameForClass(get_class($this->_record), $offset, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM);

        $column = $this->_map->getColumn($column_name);

        if ($value = $column->getDefaultValue()) {
            $this->_record->setByPosition($pos, $value);
        } else {
            $this->_record->setByPosition($pos, null);
        }

    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return $this->_record->exportTo('JSON');
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    public function unserialize($serialized)
    {
        $this->_record->importFrom('JSON', $serialized);
    }

    /**
     * @return string
     */
    public function getUid()
    {
        return $this->_record->getPrimaryKey();
    }

    public function save(ConnectionInterface $connection)
    {
        return $this->_record->save($connection);
    }

    public function __toString(){
        return (string)$this->_record;
    }
}
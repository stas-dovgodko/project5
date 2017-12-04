<?php
namespace project5\Propel;

use project5\IProvider;
use project5\Propel\Field\Relation;
use project5\Provider\Compare\E\UnsupportedException;

use project5\Provider\Compare\Equal as EqualComparer;
use project5\Provider\Compare\Range as RangeComparer;

use project5\Provider\Field\Boolean;
use project5\Provider\Field\Date;
use project5\Provider\Field\Datetime;
use project5\Provider\Field\Number;
use project5\Provider\Field\String;
use project5\Provider\ICanBeCounted;
use project5\Provider\ICanBeFiltered;
use project5\Provider\ICanBePaged;
use project5\Provider\ICanBeSorted;
use project5\Provider\ICanDelete;
use project5\Provider\ICanSave;
use project5\Provider\ICanSearch;
use project5\Provider\IComparer;
use project5\Provider\IEntity;
use project5\Provider\IField;
use project5\Provider\PagerMethods;
use project5\Provider\Sort\Asc;
use project5\Provider\Sort\Desc;
use project5\Provider\Sort\ISorter;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Propel;

class Provider implements IProvider, ICanBeCounted, ICanBePaged, ICanBeFiltered, ICanSave, ICanDelete, ICanBeSorted, ICanSearch
{
    use PagerMethods;

    /**
     * @var ModelCriteria
     */
    private $_model;

    /**
     * @var ModelCriteria|null
     */
    private $filteredModel;

    private $filteredCount;

    /**
     * @var ConnectionInterface|null
     */
    private $_connection = null;

    /**
     * @var ConnectionInterface|null
     *
     */
    private $_readConnection = null;

    /**
     * Context filters
     *
     * @var array
     */
    private $_filters = [];

    protected $readOnly;

    protected $sorting = [];

    protected $search;

    /**
     * @param ModelCriteria $model
     */
    public function __construct(ModelCriteria $model, ConnectionInterface $connection = null, $readOnly = false) {
        $this->_model = $model;
        $this->readOnly = $readOnly;

        if ($connection) {
            $this->_connection = $connection;
        } else {
            $this->_connection = Propel::getServiceContainer()->getReadConnection($model->getDbName());
        }
    }

    /**
     * @param null|\Propel\Runtime\Connection\ConnectionInterface $connection
     */
    public function setConnection($connection)
    {
        $this->_connection = $connection;
    }

    /**
     * @return null|\Propel\Runtime\Connection\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    /**
     * @param null|\Propel\Runtime\Connection\ConnectionInterface $readConnection
     */
    public function setReadConnection($readConnection)
    {
        $this->_readConnection = $readConnection;
    }

    /**
     * @return null|\Propel\Runtime\Connection\ConnectionInterface
     */
    public function getReadConnection()
    {
        return $this->_readConnection ? $this->_readConnection : $this->getConnection();
    }



    /**
     * @param $modelQueryClassname *Query full call name
     * @return Provider
     */
    public static function Create($modelQueryClassname, ConnectionInterface $connection = null)
    {
        $query = $modelQueryClassname::Create();

        return new Provider($query, $connection);
    }

    /**
     * List of IField's
     *
     * @param callable $filter
     * @return \Traversable|IField[]
     */
    public function getFields(callable $filter = null)
    {
        $fields = []; $table_map = $this->_model->getTableMap();
        foreach($this->_model->getTableMap()->getColumns() as $column)
        {

            if (PropelTypes::isNumericType($column->getType())) {
                $field = new Number($column->getName(), $column->getPhpName());
            } else {
                switch ($type = $column->getType()) {
                    case PropelTypes::TIMESTAMP:
                        $field = new Datetime($column->getName(), $column->getPhpName());
                        break;
                    case PropelTypes::DATE:
                        $field = new Date($column->getName(), $column->getPhpName());
                        break;
                    case PropelTypes::BOOLEAN:
                        $field = new Boolean($column->getName(), $column->getPhpName());
                        break;
                    default:
                        //echo $type.'<br>';
                        $field = new String($column->getName(), $column->getPhpName());
                        $field->setMaxLength($column->getSize());
                }
            }

            if ($column->isForeignKey())
            {
                $relation = $column->getRelation();
                $relation_query_class = $relation->getForeignTable()->getClassName().'Query';

                $relations = []; $titles = [];
                foreach($relation->getLocalColumns() as $local_column) {
                    $relations[$local_column->getName()] = $local_column->getRelatedColumnName();

                    $titles[] = $local_column->getRelatedColumn()->getPhpName();
                }


                $relation_provider = self::Create($relation_query_class, $this->getConnection());
                //$relation_provider = $column->getRelatedTable()->get
                $field = new Relation($column->getName(), $relation_provider, $relations, null, $column->getPhpName());
            }

            if ($column->isPrimaryKey()  && $table_map->isUseIdGenerator()) {
                $field->setSurrorate(true);
            }
            $field->setCanBeEmpty(!$column->isNotNull());

            $fields[] = $field;
        }

        return $filter ? array_filter($fields, $filter) : $fields;
    }

    /**
     * @param $uid
     * @return IEntity|null
     */
    public function findByUid($uid)
    {
        $model = clone $this->_model;
        $active_record = $model->setFormatter(ModelCriteria::FORMAT_OBJECT)->findPk($uid);

        if ($active_record instanceof ActiveRecordInterface) {
            $entity = new Entity($this->_model->getTableMap(), $active_record);

            return $entity;
        } else {
            return null;
        }
    }

    /**
     * @return ModelCriteria
     */
    public function filteredModel()
    {
        if ($this->filteredModel === null) {
            $this->filteredModel = clone $this->_model;
            $this->filteredModel->setFormatter(ModelCriteria::FORMAT_OBJECT);


            $table_map = $this->filteredModel->getTableMap();
            foreach ($this->_filters as list($type, $column_name, $value, $comparison)) {
                $column = $table_map->getColumn($column_name);

                $this->filteredModel->addAnd($column, $value, $comparison);
            }

            foreach ($this->sorting as list($column_name, $order)) {
                $column = $table_map->getColumn($column_name);

                $this->filteredModel->orderBy($column->getName(), $order);
            }

            if ($this->search !== null) {
                $criterion = null; /** @var $criterion AbstractCriterion */
                foreach($table_map->getColumns() as $column) {
                    if ($column->isPrimaryString()) {
                        if ($criterion === null) {
                            $criterion = $this->filteredModel->getNewCriterion($column, '%'.$this->search.'%', Criteria::LIKE);
                        } else {
                            $criterion->addOr($this->filteredModel->getNewCriterion($column, '%'.$this->search.'%', Criteria::LIKE));
                        }
                    } elseif ($column->isPrimaryKey()) {
                        if ($criterion === null) {
                            $criterion = $this->filteredModel->getNewCriterion($column, $this->search);
                        } else {
                            $criterion->addOr($this->filteredModel->getNewCriterion($column, $this->search));
                        }
                    }
                }
                if ($criterion) {
                    $this->filteredModel->addAnd($criterion);
                }
            }
        }

        return $this->filteredModel;
    }

    /**
     * IEntity's
     *
     * @return \Traversable|IEntity[]
     */
    public function iterate()
    {
        $model = clone $this->filteredModel();
        $model->setLimit($limit = $this->getPagerLimit())->setOffset($this->getPagerOffset());

        foreach($model->find($this->getReadConnection()) as $item) {
            $entity = new Entity($model->getTableMap(), $item);

            yield $entity;
        }
    }

    /**
     * Create empty entity
     *
     * @return IEntity
     */
    public function createEntity()
    {
        $class = $this->_model->getModelName();

        return new Entity($this->_model->getTableMap(), new $class());
    }

    /**
     * @return int
     */
    public function getCount()
    {
        if ($this->filteredModel === null || $this->filteredCount === null) {
            $this->filteredCount = $this->filteredModel()->setLimit(-1)->setOffset(0)->count($this->getReadConnection());
        }

        return $this->filteredCount;
    }


    /**
     * @throws UnsupportedException
     * @param IField $field,
     * @param IComparer $comparer
     * @return ICanBeFiltered
     */
    public function filter(IField $field, IComparer $comparer)
    {
        if ($comparer instanceof EqualComparer) {
            if (is_array($comparer->getMatch())) $this->_filters[] = [1, $field->getName(), $comparer->getMatch(), Criteria::IN];
            else $this->_filters[] = [1, $field->getName(), $comparer->getMatch(), Criteria::EQUAL];
        }
        if ($comparer instanceof RangeComparer) {
            if (($from = $comparer->getFrom()) !== null) {
                $this->_filters[] = [1, $field->getName(), $from, Criteria::GREATER_EQUAL];
            }
            if (($to = $comparer->getTo()) !== null) {
                $this->_filters[] = [1, $field->getName(), $to, Criteria::LESS_EQUAL];
            }
        }
        $this->filteredModel = null;
    }

    /**
     * Check if instance can be filtered
     *
     * @return ICanBeFiltered
     */
    public function resetFilter() {
        $this->_filters = [];
        $this->filteredModel = null;

        return $this;
    }

    /**
     * Get list of filterable fields
     *
     * @return IField[]
     */
    public function getFilterable() {
        return $this->getFields();
    }

    /**
     * @param IEntity $entity
     * @return bool
     */
    public function saveEntity(IEntity $entity)
    {
        if (!$this->readOnly) {
            $this->filteredCount = null;
            if ($entity instanceof Entity) {
                return $entity->save($this->getConnection());
            } else {
                $model = clone $this->_model;
                $table_map = $model->getTableMap();

                if ($uid = $entity->getUid()) {
                    $criteria = $model->setFormatter(ModelCriteria::FORMAT_ARRAY)->findPk($uid);

                    $data = [];
                    foreach ($this->getFields() as $field) {
                        $data[$field->getName()] = $field->get($entity);
                    }


                    return ($model->filterByArray($criteria)->update($data, $this->getConnection(), true) > 0);
                } else {
                    $data = [];
                    foreach ($this->getFields() as $field) {
                        $data[$table_map->translateFieldname($field->getName(), TableMap::TYPE_FIELDNAME,
                            TableMap::TYPE_PHPNAME)] = $field->get($entity);
                    }
                    $model->filterByArray($data);

                    return $model->doInsert($this->getConnection());
                }
            }
        } else {
            throw new \DomainException('Can\'t delete from read-only provider');
        }
    }

    /**
     * @return bool
     */
    public function canSave() {
        return !$this->readOnly;
    }

    /**
     * @param IEntity $entity
     * @return bool
     */
    public function deleteEntity(IEntity $entity)
    {
        if (!$this->readOnly) {

            $this->filteredCount = null;
            $model = clone $this->_model;
            $table_map = $model->getTableMap();
            $criteria = [];
            foreach ($this->getFields() as $field) {
                $criteria[$table_map->translateFieldname($field->getName(), TableMap::TYPE_FIELDNAME,
                    TableMap::TYPE_PHPNAME)] = $field->get($entity);
            }

            return ($model->filterByArray($criteria)->delete($this->getConnection()) > 0);
        } else {
            throw new \DomainException('Can\'t delete from read-only provider');
        }
    }

    /**
     * @param string $uid
     * @return bool
     */
    public function deleteByUid($uid)
    {
        $this->filteredCount = null;
        return $this->deleteEntity($this->findByUid($uid));
    }

    /**
     * @return bool
     */
    public function canDelete() {
        return $this->canSave();
    }

    /**
     * @return IField[]
     */
    public function getSortable()
    {
        return $this->getFields();
    }

    /**
     * @throws UnsupportedException
     * @param IField $field
     * @param ISorter $comparer
     * @return ICanBeSorted
     */
    public function sort(IField $field, ISorter $comparer)
    {
        if ($comparer instanceof Asc) {
            $this->sorting[] = [$field->getName(), Criteria::ASC];

            $this->filteredModel = null;

            return $this;
        } elseif ($comparer instanceof Desc) {
            $this->sorting[] = [$field->getName(), Criteria::DESC];

            $this->filteredModel = null;

            return $this;
        } else {
            throw new UnsupportedException('Asc or Desc order only');
        }
    }

    /**
     * @return ICanBeSorted
     */
    public function resetSorting()
    {
        $this->sorting = [];
        $this->filteredModel = null;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSearchTerm()
    {
        return $this->search;
    }

    /**
     * @param string|null $term
     * @return ICanSearch
     */
    public function setSearchTerm($term)
    {
        $this->search = $term;
        $this->filteredModel = null;

        return $this;
    }
}
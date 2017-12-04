<?php
/**
 * Created by PhpStorm.
 * User: Стас
 * Date: 20.07.14
 * Time: 15:49
 */
namespace project5\Rdbms;


use \Doctrine\DBAL\Query\QueryBuilder;

use project5\Exception\Unsupported;
use project5\IProvider;


use project5\Provider\Field\String as StringSurrogate;

use project5\Provider\ICanDelete;
use project5\Rdbms\Field\String,
    project5\Rdbms\Field\Relation;

use project5\Provider\ICanBeCounted;
use project5\Provider\ICanBeFiltered,
    project5\Provider\ICanBePaged,
    project5\Provider\ICanBeSorted,
    project5\Provider\ICanSearch,
    project5\Provider\IEntity,
    project5\Provider\IField,
    project5\Provider\Methods,
    project5\Provider\SyncIteratorMethods;

use project5\Provider\Compare\Equal,
    project5\Provider\Compare\Not;

use project5\Provider\ICanSave;
use project5\Provider\Sort\Asc,
    project5\Provider\Sort\Desc,
    project5\Provider\Sort\E\UnsupportedException,
    project5\Provider\Sort\ISorter;

class Provider implements IProvider, ICanBePaged, ICanBeFiltered, ICanSearch, ICanBeSorted, ICanBeCounted, ICanSave, ICanDelete
{
    use Methods, SyncIteratorMethods;

    private $_search;

    /**
     * @var IEntity|null
     */
    private $_filter = null;

    private $_fields;

    private $_pks = [];

    /**
     * @var QueryBuilder
     */
    protected $_query;

    /**
     * @var Connection
     */
    protected $_writeConnection;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $_dbalWriteConnection;

    public function __construct(QueryBuilder $query, array $fields = null)
    {
        $this->_query = $query;
        $this->_fields = $fields;

        $this->getFields();
    }

    /**
     * Set connection to write [MASTER]
     *
     * @param Connection $connection
     * @return $this
     */
    public function setWriteConnection(Connection $connection)
    {
        $this->_writeConnection = $connection;
        $this->_dbalWriteConnection = null;

        return $this;
    }

    /**
     * @param \Doctrine\DBAL\Query\QueryBuilder $query
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function setQuery($query)
    {
        $this->_query = $query;

        return $query;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * List of IField's
     *
     * @param callable $filter
     * @return \Traversable
     */
    public function getFields(callable $filter = null)
    {
        if ($this->_fields) {
            return $this->_fields;
        }

        return $this->_fields = $this->_explain();
    }

    /**
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @param $uid
     * @return Entity
     */
    public function findByUid($uid)
    {
        if (!$this->_pks) {
            $this->_pks = $this->_findPks($this->_query, $this->getFields());
        }

        if ($this->_pks) {
            if (!is_array($uid)) $uid = [$uid];

            $i = 0;
            $where = [];
            $params = [];
            foreach ($this->_pks as $field) {
                /** @var $field IField */
                if (isset($uid[$i])) {
                    $where[] = "{$field->getName()} = ?";
                    $params[] = $uid[$i];
                } else {
                    throw new \InvalidArgumentException('Can\'t found PK field in uid: "'.var_export($uid, true).'"');
                }
                $i++;
            }

            $query = clone $this->_query;
            foreach ($where as $part) {
                $query->where($part);
            }
            foreach ($params as $k => $part) {
                $query->setParameter((int)$k, $part);
            }

            $stmt = $query->execute();
            $stmt->setFetchMode(\PDO::FETCH_INTO, $this->createEntity());

            $entity = null;
            while ($row = $stmt->fetch()) {
                if ($entity !== null) {
                    throw new \RuntimeException('More than one row found for "'.var_export($uid, true).'"');
                }
                $pks = array();
                if ($this->_pks) foreach ($this->_pks as $pk) {
                    /** @var $pk IField */
                    $pks[] = $pk->get($row);
                }
                if (sizeof($pks) == 1) $pks = $pks[0];
                $row->setUid($pks);
                $entity = clone $row;
            }


            return $entity;
        } else {

        }
    }


    /**
     * Create empty entity
     *
     * @return Entity
     */
    public function createEntity()
    {
        return new Entity();
    }


    private function _explain(QueryBuilder $query = null)
    {
        if ($query === null) $query = $this->_query;


            $clone_query = clone $query;
            $r = $clone_query->setFirstResult(0)->setMaxResults(1)->execute()->fetch(\PDO::FETCH_ASSOC);
            $names = is_array($r) ? array_keys($r) : [];


            $schema_manager = $query->getConnection()->getSchemaManager();

            $fields = [];
            $form = array_reverse($query->getQueryPart('from'));

            foreach ($form as $part) {
                $table = $part['table'];
                $alias = $part['alias'];

                /*$indexes = $schema_manager->listTableIndexes($table);

                if ($indexes) foreach ($indexes as $index)  {

                }*/

                $fks = $schema_manager->listTableForeignKeys($table);
                if ($fks) foreach ($fks as $fk) {
                    /** @var $fk \Doctrine\DBAL\Schema\ForeignKeyConstraint */
                    $locals = []; $foreigns = $fk->getForeignColumns();
                    foreach ($fk->getLocalColumns() as $column_name) {
                        if (isset($r[$column_name])) {
                            $locals[$column_name] = $column_name;
                        } elseif (isset($r[$alias . '.' . $column_name])) {
                            $locals[$column_name] = $alias . '.' . $column_name;
                        }
                    }


                    if ($locals && sizeof($locals) === sizeof($foreigns)) {
                        $relations = []; reset($foreigns);
                        foreach($locals as $local_name) {
                            $relations[$local_name] = current($foreigns); next($foreigns);
                        }

                        $base_name = $fk->getForeignTableName();
                        $suffix = '';
                        $i = 0;
                        while (isset($fields[$field_name = $base_name . $suffix])) {
                            $suffix = '_' . ++$i;
                        }

                        $fields[$field_name] = new Relation($field_name, $query->getConnection(), $fk->getForeignTableName(), $alias, $relations);

                        // filter names
                        $filtered = []; $injected = false;
                        foreach ($names as $n) {
                            if (in_array($n, $locals)) {
                                if (!$injected) {
                                    $filtered[] = $field_name;
                                    $injected = true;
                                }
                            } else $filtered[] = $n;
                        }
                        $names = $filtered;
                    }
                }

                if ($columns = $schema_manager->listTableColumns($table))
                foreach ($columns as $column_name => $column) /** @var $column \Doctrine\DBAL\Schema\Column */ {

                    $field = null;
                    if (isset($r[$column_name])) {
                        $field = new String($alias, $column_name, $column_name);
                        unset($r[$column_name]);
                    } elseif (isset($r[$alias . '.' . $column_name])) {
                        $field = new String($alias, $alias . '.' . $column_name, $column_name);
                        unset($r[$alias . '.' . $column_name]);
                    }



                    if ($field && !isset($fields[$field->getName()])) {
                        if ($column->getAutoincrement()) {
                            $field->setSurrorate(true);
                        }

                        $fields[$field->getName()] = $field;
                    }
                }
            }

            $ordered = [];
            foreach ($names as $n) {
                if (isset($fields[$n])) $ordered[] = $fields[$n];
                else {
                    // create surrogate field
                    $field = new StringSurrogate($n);
                    $field->setSurrorate(true);
                    $ordered[] = $field;
                }
            }

            return $ordered;

    }

    private function _findPks(QueryBuilder $query = null, $fields = null)
    {
        if ($query === null) $query = $this->_query;

        $schema_manager = $query->getConnection()->getSchemaManager();


        if ($fields === null) {
            $fields = $this->_explain($query);
        }

        $indexed_names = [];
        foreach ($query->getQueryPart('from') as $part) {
            $table = $part['table'];

            if ($schema_manager->tablesExist([$table])) {
                foreach ($schema_manager->listTableIndexes($table) as $index) /** @var $column \Doctrine\DBAL\Schema\Index */ {
                    if ($index->isPrimary()) {
                        foreach ($index->getColumns() as $column_name) {

                            $indexed_names[] = $column_name;
                        }
                    }
                }
            }
        }

        return array_filter($fields, function (IField $field) use($indexed_names) {
            return in_array($field->getName(), $indexed_names);
        });

    }

    /**
     * @var \Doctrine\DBAL\Driver\Statement
     */
    private $_stmt;

    protected function _buildQuery()
    {
        $params = [];
        $where = [];

        $fields = $this->getFields();

        $query = clone $this->_query;

        // add joins and filtering
        foreach($fields as $field) {
            /*if ($this->_filter) {
                $value = $field->get($this->_filter);
                if ($value) {
                    $where[] = "{$field->getName()} = ?";
                    $params[] = $value;
                }
            }*/


            if ($field instanceof Relation) {
                $local_alias = $field->getLocalTableAlias();
                $ftable = $field->getForeignTableName();
                $falias = $field->getUniqueTitleAlias();


                $joins = []; $locals = [];
                foreach($field->getRelations() as $local_name => $foreign_field_name) {
                    $locals[] = sprintf('%s.%s', $local_alias, $local_name);
                    $joins[] = sprintf('%s.%s = %s.%s', $local_alias, $local_name, $falias, $foreign_field_name);
                }

                $query->leftJoin($local_alias, $ftable, $falias, implode(' AND ', $joins));

                $foreign_name = $field->getTitleField()->getName();
                $query->addSelect(sprintf('%s.%s as %s', $falias, $foreign_name, $falias));

                foreach($this->_comparers as $info) {
                    list($compare_field, $comparer) = $info;
                    /** @var IField $field */

                    if ($compare_field === $field) {
                        if ($comparer instanceof Equal) {
                            $value = $comparer->getMatch();

                            if (!is_array($value)) $value = [$value];
                            reset($value);
                            foreach($locals as $local_name) {
                                $where[] = "{$local_name} = ?";
                                $params[] = current($value); next($value);
                            }


                        } else {
                            throw new Unsupported();
                        }
                    }

                }
            } else {
                foreach($this->_comparers as $info) {
                    list($compare_field, $comparer) = $info;
                    /** @var String $field */

                    if ($compare_field === $field) {
                        if ($comparer instanceof Equal) {
                            $value = $comparer->getMatch();

                            if ($field instanceof String) {
                                $where[] = "{$field->getTableAlias()}.{$field->getName()} = ?";
                                $params[] = $value;
                            }


                        } else {
                            throw new Unsupported();
                        }
                    }

                }
            }
        }

        foreach ($where as $part) {
            $query->andWhere($part);
        }
        foreach ($params as $k => $part) {
            $query->setParameter((int)$k, $part);
        }
        $query->setFirstResult($this->getPagerOffset())->setMaxResults($this->getPagerLimit());

        return $query;
    }

    protected function _reset($cursor, array $extra = [])
    {
        $query = $this->_buildQuery();

        $this->_stmt = $query->execute();
        $this->_stmt->setFetchMode(\PDO::FETCH_INTO, $this->createEntity());

        return $this->getPagerOffset();
    }

    protected function _next(array &$extra)
    {
        if ($row = $this->_stmt->fetch()) {
            if (!$this->_pks) {
                $this->_pks = $this->_findPks($this->_query, $this->getFields());
            }

            $pks = array();
            if ($this->_pks) foreach ($this->_pks as $pk) {
                /** @var $pk IField */
                $pks[] = $pk->get($row);
            }
            if (sizeof($pks) == 1) $pks = $pks[0];
            $row->setUid($pks);
            return clone $row;
        }

        return null;
    }


    /**
     * @return string|null
     */
    public function getSearchTerm()
    {
        return $this->_search;
    }

    /**
     * @param string|null $term
     * @return ICanSearch
     */
    public function setSearchTerm($term)
    {
        $this->_search = $term;

        return $this;
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
    public function sort(IField $field, ISorter $sorter)
    {
        if ($field instanceof Relation) {
            $order_field_name = $field->getUniqueTitleAlias();
        } else {
            $order_field_name = $field->getName();
        }

        if ($sorter instanceof Asc) {
            $this->_query->addOrderBy($order_field_name);
        } elseif ($sorter instanceof Desc) {
            $this->_query->addOrderBy($order_field_name, 'DESC');
        } else {
            throw new UnsupportedException('Unsupported ' . get_class($sorter) . ' sorter');
        }

        return $this;
    }

    /**
     * @return ICanBeSorted
     */
    public function resetSorting()
    {
        $this->_query->resetQueryPart('orderBy');

        return $this;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        $count_query = $this->_buildQuery();

        return $count_query
            ->resetQueryPart('orderBy')
            ->setMaxResults(null)
            ->setFirstResult(null)
            ->select('COUNT(*)')
        ->execute()->fetchColumn(0);
    }

    /**
     * @param Connection $conn
     * @param $table
     * @return Provider
     */
    public static function QueryTable(Connection $conn, $table)
    {

        $queryBuilder = $conn->createQueryBuilder();
        $queryBuilder->select('t.*')->from($table, 't');

        return new self($queryBuilder);
    }

    /**
     *
     * @return \Doctrine\DBAL\Connection
     */
    private function _getWriteDbalConnection()
    {
        if ($this->_dbalWriteConnection === null) {
            if ($this->_writeConnection) $this->_dbalWriteConnection = $this->_writeConnection->createQueryBuilder()->getConnection();
            else $this->_dbalWriteConnection = $this->_query->getConnection();
        }

        return $this->_dbalWriteConnection;
    }

    /**
     * @throws Unsupported
     * @param IEntity $entity
     * @return bool
     */
    public function saveEntity(IEntity $entity)
    {
        if (!$this->canSave()) {
            throw new Unsupported();
        }
        $table = null;
        foreach ($this->_query->getQueryPart('from') as $part) {
            $table = $part['table'];
        }

        $data = []; $fields = $this->getFields();
        foreach($fields as $field) {
            /** @var $field IField */
            if ($field->isSurrogate()) continue;
            if ($field instanceof Relation) {
                foreach($field->getLocal($entity) as $local_name => $value) {
                    $data[$local_name] = $value;
                }
            } else {
                $data[$field->getName()] = $field->get($entity);
            }
        }

        if ($uid = $entity->getUid()) {
            if (!is_array($uid)) $uid = [$uid]; reset($uid);
            $pks = [];
            foreach(self::_findPks($this->_query, $fields) as $field) {
                $pks[$field->getName()] = current($uid); next($uid);
            }
            $this->_getWriteDbalConnection()->update($table, $data, $pks);
        } else {
            $this->_getWriteDbalConnection()->insert($table, $data);
        }



        return true;
    }

    /**
     * Can save if use only one table query AND has all required fields
     *
     * @return bool
     */
    public function canSave()
    {
        $tables = [];
        foreach ($this->_query->getQueryPart('from') as $part) {
            $table = $part['table'];

            $tables[] = $table;
        }

        if (sizeof(array_unique($tables)) === 1) {
            $table = $tables[0]; $required = [];
            foreach($this->_query->getConnection()->getSchemaManager()->listTableColumns($table) as $column) /** @var $column \Doctrine\DBAL\Schema\Column */ {
                if ($column->getNotnull() && !$column->getAutoincrement()) $required[$column->getName()] = 1;
            }

            if (sizeof($required)) {
                foreach($this->getFields() as $field) {
                    /** @var $field IField */
                    if ($field instanceof Relation) {
                        foreach($field->getRelations() as $local_name => $value) {
                            if (isset($required[$local_name])) unset($required[$local_name]);
                        }
                    } else {
                        if (isset($required[$local_name = $field->getName()])) unset($required[$local_name]);
                    }
                }

                return (sizeof($required) === 0);
            } else return true;
        } else return false;
    }

    /**
     * @return IField[]
     */
    public function getFilterable()
    {
        return array_filter($this->getFields(), function(IField $field) {
            return ($field instanceof String) || ($field instanceof Relation);
        });
    }

    /**
     * @param IEntity $entity
     * @return bool
     */
    public function deleteEntity(IEntity $entity)
    {
        return $this->deleteByUid($entity->getUid());
    }

    /**
     * @param string $uid
     * @return bool
     */
    public function deleteByUid($uid)
    {
        if (!$this->canSave()) {
            throw new Unsupported();
        }
        $table = null;
        foreach ($this->_query->getQueryPart('from') as $part) {
            $table = $part['table'];
        }


        if (!is_array($uid)) $uid = [$uid]; reset($uid);
        $pks = [];
        foreach(self::_findPks($this->_query, $this->getFields()) as $field) {
            $pks[$field->getName()] = current($uid); next($uid);
        }

        $this->_getWriteDbalConnection()->delete($table, $pks);

        return true;
    }

    /**
     * @return bool
     */
    public function canDelete()
    {
        return true;
    }

}

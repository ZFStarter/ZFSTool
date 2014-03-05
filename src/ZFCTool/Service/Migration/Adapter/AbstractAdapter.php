<?php

namespace ZFCTool\Service\Migration\Adapter;

use Zend\Db\Adapter\Adapter;

abstract class AbstractAdapter
{
    /**
     * Default Database adapter
     *
     * @var Adapter
     */
    protected $_dbAdapter = null;


    /**
     * @param Adapter $dbAdapter
     */
    public function __construct(Adapter $dbAdapter)
    {
        $this->_dbAdapter = $dbAdapter;
    }

    /**
     * setDbAdapter
     *
     * @param  Adapter $dbAdapter
     * @return AbstractAdapter
     */
    protected function setDbAdapter($dbAdapter = null)
    {
        if ($dbAdapter && ($dbAdapter instanceof Adapter)) {
            $this->_dbAdapter = $dbAdapter;
        }
        return $this;
    }

    /**
     * getDbAdapter
     *
     * @return Adapter
     */
    public function getDbAdapter()
    {
        if (!$this->_dbAdapter) {
            $this->setDbAdapter();
        }
        return $this->_dbAdapter;
    }

    /**
     * query
     *
     * @param   string $query
     * @param array $bind
     * @return  AbstractAdapter
     */
    public function query($query, $bind = array())
    {
        $this->getDbAdapter()->query($query, $bind);
        return $this;
    }

    /**
     * Insert
     *
     * @param string $table
     * @param array $params
     * @return AbstractAdapter
     */
    public function insert($table, array $params)
    {
        return $this->getDbAdapter()->insert($table, $params);
    }

    /**
     * Updates table rows with specified data based on a WHERE clause.
     *
     * @param  mixed $table The table to update.
     * @param  array $bind Column-value pairs.
     * @param  mixed $where UPDATE WHERE clause(s).
     * @return int          The number of affected rows.
     */
    public function update($table, array $bind, $where = '')
    {
        return $this->getDbAdapter()->update($table, $bind, $where);
    }


    /**
     * createTable
     *
     * @param   string $table table name
     * @return  AbstractAdapter
     */
    abstract public function createTable($table);

    /**
     * dropTable
     *
     * @param   string $table table name
     * @return  AbstractAdapter
     */
    abstract public function dropTable($table);

    /**
     * createColumn
     *
     * FIXME: requried quoted queries data
     *
     * @param   string $table
     * @param   string $column
     * @param   string $datatype
     * @param   string $length
     * @param   string $default
     * @param   bool $notnull
     * @param   bool $primary
     * @return  bool
     */
    abstract public function createColumn($table,
                                          $column,
                                          $datatype,
                                          $length = null,
                                          $default = null,
                                          $notnull = false,
                                          $primary = false
    );


    /**
     * dropColumn
     *
     * @param   string $table
     * @param   string $name
     * @return  bool
     */
    abstract public function dropColumn($table, $name);

    /**
     * Create an unique index on table
     *
     * @param string $table
     * @param array $columns
     * @param string $indName
     * @return AbstractAdapter
     */
    abstract public function createUniqueIndexes($table, array $columns, $indName = null);

    /**
     * Drop an index on table
     *
     * @param        $table
     * @param string $indName
     * @return AbstractAdapter
     */
    abstract public function dropUniqueIndexes($table, $indName);

    /**
     * __call for unsupported adaptors methods
     *
     * @param string $name Method name
     * @param mixed $arguments Method Arguments
     *                          return AbstractAdapter
     * @return AbstractAdapter
     */
    public function  __call($name, $arguments)
    {
        return $this;
    }
}

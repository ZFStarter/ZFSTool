<?php
namespace ZFCTool\Service\Migration\Adapter;

use Zend\Db\Adapter\Adapter;
use ZFCTool\Exception\ZFCToolException;
use ZFCTool\Service\Migration\AbstractMigration;
use Zend\Db\Metadata\Metadata;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Ddl;
use Zend\Db\Sql\Ddl\Column;

class Mysql extends AbstractAdapter
{

    /**
     * Create table
     *
     * @param string $table
     * @return AbstractAdapter
     */
    public function createTable($table)
    {
        $this->query(
            'CREATE TABLE ' .
            $table .
            ' ( `id` bigint NOT NULL AUTO_INCREMENT , PRIMARY KEY (`id`))' .
            ' Engine=InnoDB',
            Adapter::QUERY_MODE_EXECUTE
        );
        return $this;
    }

    /**
     * dropTable
     *
     * @param   string $table table name
     * @return  AbstractAdapter
     */
    public function dropTable($table)
    {
        $this->query('DROP TABLE ' . $table, Adapter::QUERY_MODE_EXECUTE);
        return $this;
    }

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
    public function createColumn(
        $table,
        $column,
        $datatype,
        $length = null,
        $default = null,
        $notnull = false,
        $primary = false
    ) {
        // alter table $table add column $column $options
        // alter table `p_zfc`.`asd` add column `name` varchar(123) NOT NULL after `id`
        $column = $this->getDbAdapter()->getPlatform()->quoteIdentifier($column);
        $query = 'ALTER TABLE ' . $this->getDbAdapter()->getPlatform()->quoteIdentifier($table)
            . ' ADD COLUMN ' . $column;

        // switch statement for $datatype
        switch ($datatype) {
            case AbstractMigration::TYPE_VARCHAR:
                $length = $length ? $length : 255;
                $query .= " varchar($length)";
                break;
            case AbstractMigration::TYPE_FLOAT:
                $length = $length ? $length : '0,0';
                $query .= " float($length)";
                break;
            case AbstractMigration::TYPE_ENUM:
                if (is_array($length)) {
                    // array to string 'el','el',...
                    $length = "'" . join("','", $length) . "'";
                }
                $query .= " enum($length)";
                break;
            default:
                $query .= " $datatype";
                break;
        }

        if (!is_null($default)) {
            // switch statement for $datatype
            switch ($datatype) {
                case (AbstractMigration::TYPE_TIMESTAMP && $default == 'CURRENT_TIMESTAMP'):
                    $query .= " default CURRENT_TIMESTAMP";
                    break;
                default:
                    $query .= ' default ' . $this->getDbAdapter()->getPlatform()->quoteIdentifier($default);
                    break;
            }
        }

        if ($notnull) {
            $query .= " NOT NULL";
        } else {
            $query .= " NULL";
        }

        if ($primary) {

            $metadata = new Metadata($this->getDbAdapter());
            // TODO: drop primary key, add primary key (`all keys`,`$column`)
            $primary = array();
            $constraints = $metadata->getConstraints($table);
            /** @var $constraint \Zend\Db\Metadata\Object\ConstraintObject */
            foreach ($constraints as $constraint) {
                if ($constraint->isPrimaryKey()) {
                    foreach ($constraint->getColumns() as $columnName) {
                        array_push($primary, $columnName);
                    }
                }
            }

            if (sizeof($primary)) {
                $keys = $quotedColumns = $this->quoteIdentifierArray($primary);
                $query .= ", drop primary key, add primary key ($keys, $column)";
            } else {
                $query .= ", add primary key ($column)";
            }
        }

        $this->query($query, Adapter::QUERY_MODE_EXECUTE);

        return $this;
    }

    /**
     * dropColumn
     *
     * @param   string $table
     * @param   string $name
     * @return  bool
     */
    public function dropColumn($table, $name)
    {
        $this->query(
            'ALTER TABLE ' . $this->getDbAdapter()->getPlatform()->quoteIdentifier($table) .
            ' DROP COLUMN ' . $this->getDbAdapter()->getPlatform()->quoteIdentifier($name),
            Adapter::QUERY_MODE_EXECUTE
        );
        return $this;
    }

    /**
     * Create an unique index on table
     *
     * @param string $table
     * @param array $columns
     * @param string $indName
     * @return AbstractAdapter
     */
    public function createUniqueIndexes($table, array $columns, $indName = null)
    {
        if (!$indName) {
            $indName = strtoupper($table . '_' . implode('_', $columns));
        }
        //quoting a columns
        $quotedColumns = $this->quoteIdentifierArray($columns);
        $query = 'ALTER TABLE ' . $this->getDbAdapter()->getPlatform()->quoteIdentifier($table)
            . ' ADD UNIQUE ' . $this->getDbAdapter()->getPlatform()->quoteIdentifier($indName)
            . '(' . $quotedColumns . ')';
        $this->query($query, Adapter::QUERY_MODE_EXECUTE);

        return $this;
    }


    /**
     * @param $table
     * @param string $indName
     * @return $this|AbstractAdapter
     * @throws \ZFCTool\Exception\ZFCToolException
     */
    public function dropUniqueIndexes($table, $indName)
    {
        if ($table && $indName) {
            $query = 'DROP INDEX ' . $this->getDbAdapter()->getPlatform()->quoteIdentifier($indName)
                . ' ON ' . $this->getDbAdapter()->getPlatform()->quoteIdentifier($table);
            $this->query($query, Adapter::QUERY_MODE_EXECUTE);
        } else {
            throw new ZFCToolException(
                "Can't drop index " . $indName . " ON " . $table
            );
        }
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
        $sql = new Sql($this->getDbAdapter());
        $insert = $sql->insert($table);
        $insert->values($params);
        $selectString = $sql->getSqlStringForSqlObject($insert);
        $this->getDbAdapter()->query($selectString, Adapter::QUERY_MODE_EXECUTE);
        return $this;
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
        $sql = new Sql($this->getDbAdapter());
        $update = $sql->update($table);
        $update->where($where);
        $update->set($bind);
        $selectString = $sql->getSqlStringForSqlObject($update);
        $this->getDbAdapter()->query($selectString, Adapter::QUERY_MODE_EXECUTE);
        return $this;
    }


    /**
     * Quoting array of identifier and converts it to coma separated string
     *
     * @param array $columns
     * @return string
     */
    protected function quoteIdentifierArray(array $columns)
    {
        $quotedColumns = array();
        foreach ($columns as $value) {
            $quotedColumns[] = $this->getDbAdapter()->getPlatform()->quoteIdentifier($value);
        }

        return implode(',', $quotedColumns);
    }
}

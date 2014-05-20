<?php
/**
 * User: naxel
 * Date: 24.02.14 13:47
 */

namespace ZFCTool\Service;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Metadata\Metadata;
use Zend\Db\Adapter\Driver\StatementInterface;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Ddl;
use Zend\Db\Sql\Ddl\Column;

class Database
{

    /**
     * array with schemes of tables
     * @var array
     */
    protected $scheme = array();

    /**
     * array with indexes of tables
     * @var array
     */
    protected $indexes = array();

    /**
     * @var array
     */
    protected $data = array();

    /**
     * array with ignored tables
     * @var array
     */
    protected $blackList = array();

    /**
     * array with "white listed" tables
     * @var array
     */
    protected $whiteList = array();

    /**
     * @var Adapter
     */
    protected $db;

    protected static $defaultDb;

    /**
     * @var array
     */
    protected $options = array();


    /**
     * @param Adapter $adapter
     * @param null $options
     * @param bool $autoLoad
     */
    public function __construct(Adapter $adapter, $options = null, $autoLoad = true)
    {

        /** @var $db Adapter */
        $this->db = $adapter;
        self::$defaultDb = $adapter;

        $this->options = $options;


        if (isset($options['blacklist']) && !isset($options['whitelist'])) {

            if (is_array($options['blacklist'])) {
                $this->blackList = $options['blacklist'];
            } else {
                $this->blackList[] = (string)$options['blacklist'];
            }

        } elseif (isset($options['whitelist']) && !empty($options['whitelist'])) {

            if (is_array($options['whitelist'])) {
                $this->whiteList = $options['whitelist'];
            } else {
                $this->whiteList[] = (string)$options['whitelist'];
            }
        }

        if ($autoLoad) {

            $metadata = new Metadata($adapter);
            $tables = $metadata->getTables();

            foreach ($tables as $table) {
                $this->addTable($table->getName());
            }
        }
    }


    /**
     * Generate dump
     *
     * @return string
     */
    public function getDump()
    {
        $dump = '';
        foreach ($this->scheme as $tableName => $fields) {

            $dump .= self::dropTable($tableName) . ';' . PHP_EOL;
            $dump .= self::createTable($tableName) . ';' . PHP_EOL;

            if (sizeof($this->data[$tableName]) > 0) {
                foreach ($this->data[$tableName] as $data) {
                    $dump .= self::insert($tableName, $data) . ';' . PHP_EOL;
                }
            }
        }

        if ($dump) {
            $dump = self::getDisableChecksNotation() . $dump;
        }

        return $dump;
    }

    /**
     * @return string
     */
    public static function getDisableChecksNotation()
    {
        $sql = "/*!40101 SET NAMES utf8 */;\n"
            . "/*!40101 SET SQL_MODE=''*/;\n"
            . "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n"
            . "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n"
            . "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n"
            . "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n";
        return $sql;
    }

    /**
     * retrieve index list from table
     * @param $table - table name
     * @return array - array of indexes
     */

    protected function getIndexListFromTable($table)
    {

        $sql = "SHOW INDEXES FROM `{$table}`";
        $indexesData = $this->db->createStatement($sql)->execute();
        $indexes = array();

        foreach ($indexesData as $index) {
            if (!isset($indexes[$index['Key_name']])) {
                $indexes[$index['Key_name']] = array();
            }
            $indexes[$index['Key_name']]['unique'] = !intval($index['Non_unique']);
            $indexes[$index['Key_name']]['type'] = $index['Index_type'];
            $indexes[$index['Key_name']]['name'] = $index['Key_name'];
            $indexes[$index['Key_name']]['table'] = $index['Table'];
            if (!isset($indexes[$index['Key_name']]['fields'])) {
                $indexes[$index['Key_name']]['fields'] = array();
            }
            $indexes[$index['Key_name']]['fields'][$index['Seq_in_index']] =
                array(
                    'name' => $index['Column_name'],
                    'length' => $index['Sub_part']
                );
            $indexes[$index['Key_name']]['constraint'] = $this->getConstraintForColumn($table, $index['Column_name']);

        }
        return $indexes;

    }

    /**
     * @param $table - table name
     * @param $colName - column name
     * @return array|bool - return list of constrains or false, if constrains not exist
     */

    protected function getConstraintForColumn($table, $colName)
    {
        $rows = $this->db->createStatement("select database() as dbname")->execute();
        $row = $rows->current();

        $dbName = $row['dbname'];

        $sql = "SELECT k.CONSTRAINT_SCHEMA,k.CONSTRAINT_NAME,"
            . "k.TABLE_NAME,k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,"
            . "k.REFERENCED_COLUMN_NAME, r.UPDATE_RULE, r.DELETE_RULE FROM "
            . "information_schema.key_column_usage k LEFT JOIN "
            . "information_schema.referential_constraints r ON "
            . "r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND "
            . " k.REFERENCED_TABLE_NAME=r.REFERENCED_TABLE_NAME "
            . "LEFT JOIN information_schema.table_constraints t ON "
            . "t.CONSTRAINT_SCHEMA = r.CONSTRAINT_SCHEMA WHERE "
            . " k.constraint_schema='$dbName' AND t.CONSTRAINT_TYPE='FOREIGN KEY' "
            . "AND k.TABLE_NAME='$table' AND r.TABLE_NAME='$table' "
            . " AND t.TABLE_NAME='$table' AND k.COLUMN_NAME='$colName'";

        $rows = $this->db->createStatement($sql)->execute();
        $row = $rows->current();

        if (!count($row)) {
            return false;
        }

        $constraint = array(
            'table' => $table,
            'name' => $row['CONSTRAINT_NAME'],
            'column' => $row['COLUMN_NAME'],
            'reference' => array(
                'table' => $row['REFERENCED_TABLE_NAME'],
                'column' => $row['REFERENCED_COLUMN_NAME'],
                'update' => $row['UPDATE_RULE'],
                'delete' => $row['DELETE_RULE'],
            )
        );
        return $constraint;
    }

    /**
     * add table to DB object
     * @param $tableName
     */
    public function addTable($tableName)
    {
        if ($this->isTblWhiteListed($tableName) && !$this->isTblBlackListed($tableName)) {

            $metadata = new \Zend\Db\Metadata\Metadata($this->db);
            // get the table names
            $columns = $metadata->getColumns($tableName);
            $scheme = array();

            /** @var $column \Zend\Db\Metadata\Object\ColumnObject */
            foreach ($columns as $column) {

                $scheme[$column->getName()] = array(
                    'SCHEMA_NAME' => null,
                    'TABLE_NAME' => $column->getTableName(),
                    'COLUMN_NAME' => $column->getName(),
                    'COLUMN_POSITION' => $column->getOrdinalPosition(),
                    'DATA_TYPE' => $column->getDataType(),
                    'DEFAULT' => $column->getColumnDefault(),
                    'NULLABLE' => $column->isNullable(),
                    'LENGTH' => $column->getCharacterMaximumLength(),
                    'SCALE' => $column->getNumericScale(),
                    'PRECISION' => $column->getNumericPrecision(),
                    'UNSIGNED' => $column->getNumericUnsigned(),
                    'PRIMARY' => false,
                    'IDENTITY' => false
                );
            }

            /** @var $constraintObject \Zend\Db\Metadata\Object\ConstraintObject */
            foreach ($metadata->getConstraints($tableName) as $constraintObject) {

                if ('PRIMARY KEY' === $constraintObject->getType()) {
                    foreach ($constraintObject->getColumns() as $columnName) {
                        $scheme[$columnName]['PRIMARY'] = true;
                        $scheme[$columnName]['IDENTITY'] = true;
                    }
                }
            }

            $this->scheme[$tableName] = $scheme;
            $this->indexes[$tableName] = $this->getIndexListFromTable($tableName);

            if (isset($this->options['loaddata']) && $this->options['loaddata'] == true) {

                $sql = new Sql($this->db);
                $this->data[$tableName] = $sql->prepareStatementForSqlObject($sql->select($tableName))->execute();
            }
        }
    }

    /**
     * delete table from DB object
     * @param $tableName
     */
    public function deleteTable($tableName)
    {
        if (array_key_exists($tableName, $this->scheme)) {
            unset($this->scheme[$tableName]);
        }
    }

    /**
     * checks for table in @blacklist
     * @param $tableName
     * @return bool
     */
    protected function isTblWhiteListed($tableName)
    {
        if (!empty($this->whiteList)) {
            return in_array($tableName, $this->whiteList);
        }
        return true;
    }

    /**
     * checks for table in @whitelist
     * @param $tableName
     * @return bool
     */
    protected function isTblBlackListed($tableName)
    {
        if (!empty($this->blackList)) {
            return in_array($tableName, $this->blackList);
        }
        return false;

    }

    /**
     * encode object data into JSON
     * @return string - JSON string
     */
    public function toString()
    {
        return json_encode(
            array('data' => $this->scheme, 'indexes' => $this->indexes)
        );
    }

    /**
     * decode object from JSON string
     * clear scheme and indexes if string is empty
     * @param $jsonString
     */
    public function fromString($jsonString)
    {
        if (!empty($jsonString)) {

            $dec = json_decode($jsonString, true);

            $this->indexes = $dec['indexes'];
            $dec = $dec['data'];

            if (!empty($this->blackList)) {
                foreach ($this->blackList as $deleteKey) {
                    if (array_key_exists($deleteKey, $dec)) {
                        unset($dec[$deleteKey]);
                    }
                }
            }

            if (!empty($this->whiteList)) {
                foreach ($dec as $tblName => $table) {
                    if (!in_array($tblName, $this->whiteList)) {
                        unset($dec[$tblName]);
                    }
                }
            }

            $this->scheme = $dec;
        } else {
            $this->scheme = array();
            $this->indexes = array();
        }

    }

    /**
     * get all tables form DB
     * @return array
     */
    public function getTables()
    {
        return $this->scheme;
    }

    /**
     * get all columns from table
     * @param $tableName
     * @return array|bool - returns false if table not exist
     */
    public function getTableColumns($tableName)
    {
        return (isset($this->scheme[$tableName])) ?
            $this->scheme[$tableName] : false;

    }

    /**
     * get all table indexes
     * @param $tableName
     * @return array - returns empty array if no indexes found
     */
    public function getIndexList($tableName)
    {
        if (array_key_exists($tableName, $this->indexes)) {
            return $this->indexes[$tableName];
        } else {
            return array();
        }
    }


    /**
     * create DROP TABLE query
     * @param $tableName
     * @return string
     */
    public static function dropTable($tableName)
    {
        return "DROP TABLE IF EXISTS `{$tableName}`";
    }

    /**
     * create query for delete column
     * @param $tableName
     * @param $column
     * @return string
     */
    public static function dropColumn($tableName, $column)
    {
        return "ALTER TABLE `{$tableName}` DROP `{$column['COLUMN_NAME']}`";
    }

    /**
     * add column attributes to query
     * @param $sql
     * @param $column
     */
    protected static function addSqlExtras(& $sql, $column)
    {
        if ($column['LENGTH']) {
            $sql .= ' (' . $column['LENGTH'] . ')';
        }
        if ($column['UNSIGNED']) {
            $sql .= ' UNSIGNED ';
        }
        if (!$column['NULLABLE']) {
            $sql .= " NOT NULL ";
        }
        if (!is_null($column['DEFAULT'])) {
            $sql .= " DEFAULT \\'{$column['DEFAULT']}\\' ";
        }
        if ($column['IDENTITY']) {
            $sql .= ' AUTO_INCREMENT ';
        }
    }

    /**
     * create query for adding column
     * @param $tableName
     * @param $column
     * @return string
     */
    public static function addColumn($tableName, $column)
    {
        $sql = "ALTER TABLE `{$tableName}` ADD `{$column['COLUMN_NAME']}` " . addslashes($column['DATA_TYPE']);
        Database::addSqlExtras($sql, $column);
        return $sql;
    }

    /**
     * create query for change column
     * @param $tableName
     * @param $column
     * @return string
     */
    public static function changeColumn($tableName, $column)
    {
        $sql = "ALTER TABLE `{$tableName}` CHANGE " .
            " `{$column['COLUMN_NAME']}` `{$column['COLUMN_NAME']}` " .
            addslashes($column['DATA_TYPE']);
        Database::addSqlExtras($sql, $column);
        return $sql;
    }


    /**
     * create CREATE TABLE query
     * @param $tblName
     * @return string
     */
    public static function createTable($tblName)
    {
        /** @var $statement StatementInterface */
        $statement = self::$defaultDb->query("SHOW CREATE TABLE `{$tblName}`");
        $results = $statement->execute();
        $trow = $results->current();

        $query = preg_replace('#AUTO_INCREMENT=\S+#is', '', $trow['Create Table']);
        //$query = preg_replace("#\n\s*#", ' ', $query); //uncomment if you want query in one line
        return $query;
    }


    /**
     * create query for adding index
     * @param $index
     * @return string
     */
    public static function addIndex($index)
    {
        if ($index['name'] === 'PRIMARY') {
            $indexString = "ALTER TABLE `{$index['table']}` ADD PRIMARY KEY";
            $fields = array();
            foreach ($index['fields'] as $f) {
                $len = intval($f['length']) ? "({$f['length']})" : '';
                $fields[] = "{$f['name']}" . $len;
            }
            $indexString .= "(" . implode(',', $fields) . ")";
        } else {
            $indexString = "CREATE ";
            if ($index['type'] === 'FULLTEXT') {
                $indexString .= " FULLTEXT ";
            }
            if ($index['unique']) {
                $indexString .= " UNIQUE ";
            }
            $indexString .= " INDEX `{$index['name']}` ";
            if (in_array($index['type'], array('RTREE', 'BTREE', 'HASH',))) {
                $indexString .= " USING {$index['type']} ";
            }
            $indexString .= " on `{$index['table']}` ";
            $fields = array();
            foreach ($index['fields'] as $f) {
                $len = intval($f['length']) ? "({$f['length']})" : '';
                $fields[] = "{$f['name']}" . $len;
            }
            $indexString .= "(" . implode(',', $fields) . ")";
        }
        return $indexString;
    }

    /**
     * create query for drop index
     * @param $index
     * @return string
     */
    public static function dropIndex($index)
    {
        return "DROP INDEX `{$index['name']}` ON `{$index['table']}`";
    }

    /**
     * create query for drop constraint
     * @param $index
     * @return string
     */
    public static function dropConstraint($index)
    {
        if (!isset($index['constraint']['column'])
            || !strlen($index['constraint']['column'])
        ) {
            return '';
        }

        $sql = "ALTER TABLE `{$index['constraint']['table']}` " .
            "DROP FOREIGN KEY `{$index['constraint']['name']}` ";

        return $sql;
    }

    /**
     * create query for adding constraint
     * @param $index
     * @return string
     */
    public static function addConstraint($index)
    {
        if (!isset($index['constraint']['column']) || !strlen($index['constraint']['column'])) {
            return '';
        }
        $sql = "ALTER TABLE `{$index['constraint']['table']}` " .
            "ADD CONSTRAINT `{$index['constraint']['name']}` " .
            "FOREIGN KEY (`{$index['constraint']['column']}`) " .
            "REFERENCES `{$index['constraint']['reference']['table']}` " .
            "(`{$index['constraint']['reference']['column']}`) " .
            "ON UPDATE {$index['constraint']['reference']['update']} " .
            "ON DELETE {$index['constraint']['reference']['delete']} ";
        return $sql;
    }


    /**
     * Generate insert for dump
     *
     * @param string $table
     * @param array $bind
     * @return string
     */
    public static function insert($table, array $bind)
    {
        // extract and quote col names from the array keys
        $cols = array();
        $values = array();
        foreach ($bind as $col => $val) {
            $cols[] = '`' . $col . '`';
            $values[] = self::$defaultDb->getPlatform()->quoteValue($val);
        }

        // build the statement
        $sql = "INSERT INTO `"
            . $table
            . '` (' . implode(', ', $cols) . ') '
            . 'VALUES (' . implode(', ', $values) . ')';

        return $sql;
    }
}

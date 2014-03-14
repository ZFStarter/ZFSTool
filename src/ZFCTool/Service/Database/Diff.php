<?php
/**
 * User: naxel
 * Date: 24.02.14 16:03
 */

namespace ZFCTool\Service\Database;

use Zend\Db\Adapter\Adapter;
use ZFCTool\Service\Database;
use Zend\Db\Adapter\Driver\StatementInterface;

class Diff
{

    /**
     * @var Database
     */
    protected $currentDb;
    /**
     * @var Database
     */
    protected $publishedDb;

    /**
     * @var array
     */
    protected $difference = array('up' => array(), 'down' => array());

    protected $createTables = array();
    protected $dropTables = array();
    protected $commonTables = array();

    /**
     * @var Adapter
     */
    protected $db;


    /**
     * @param Adapter $adapter
     * @param $currentDb
     * @param $lastPublishedDb
     */
    public function __construct(Adapter $adapter, $currentDb, $lastPublishedDb)
    {
        /** @var $db Adapter */
        $this->db = $adapter;

        $this->currentDb = $currentDb;
        $this->publishedDb = $lastPublishedDb;
    }

    /**
     * add query to upgrade actions
     * @param $query
     */
    protected function up($query)
    {
        if (!empty($query)) {
            $this->difference['up'][] = $query;
        }
    }

    /**
     * add query to downgrade action
     * @param $query
     */
    protected function down($query)
    {
        if (!empty($query)) {
            $this->difference['down'][] = $query;
        }
    }

    /**
     * get difference between databases
     * @return array - with two subarrays: "up" & "down"
     */
    public function getDifference()
    {
        $this->compareTables();

        $this->compareCommonTablesScheme();

        return $this->difference;
    }

    /**
     * get difference between tables in databases
     */
    protected function compareTables()
    {
        $currentTables = $this->currentDb->getTables();
        $lastPublishedTables = $this->publishedDb->getTables();

        $this->createTables = array_diff_key($currentTables, $lastPublishedTables);
        $this->dropTables = array_diff_key($lastPublishedTables, $currentTables);
        $this->commonTables = array_intersect_key($currentTables, $lastPublishedTables);

        foreach ($this->createTables as $tblName => $table) {
            $this->addCreateTable($tblName);
        }
        foreach ($this->dropTables as $tblName => $table) {
            $this->addDropTable($tblName);
        }

    }

    /**
     * add table creation action to "upgrade"
     * @param $tableName
     */
    protected function addCreateTable($tableName)
    {
        $this->down(Database::dropTable($tableName));

        //Database::dropTable()

        $this->up(Database::dropTable($tableName));
        $this->up(Database::createTable($tableName));
    }

    /**
     * add drop creation action to "upgrade"
     * @param $tableName
     */
    protected function addDropTable($tableName)
    {
        $this->up(Database::dropTable($tableName));
        $this->down(Database::dropTable($tableName));
        $this->down(Database::createTable($tableName));
    }

    /**
     * compare schemes of common tables
     */
    protected function compareCommonTablesScheme()
    {
        if (sizeof($this->commonTables) > 0) {
            foreach ($this->commonTables as $tblName => $table) {

                $currentTable = $this->currentDb->getTableColumns($tblName);
                $publishedTable = $this->publishedDb->getTableColumns($tblName);

                $this->createDifferenceInsideTable($tblName, $currentTable, $publishedTable);


                $this->createIndexDifference($tblName);
            }
        }
    }

    /**
     * get difference between two schemes of tables
     * @param $table
     * @param $tblCurrentCols
     * @param $tblPublishedCols
     */
    protected function createDifferenceInsideTable($table, $tblCurrentCols, $tblPublishedCols)
    {

        foreach ($tblCurrentCols as $currCol) {
            $colForCompare = $this->checkColumnExists($currCol, $tblPublishedCols);

            if (!$colForCompare) {
                $this->up(Database::addColumn($table, $currCol));
                $this->down(Database::dropColumn($table, $currCol));
            } else {
                if ($currCol === $colForCompare) {
                    continue;
                }
                $sql = Database::changeColumn($table, $currCol);
                $this->up($sql);
                $sql = Database::changeColumn($table, $colForCompare);
                $this->down($sql);
            }
        }


        foreach ($tblPublishedCols as $publishedColumn) {

            $has = $this->checkColumnExists($publishedColumn, $tblCurrentCols);

            if (!$has) {
                $constraint = $this->getConstraintForColumn($table, $publishedColumn['COLUMN_NAME']);

                if (count($constraint)) {
                    $this->down(Database::addConstraint(array('constraint' => $constraint)));
                    $this->up(Database::dropConstraint(array('constraint' => $constraint)));
                }
                $this->down(Database::addColumn($table, $publishedColumn));
                $this->up(Database::dropColumn($table, $publishedColumn));
            }
        }
    }


    /**
     * Get difference between table indexes
     *
     * @param $table
     */
    protected function createIndexDifference($table)
    {
        $currentIndexes = $this->currentDb->getIndexList($table);
        $publishedIndexes = $this->publishedDb->getIndexList($table);

        foreach ($currentIndexes as $curIndex) {
            $indexForCompare = $this->findIndex($curIndex, $publishedIndexes);
            if (!$indexForCompare) {
                $this->up(Database::addIndex($curIndex));
                $this->up(Database::addConstraint($curIndex));

                $this->down(Database::dropConstraint($curIndex));
                $this->down(Database::dropIndex($curIndex));
            } elseif ($indexForCompare !== $curIndex) {
                $this->up(Database::dropConstraint($curIndex));
                $this->up(Database::dropIndex($curIndex));

                $this->down(Database::dropConstraint($curIndex));
                $this->down(Database::dropIndex($curIndex));
                $this->down(Database::addIndex($indexForCompare));
                $this->down(Database::addConstraint($indexForCompare));
            }
        }

        //For creating deleted indexes
        $deletedIndexes = $this->getDeletedIndexes($currentIndexes, $publishedIndexes);
        if ($deletedIndexes) {
            foreach ($deletedIndexes as $deletedIndex) {
                //Create deleted index
                $this->up(Database::dropConstraint($deletedIndex));
                $this->up(Database::dropIndex($deletedIndex));
                //Delete index
                $this->down(Database::addConstraint($deletedIndex));
                $this->down(Database::addIndex($deletedIndex));
            }
        }
    }


    /**
     * @param $column
     * @param $colList
     * @return bool
     */
    protected function checkColumnExists($column, $colList)
    {

        return (array_key_exists($column['COLUMN_NAME'], $colList)) ?
            $colList[$column['COLUMN_NAME']] : false;

    }

    /**
     * Find Index exists
     * @param $index
     * @param $indexList
     * @return null | array - if index exist return index
     */
    protected function findIndex($index, $indexList)
    {
        foreach ($indexList as $comparingIndex) {
            if ($index['name'] === $comparingIndex['name']) {
                return $comparingIndex;
            }
        }
        return null;
    }


    /**
     * Get deleted indexes in current DB
     *
     * @param array $currentIndexes
     * @param array $publishedIndexes
     * @return array
     */
    protected function getDeletedIndexes($currentIndexes, $publishedIndexes)
    {
        $nonExistIndexes = array();
        foreach ($publishedIndexes as $publishedIndex) {
            $exist = false;
            foreach ($currentIndexes as $currentIndex) {

                if ($currentIndex['name'] === $publishedIndex['name']) {
                    $exist = true;
                    break;
                }
            }

            if (!$exist) {
                $nonExistIndexes[] = $publishedIndex;
            }
        }
        return $nonExistIndexes;
    }


    protected function getConstraintForColumn($table, $colName)
    {
        /** @var $statement StatementInterface */
        $statement = $this->db->query("select database() as dbname");
        $results = $statement->execute();
        $row = $results->current();

        $dbName = $row['dbname'];

        $sql = "SELECT k.CONSTRAINT_SCHEMA,
                       k.CONSTRAINT_NAME,
                       k.TABLE_NAME,
                       k.COLUMN_NAME,
                       k.REFERENCED_TABLE_NAME,
                       k.REFERENCED_COLUMN_NAME,
                       r.UPDATE_RULE,
                       r.DELETE_RULE
                       FROM information_schema.key_column_usage k
                       LEFT JOIN information_schema.referential_constraints r
                       ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
                       AND k.REFERENCED_TABLE_NAME=r.REFERENCED_TABLE_NAME
                       LEFT JOIN information_schema.table_constraints t
                       ON t.CONSTRAINT_SCHEMA = r.CONSTRAINT_SCHEMA
                       WHERE
                        k.constraint_schema='$dbName'
                        AND t.CONSTRAINT_TYPE='FOREIGN KEY'
                        AND k.TABLE_NAME='$table'
                        AND r.TABLE_NAME='$table'
                        AND t.TABLE_NAME='$table'
                        AND k.COLUMN_NAME='$colName'";


        /** @var $statement StatementInterface */
        $statement = $this->db->query($sql);
        $results = $statement->execute();
        $row = $results->current();

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
}

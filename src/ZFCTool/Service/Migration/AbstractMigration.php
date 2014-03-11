<?php
/**
 * User: naxel
 * Date: 19.02.14 13:06
 */

namespace ZFCTool\Service\Migration;

use Zend\Db\Adapter\Adapter;
use ZFCTool\Exception\ZFCToolException;
use ZFCTool\Service\Migration\Adapter\AbstractAdapter;
use Zend\Db\Adapter\Driver\Pdo\Pdo;
use ZFCTool\Service\Migration\Adapter\Mysql;
use ZFCTool\Service\MigrationManager;

abstract class AbstractMigration
{

    /**
     * Currently Supported Data Types
     *
     * @see http://framework.zend.com/manual/2.2/en/modules/zend.db.sql.ddl.html#currently-supported-data-types
     */
    const TYPE_BIGINT = 'bigint';

    const TYPE_BLOB = 'blob';

    const TYPE_BOOLEAN = 'boolean';

    const TYPE_CHAR = 'char';

    const TYPE_DATE = 'date';

    const TYPE_DECIMAL = 'decimal';

    const TYPE_FLOAT = 'float';

    const TYPE_INT = 'int';

    const TYPE_TEXT = 'text';

    const TYPE_TIME = 'time';

    const TYPE_VARCHAR = 'varchar';

    const TYPE_LONGTEXT = 'longtext';
    const TYPE_ENUM = 'enum';
    const TYPE_DATETIME = 'datetime';
    const TYPE_TIMESTAMP = 'timestamp';

    /**
     * Default Database adapter
     *
     * @var Adapter
     */
    protected $db = null;

    /**
     * migration Adapter
     *
     * @var AbstractAdapter
     */
    protected $_migrationAdapter = null;

    /**
     * migration manager
     *
     * @var MigrationManager
     */
    protected $_migrationManager = null;


    /**
     * @param Adapter $dbAdapter
     */
    public function __construct(Adapter $dbAdapter)
    {
        $this->db = $dbAdapter;
    }

    /**
     * up
     *
     * update DB from migration
     *
     * @return  AbstractMigration
     */
    abstract public function up();

    /**
     * down
     *
     * degrade DB from migration
     *
     * @return  AbstractMigration
     */
    abstract public function down();


    /**
     * getDbAdapter
     *
     * @return Adapter
     */
    public function getDbAdapter()
    {
        return $this->db;
    }

    /**
     * setMigrationMananger
     *
     * @param MigrationManager $migrationManager
     * @return AbstractMigration
     */
    public function setMigrationManager(MigrationManager $migrationManager)
    {
        $this->_migrationManager = $migrationManager;
        return $this;
    }


    /**
     * getMigrationManager
     *
     * @return MigrationManager
     * @throws \ZFCTool\Exception\ZFCToolException
     */
    public function getMigrationManager()
    {
        if (!$this->_migrationManager) {
            throw new ZFCToolException('Migration manager is not set');
        }
        return $this->_migrationManager;
    }


    /**
     * setMigrationAdapter
     *
     * @return AbstractMigration
     * @throws \ZFCTool\Exception\ZFCToolException
     */
    public function setMigrationAdapter()
    {

        if ($this->getDbAdapter()->getDriver() instanceof Pdo) {
            $className = '\ZFCTool\Service\Migration\Adapter\Mysql';
            /*} elseif ($this->getDbAdapter() instanceof Zend_Db_Adapter_Pdo_Sqlite) {
                $className = 'Core_Migration_Adapter_Sqlite';
            } elseif ($this->getDbAdapter() instanceof Zend_Db_Adapter_Pdo_Pgsql) {
                $className = 'Core_Migration_Adapter_Pgsql';*/
        } else {
            throw new ZFCToolException("This type of adapter not suppotred");
        }
        $this->_migrationAdapter = new $className($this->getDbAdapter());

        return $this;
    }

    /**
     * getMigrationAdapter
     *
     * @return Adapter
     */
    public function getMigrationAdapter()
    {
        if (!$this->_migrationAdapter) {
            $this->setMigrationAdapter();
        }
        return $this->_migrationAdapter;
    }


    /**
     * @throws \ZFCTool\Exception\ZFCToolException
     */
    public function stop()
    {
        throw new ZFCToolException('This is final migration');
    }

    /**
     * query
     *
     * @param  string $query
     * @param  array $bind
     * @return AbstractMigration
     */
    public function query($query, $bind = array())
    {
        $this->getMigrationAdapter()->query($query, $bind);
        return $this;
    }

    /**
     * insert
     *
     * @param   string $table
     * @param   array $params
     * @return  int The number of affected rows.
     */
    public function insert($table, array $params)
    {
        $this->getMigrationAdapter()->insert($table, $params);
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
        $this->getMigrationAdapter()->update($table, $bind, $where);
        return $this;
    }

    /**
     * createTable
     *
     * @param   string $table table name
     * @return  AbstractMigration
     */
    public function createTable($table)
    {
        $this->getMigrationAdapter()->createTable($table);
        return $this;
    }

    /**
     * dropTable
     *
     * @param   string $table table name
     * @return  AbstractMigration
     */
    public function dropTable($table)
    {
        $this->getMigrationAdapter()->dropTable($table);
        return $this;
    }

    /**
     * createColumn
     *
     * FIXME: requried quoted queries data
     *
     * @param   string $tableName
     * @param   string $name
     * @param   string $dataType
     * @param   string $length
     * @param   string $default
     * @param   bool $nullable
     * @param   bool $primary
     * @return  AbstractMigration
     */
    public function createColumn(
        $tableName,
        $name,
        $dataType,
        $length = null,
        $default = null,
        $nullable = true,
        $primary = false
    )
    {
        //if ($default && self::DEFAULT_CURRENT_TIMESTAMP == $default) {
        //    $default = $this->getMigrationAdapter()->getCurrentTimestamp();
        //}
        $this->getMigrationAdapter()->createColumn(
            $tableName,
            $name,
            $dataType,
            $length,
            $default,
            $nullable,
            $primary
        );

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
        $this->getMigrationAdapter()->dropColumn($table, $name);
        return $this;
    }

    /**
     * createUniqueIndexes
     *
     * @param   string $table
     * @param   array $columns
     * @param   string $indName
     * @return  bool
     */
    public function createUniqueIndexes($table, array $columns, $indName = null)
    {
        $this->getMigrationAdapter()
            ->createUniqueIndexes($table, $columns, $indName);

        return $this;
    }

    /**
     * dropColumn
     *
     * @param   string $table
     * @param            $indName
     * @internal param array $columns
     * @return  AbstractMigration
     */
    public function dropUniqueIndexes($table, $indName)
    {
        $this->getMigrationAdapter()->dropUniqueIndexes($table, $indName);

        return $this;
    }
}

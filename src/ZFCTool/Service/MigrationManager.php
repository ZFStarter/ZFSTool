<?php
/**
 * User: naxel
 * Date: 24.02.14 10:47
 */

namespace ZFCTool\Service;

use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Db\Sql\Ddl;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\Db\Adapter\Driver\StatementInterface;
use Zend\Db\Adapter\Adapter;
use Zend\ServiceManager\ServiceLocatorInterface;

use ZFCTool\Exception\ConflictedMigrationException;
use ZFCTool\Exception\MigrationExecutedException;
use ZFCTool\Exception\MigrationNotLoadedException;
use ZFCTool\Exception\NoMigrationsForExecutionException;
use ZFCTool\Exception\OldMigrationException;
use ZFCTool\Exception\YoungMigrationException;
use ZFCTool\Exception\ZFCToolException;
use ZFCTool\Exception\CurrentMigrationException;
use ZFCTool\Exception\IncorrectMigrationNameException;
use ZFCTool\Exception\MigrationNotExistsException;
use ZFCTool\Service\Migration\AbstractMigration;
use ZFCTool\Service\Database\Diff;

class MigrationManager
{

    //Migration status type
    const MIGRATION_TYPE_READY = 'ready';

    const MIGRATION_TYPE_LOADED = 'loaded';

    const MIGRATION_TYPE_NOT_EXIST = 'not_exist';

    const MIGRATION_TYPE_CONFLICT = 'conflict';

    /**
     * @var \Zend\Db\Adapter\Adapter
     */
    protected $db;

    /**
     * Variable contents options
     *
     * @var array
     */
    protected $options = array(
        // Migrations schema table name
        'migrationsSchemaTable' => 'migrations',
        // Path to project directory
        'projectDirectoryPath' => null,
        // Path to modules directory
        'modulesDirectoryPath' => null,
        // Migrations directory name
        'migrationsDirectoryName' => 'migrations',
    );

    /**
     * Message stack
     *
     * @var array
     */
    protected $messages = array();

    /** @var  ServiceLocatorInterface */
    protected $serviceLocator;

    /**
     * Check before start transaction
     *
     * @var bool
     */
    protected $transactionFlag = false;


    /**
     * @param $serviceLocator
     * @throws ZFCToolException
     */
    public function __construct($serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;

        $config = $this->serviceLocator->get('Config');

        $this->options = array_merge($this->options, $config['ZFCTool']['migrations']);

        /** @var $db Adapter */
        $this->db = $this->serviceLocator->get('Zend\Db\Adapter\Adapter');

        $this->createTable();
    }


    /**
     * Method returns stack of messages
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }


    /**
     * @param string $messages
     */
    public function addMessage($messages)
    {
        array_push($this->messages, $messages);
    }

    /**
     * Clear all messages
     */
    public function clearMessages()
    {
        $this->messages = array();
    }


    /**
     * Method return application directory path
     *
     * @throws ZFCToolException
     * @return string
     */
    public function getProjectDirectoryPath()
    {
        if (null == $this->options['projectDirectoryPath']) {
            throw new ZFCToolException('Project directory path undefined.');
        }

        return $this->options['projectDirectoryPath'];
    }

    /**
     * Method set application directory path
     *
     * @param  string $value
     * @return MigrationManager
     */
    public function setProjectDirectoryPath($value)
    {
        $this->options['projectDirectoryPath'] = $value;
        return $this;
    }

    /**
     * Method return application directory path
     *
     * @throws ZFCToolException
     * @return string
     */
    public function getModulesDirectoryPath()
    {
        if (null == $this->options['modulesDirectoryPath']) {
            throw new ZFCToolException('Modules directory path undefined.');
        }

        return $this->options['modulesDirectoryPath'];
    }

    /**
     * Method set application directory path
     *
     * @param string $value
     * @return MigrationManager
     */
    public function setModulesDirectoryPath($value)
    {
        $this->options['modulesDirectoryPath'] = $value;
        return $this;
    }

    /**
     * Method get migrations directory name
     *
     * @throws ZFCToolException
     * @return string
     */
    public function getMigrationsDirectoryName()
    {
        if (null == $this->options['migrationsDirectoryName']) {
            throw new ZFCToolException('Migrations directory name undefined.');
        }

        return $this->options['migrationsDirectoryName'];
    }


    /**
     * Method set migrations directory name
     *
     * @param string $name
     */
    public function setMigrationsDirectoryName($name)
    {
        $this->options['migrationsDirectoryName'] = $name;
    }

    /**
     * Method returns path to migrations directory
     *
     * @param string $module Module name
     * @throws ZFCToolException
     * @return string
     */
    public function getMigrationsDirectoryPath($module = null)
    {
        if (null == $module) {
            $path = $this->getProjectDirectoryPath();
            $path .= '/' . $this->getMigrationsDirectoryName();
        } else {
            $modulePath = $this->getModulesDirectoryPath() . '/' . $module;

            if (!file_exists($modulePath)) {
                throw new ZFCToolException("Module `$module` not exists.");
            }

            $path = $modulePath . '/' . $this->getMigrationsDirectoryName();
        }

        $this->preparePath($path);

        return $path;
    }


    /**
     * Method return migrations schema table
     *
     * @return string
     */
    public function getMigrationsSchemaTable()
    {
        return $this->options['migrationsSchemaTable'];
    }

    /**
     * Create migrations table
     */
    public function createTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `" . $this->getMigrationsSchemaTable() . "`(
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `module` varchar(128) NOT NULL,
                `migration` varchar(64) NOT NULL,
                `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `state` longtext,
                PRIMARY KEY (`id`),
                UNIQUE KEY `UNIQUE_MIGRATION` (`module`,`migration`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
        ";

        /** @var $statement StatementInterface */
        $statement = $this->db->query($sql);
        $statement->execute();
    }


    /**
     * @param null|string $module
     * @param null|string $migrationBody
     * @return string
     */
    public function create($module = null, $migrationBody = null)
    {
        $path = $this->getMigrationsDirectoryPath($module);

        list(, $mSec) = explode(".", microtime(true));
        $migrationName = date('Ymd_His_') . substr($mSec, 0, 2);


        $methodUp = array(
            'name' => 'up',
            'docblock' => DocBlockGenerator::fromArray(
                array(
                    'shortDescription' => 'Upgrade',
                    'longDescription' => null,
                )
            ),
        );

        $methodDown = array(
            'name' => 'down',
            'docblock' => DocBlockGenerator::fromArray(
                array(
                    'shortDescription' => 'Degrade',
                    'longDescription' => null,
                )
            ),
        );


        if ($migrationBody) {
            if (isset($migrationBody['up'])) {
                $upBody = '';
                foreach ($migrationBody['up'] as $query) {
                    $upBody .= '$this->query(\'' . $query . '\');' . PHP_EOL;
                }
                $methodUp['body'] = $upBody;
            }
            if (isset($migrationBody['down'])) {
                $downBody = '';
                foreach ($migrationBody['down'] as $query) {
                    $downBody .= '$this->query(\'' . $query . '\');';
                }
                $methodDown['body'] = $downBody;
            }
        }


        $class = new ClassGenerator();
        $class->setName('Migration_' . $migrationName)
            ->setExtendedClass('AbstractMigration')
            ->addUse('ZFCTool\Service\Migration\AbstractMigration')
            ->addMethods(
                array(
                    // Method passed as array
                    MethodGenerator::fromArray($methodUp),
                    MethodGenerator::fromArray($methodDown),
                )
            );

        $file = new FileGenerator(
            array(
                'classes' => array($class),
            )
        );

        $code = $file->generate();

        $migrationPath = $path . '/' . $migrationName . '.php';
        file_put_contents($migrationPath, $code);

        return $migrationPath;
    }

    /**
     * Method prepare path (create not existing dirs)
     *
     * @param string $path
     */
    protected function preparePath($path)
    {
        if (!is_dir($path)) {
            $this->preparePath(dirname($path));
            mkdir($path, 0777);
        }
    }


    /**
     * Method returns array of exists in filesystem migrations
     *
     * @param string $module Module name
     * @return array
     */
    public function getExistsMigrations($module = null)
    {
        $filesDirty = scandir($this->getMigrationsDirectoryPath($module));

        $migrations = array();
        // foreach loop for $filesDirty array
        foreach ($filesDirty as $file) {
            if (preg_match('/\d{8}_\d{6}_\d{2}\.php$/', $file)
                || preg_match('/\d{8}_\d{6}_\d{2}_[A-z0-9]*\.php$/', $file)
            ) {
                array_push($migrations, substr($file, 0, -4));
            }
        }

        sort($migrations);

        return $migrations;
    }


    /**
     * Method return array of loaded migrations
     *
     * @param string $module Module name
     * @return array
     */
    public function getLoadedMigrations($module = null)
    {
        $sql = new Sql($this->db);
        $select = $sql->select()
            ->from($this->getMigrationsSchemaTable())
            ->order('migration ASC');

        if ($module) {
            $where = new Where();
            $where->equalTo('module', $module);
            $select->where($where);
        }

        $statement = $sql->prepareStatementForSqlObject($select);
        $items = $statement->execute();

        $migrations = array();
        foreach ($items as $item) {
            $migrations[] = $item['migration'];
        }

        return $migrations;
    }

    /**
     * execute array from string
     * @param $str
     * @return array
     */
    protected function strToArray($str)
    {
        if (!empty($str)) {

            if (strpos($str, ',')) {
                return explode(',', $str);
            }
            return array($str);
        } else {
            return array();
        }
    }


    /**
     * Get last data base state
     *
     * @return string
     */
    protected function getLastDbState()
    {
        $lastMigration = $this->getLastMigration();

        $sql = new Sql($this->db);
        $select = $sql->select();

        $query = $select->from($this->options['migrationsSchemaTable']);

        $where = new Where();
        $where->equalTo('id', $lastMigration['id']);
        $select->where($where);

        $selectString = $sql->getSqlStringForSqlObject($query);
        $result = $this->db->query($selectString, Adapter::QUERY_MODE_EXECUTE);

        if ($result) {
            $row = $result->current();
            if ($row) {
                return $row->state;
            }
        }

        return null;
    }


    /**
     * Method returns last migration for selected module
     *
     * @param null $module
     * @throws ZFCToolException
     * @return array
     */
    public function getLastMigration($module = null)
    {
        try {
            $sql = new Sql($this->db);
            $select = $sql->select();
            $select->from($this->getMigrationsSchemaTable())
                ->order('id DESC')
                ->limit(1);

            if ($module) {
                $where = new Where();
                $where->equalTo('module', $module);
                $select->where($where);
            }

            $statement = $sql->prepareStatementForSqlObject($select);
            $results = $statement->execute();
            $lastMigration = $results->current();

            if (!$lastMigration) {
                throw new ZFCToolException(
                    "Not found migration version in database"
                );
            }
        } catch (\Exception $e) {
            // maybe table is not exist; this is first revision
            $lastMigration = array('id' => 0, 'migration' => 0);
        }

        return $lastMigration;
    }


    /**
     * Listing migrations
     *
     * @param null|string $module
     * @return array
     */
    public function listMigrations($module = null)
    {
        $result = array();
        $lastMigration = $this->getLastMigration($module);
        $lastMigrationName = $lastMigration['migration'];

        $exists = $this->getExistsMigrations($module);
        $loaded = $this->getLoadedMigrations($module);

        $migrations = array_merge($exists, $loaded);
        $migrations = array_unique($migrations);

        sort($migrations);

        foreach ($migrations as $migration) {
            $v = 0;
            if (in_array($migration, $exists)) {
                $v = $v + 1;
            }
            if (in_array($migration, $loaded)) {
                $v = $v + 2;
            }

            $type = self::MIGRATION_TYPE_READY;

            switch ($v) {
                case 1:
                    if ($migration < $lastMigrationName) {
                        $type = self::MIGRATION_TYPE_CONFLICT;
                    } else {
                        $type = self::MIGRATION_TYPE_READY;
                    }
                    break;
                case 2:
                    $type = self::MIGRATION_TYPE_NOT_EXIST;
                    break;
                case 3:
                    $type = self::MIGRATION_TYPE_LOADED;
                    break;
            }
            $result[] = array('name' => $migration, 'type' => $type);
        }
        return $result;
    }

    /**
     * get difference between current db state and last db state, after this
     * create migration with auto-generated queries
     *
     * @param null $module
     * @param string $blacklist
     * @param string $whitelist
     * @param bool $showDiff
     * @return array|bool|string
     */

    public function generateMigration($module = null, $blacklist = '', $whitelist = '', $showDiff = false)
    {
        $blkListedTables = array();
        $blkListedTables[] = $this->options['migrationsSchemaTable'];
        $blkListedTables = array_merge($blkListedTables, $this->strToArray($blacklist));

        $whtListedTables = array();
        $whtListedTables = array_merge($whtListedTables, $this->strToArray($whitelist));

        $options = array();
        $options['blacklist'] = $blkListedTables;

        if (sizeof($whtListedTables) > 0) {
            $options['whitelist'] = $whtListedTables;
        }

        $currDb = new Database($this->db, $options);

        $lastPublishedDb = new Database($this->db, $options, false);

        $lastPublishedDb->fromString($this->getLastDbState());

        $diff = new Diff($this->db, $currDb, $lastPublishedDb);
        $difference = $diff->getDifference();

        if (!count($difference['up']) && !count($difference['down'])) {
            return false;
        } else {
            if ($showDiff) {
                return $difference;
            } else {
                return $this->create($module, $difference);
            }
        }
    }

    /**
     * @param $module
     * @param $migration
     * @return mixed
     * @throws ZFCToolException
     */
    public function commit($module, $migration)
    {
        $lastMigration = $this->getLastMigration($module);

        if ($migration) {
            if (!self::isMigration($migration)) {
                throw new IncorrectMigrationNameException("Migration name `$migration` is not valid");
            } elseif ($lastMigration['migration'] == $migration) {
                throw new CurrentMigrationException("Migration `$migration` is current");
            }

            $exists = $this->getExistsMigrations($module);

            if (!in_array($migration, $exists)) {
                throw new MigrationNotExistsException("Migration `$migration` not exists");
            }

            $loaded = $this->getLoadedMigrations($module);

            if (in_array($migration, $loaded)) {
                throw new MigrationExecutedException("Migration `$migration` already executed");
            }

            $this->pushMigration($module, $migration);

            // add db state to migration
            $this->updateDbState($module, $migration);

        } else {
            throw new IncorrectMigrationNameException('Need migration name for fake upgrade.');
        }
    }

    /**
     * Method check string, if string valid migration name returns true
     *
     * @param string $value String to check
     * @return boolean
     */
    public static function isMigration($value)
    {
        return ('0' == $value) || preg_match('/^\d{8}_\d{6}_\d{2}$/', $value) ||
        preg_match('/\d{8}_\d{6}_\d{2}_[A-z0-9]*$/', $value);
    }


    /**
     * Method add migration to schema table
     *
     * @param string $module Module name
     * @param string $migration Migration name
     * @return $this
     */
    protected function pushMigration($module, $migration)
    {
        if (null === $module) {
            $module = '';
        }

        try {
            $sql = new Sql($this->db);
            $insert = $sql->insert($this->getMigrationsSchemaTable());
            $insert->values(
                array(
                    'module' => $module,
                    'migration' => $migration
                )
            );
            $selectString = $sql->getSqlStringForSqlObject($insert);
            $this->db->query($selectString, Adapter::QUERY_MODE_EXECUTE);

        } catch (\Exception $e) {
            // table is not exist
        }

        return $this;
    }


    /**
     * Method remove migration from schema table
     *
     * @param string $module Module name
     * @param string $migration Migration name
     * @return $this
     */
    protected function pullMigration($module, $migration)
    {
        if (null === $module) {
            $module = '';
        }

        try {

            $sql = new Sql($this->db);
            $delete = $sql->delete($this->getMigrationsSchemaTable());
            $delete->where(
                array(
                    'module' => $module,
                    'migration' => $migration
                )
            );
            $selectString = $sql->getSqlStringForSqlObject($delete);
            $this->db->query($selectString, Adapter::QUERY_MODE_EXECUTE);

        } catch (\Exception $e) {
            // table is not exist
        }

        return $this;
    }


    /**
     * Method downgrade all migration or migrations to selected
     *
     * @param string $module Module name
     * @param string $to Migration name
     * @throws \ZFCTool\Exception\ZFCToolException
     */
    public function down($module, $to = null)
    {
        $lastMigration = $this->getLastMigration($module);
        $lastMigration = $lastMigration['migration'];

        if (!$lastMigration) {
            throw new NoMigrationsForExecutionException("Not one migration is not accepted");
        }

        if (null !== $to) {
            if (!self::isMigration($to)) {
                throw new IncorrectMigrationNameException("Migration name `$to` is not valid");
//            } elseif ($lastMigration == $to) {
//                throw new CurrentMigrationException("Migration `$to` is current");
            } elseif ($lastMigration < $to) {
                throw new YoungMigrationException(
                    "Migration `$to` is younger than current migration `$lastMigration`"
                );
            }
        }

        $exists = $this->getExistsMigrations($module);
        $loaded = $this->getLoadedMigrations($module);

        rsort($loaded);

        if (($to) && (!in_array($to, $loaded))) {

            throw new MigrationNotLoadedException("Migration `$to` not loaded");
        }

        foreach ($loaded as $migration) {

            if (!in_array($migration, $exists)) {
                throw new MigrationNotExistsException("Migration `$migration` not exists");
            }

            try {
                $includePath = $this->getMigrationsDirectoryPath($module)
                    . '/' . $migration . '.php';

                include_once $includePath;

                $migrationClass = 'Migration_' . $migration;
                $migrationObject = new $migrationClass($this->db);
                /** @var AbstractMigration $migrationObject */
                $migrationObject->setMigrationManager($this);

                if (!$this->transactionFlag) {
                    $connection = $migrationObject->getDbAdapter()->getDriver()->getConnection();
                    $connection->beginTransaction();

                    $this->transactionFlag = true;
                    try {
                        $migrationObject->down();
                        $connection->commit();
                    } catch (\Exception $e) {
                        $connection->rollback();
                        throw new ZFCToolException(
                            "Migration `$migration` return exception:\n"
                            . $e->getMessage() . ' in ' . $e->getFile() . '#' . $e->getLine()
                        );
                    }
                    $this->transactionFlag = false;
                } else {
                    $migrationObject->down();
                }

                if ($module) {
                    $this->addMessage($module . ": degrade revision `$migration`");
                } else {
                    $this->addMessage("Degrade revision `$migration`");
                }

                $this->pullMigration($module, $migration);
            } catch (\Exception $e) {
                throw new ZFCToolException(
                    "Migration `$module`.`$migration` return exception:\n"
                    . $e->getMessage() . ' in ' . $e->getFile() . '#' . $e->getLine()
                );
            }

            if (($to) && ($migration == $to)) {
                break;
            }
        }
    }


    /**
     * Method upgrade all migration or migrations to selected
     *
     * @param string $module Module name
     * @param string $to Migration name
     *
     * @throws \ZFCTool\Exception\IncorrectMigrationNameException
     * @throws \ZFCTool\Exception\ConflictedMigrationException
     * @throws \ZFCTool\Exception\MigrationNotExistsException
     * @throws \ZFCTool\Exception\OldMigrationException
     * @throws \ZFCTool\Exception\ZFCToolException
     * @throws \ZFCTool\Exception\NoMigrationsForExecutionException
     * @throws \ZFCTool\Exception\CurrentMigrationException
     */
    public function up($module = null, $to = null)
    {
        $lastMigration = $this->getLastMigration($module);
        $lastMigration = $lastMigration['migration'];

        if ($to) {
            if (!self::isMigration($to)) {
                throw new IncorrectMigrationNameException("Migration name `$to` is not valid");
            } elseif ($lastMigration == $to) {
                throw new CurrentMigrationException("Migration `$to` is current");
            } elseif ($lastMigration > $to) {
                throw new OldMigrationException(
                    "Migration `$to` is older than current migration `$lastMigration`"
                );
            }
        }

        $exists = $this->getExistsMigrations($module);
        $loaded = $this->getLoadedMigrations($module);

        $ready = array_diff($exists, $loaded);

        if (sizeof($ready) == 0) {
            if ($module) {
                throw new NoMigrationsForExecutionException($module . ': no migrations to upgrade.');
            } else {
                throw new NoMigrationsForExecutionException('No migrations to upgrade.');
            }
        }

        sort($ready);

        if (($to) && (!in_array($to, $exists))) {
            throw new MigrationNotExistsException("Migration `$to` not exists");
        }

        foreach ($ready as $migration) {
            if ($migration < $lastMigration) {
                throw new ConflictedMigrationException("Migration `$migration` is conflicted");
            }

            try {
                $includePath = $this->getMigrationsDirectoryPath($module)
                    . '/' . $migration . '.php';

                include_once $includePath;

                $migrationClass = 'Migration_' . $migration;

                /** @var AbstractMigration $migrationObject */
                $migrationObject = new $migrationClass($this->db);
                $migrationObject->setMigrationManager($this);

                if (!$this->transactionFlag) {
                    $connection = $migrationObject->getDbAdapter()->getDriver()->getConnection();
                    $connection->beginTransaction();
                    $this->transactionFlag = true;
                    try {
                        $migrationObject->up();
                        $connection->commit();
                    } catch (\Exception $e) {
                        $connection->rollBack();
                        throw new ZFCToolException(
                            "Migration `$migration` return exception:\n"
                            . $e->getMessage() . ' in ' . $e->getFile() . '#' . $e->getLine()
                        );
                    }
                    $this->transactionFlag = false;
                } else {
                    $migrationObject->up();
                }

                if ($module) {
                    $this->addMessage($module . ": upgrade to revision `$migration`");
                } else {
                    $this->addMessage("Upgrade to revision `$migration`");
                }

                $this->pushMigration($module, $migration);

                // add db state to migration
                $this->updateDbState($module, $migration);


            } catch (\Exception $e) {
                throw new ZFCToolException(
                    "Migration `$migration` return exception:\n"
                    . $e->getMessage() . ' in ' . $e->getFile() . '#' . $e->getLine()
                );
            }

            if (($to) && ($migration == $to)) {
                break;
            }
        }
    }


    /**
     * Add db state to migration
     *
     * @param string|null $module
     * @param string| $migration
     */
    protected function updateDbState($module, $migration)
    {
        $db = new Database($this->db, array('blacklist' => $this->options['migrationsSchemaTable']));
        $sql = new Sql($this->db);
        $update = $sql->update($this->getMigrationsSchemaTable());
        $where = new Where();
        $where->equalTo('module', $module)
            ->and
            ->equalTo('migration', $migration);
        $update->where($where);
        $update->set(
            array(
                'state' => $db->toString()
            )
        );
        $selectString = $sql->getSqlStringForSqlObject($update);
        $this->db->query($selectString, Adapter::QUERY_MODE_EXECUTE);
    }


    /**
     * Method rollback last migration or few last migrations
     *
     * @param string $module Module name
     * @param int $step Steps to rollback
     * @throws \ZFCTool\Exception\ZFCToolException
     * @throws \ZFCTool\Exception\NoMigrationsForExecutionException
     */
    public function rollback($module, $step)
    {
        if (!is_numeric($step) || ($step <= 0)) {
            throw new ZFCToolException("Step count `$step` is invalid");
        }

        $exists = $this->getExistsMigrations($module);
        $loaded = $this->getLoadedMigrations($module);

        if (sizeof($loaded) == 0) {
            throw new NoMigrationsForExecutionException('No migrations to rollback.');
        }

        rsort($loaded);

        foreach ($loaded as $migration) {

            if (!in_array($migration, $exists)) {
                throw new MigrationNotExistsException("Migration `$migration` not exists");
            }

            try {
                $includePath = $this->getMigrationsDirectoryPath($module)
                    . '/' . $migration . '.php';

                include_once $includePath;

                $migrationClass = 'Migration_' . $migration;
                /** @var AbstractMigration $migrationObject */
                $migrationObject = new $migrationClass($this->db);

                $connection = $migrationObject->getDbAdapter()->getDriver()->getConnection();
                $connection->beginTransaction();
                try {
                    $migrationObject->down();
                    $connection->commit();
                } catch (\Exception $e) {
                    $connection->rollback();
                    throw new ZFCToolException(
                        "Migration `$migration` return exception:\n"
                        . $e->getMessage() . ' in ' . $e->getFile() . '#' . $e->getLine()
                    );
                }

                $this->addMessage("Degrade migration '$migration'");

                $this->pullMigration($module, $migration);
            } catch (\Exception $e) {
                throw new ZFCToolException(
                    "Migration `$module`.`$migration` return exception:\n"
                    . $e->getMessage() . ' in ' . $e->getFile() . '#' . $e->getLine()
                );
            }

            $step--;
            if ($step <= 0) {
                break;
            }
        }
    }
}

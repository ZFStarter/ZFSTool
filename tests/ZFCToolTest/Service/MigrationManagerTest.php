<?php
/**
 * User: naxel
 * Date: 25.02.14 13:23
 */

namespace ZFCToolTest\Service;

use ZFCToolTest\Bootstrap;
use ZFCTool\Service\MigrationManager;
use PHPUnit_Framework_TestCase;

use ZFCTool\Service\Migration\Adapter\Mysql;
use ZFCTool\Service\Database;
use ZFCTool\Service\Migration\AbstractMigration;
use Zend\Db\Adapter\Adapter;
use ZFCTool\Exception\ZFCToolException;
use ZFCTool\Exception\IncorrectMigrationNameException;
use ZFCTool\Exception\CurrentMigrationException;
use ZFCTool\Exception\MigrationNotExistsException;
use ZFCTool\Exception\MigrationExecutedException;
use ZFCTool\Exception\YoungMigrationException;
use ZFCTool\Exception\NoMigrationsForExecutionException;

class MigrationManagerTest extends \PHPUnit_Framework_TestCase
{
    const FIXTURE_MODULE = 'simplemodule';

    /**
     * @var MigrationManager
     */
    protected static $manager;

    /**
     * @var \Zend\Db\Adapter\Adapter
     */
    protected static $db;


    public static function setUpBeforeClass()
    {
        /** @var $serviceManager \Zend\ServiceManager\ServiceManager */
        $serviceManager = Bootstrap::getServiceManager();
        self::$manager = new MigrationManager($serviceManager);
        self::$db = $serviceManager->get('Zend\Db\Adapter\Adapter');
    }

    protected function setUp()
    {
        self::$manager->createTable();
    }

    protected function tearDown()
    {
        $result = self::$db->query("SHOW TABLES LIKE 'items_%';", Adapter::QUERY_MODE_EXECUTE);

        foreach ($result->toArray() as $data) {
            foreach ($data as $tableName) {
                self::$db->query("DROP TABLE `" . $tableName . "`", Adapter::QUERY_MODE_EXECUTE);
            }
        }

        self::$db->query(
            "DROP TABLE `" . self::$manager->getMigrationsSchemaTable() . "`",
            Adapter::QUERY_MODE_EXECUTE
        );
    }

    public function testSetModulesDirectoryPath()
    {
        //Store
        $modulesDirectoryPath = self::$manager->getModulesDirectoryPath();

        self::$manager->setModulesDirectoryPath('/test/path');
        $this->assertEquals('/test/path', self::$manager->getModulesDirectoryPath());
        //Restore
        self::$manager->setModulesDirectoryPath($modulesDirectoryPath);
    }


    public function testSetProjectDirectoryPath()
    {
        //Store
        $projectDirectoryPath = self::$manager->getProjectDirectoryPath();

        self::$manager->setProjectDirectoryPath('/test/path');
        $this->assertEquals('/test/path', self::$manager->getProjectDirectoryPath());
        //Restore
        self::$manager->setProjectDirectoryPath($projectDirectoryPath);
    }


    public function testGetProjectDirectoryPathExceptions()
    {
        //Store
        $projectDirectoryPath = self::$manager->getProjectDirectoryPath();

        self::$manager->setProjectDirectoryPath(null);
        try {
            self::$manager->getProjectDirectoryPath();
        } catch (ZFCToolException $expected) {
            $this->assertTrue(true);
            //Restore
            self::$manager->setProjectDirectoryPath($projectDirectoryPath);
            return;
        }

        $this->fail('An expected Exception has not been raised.');
    }


    public function testGetModulesDirectoryPathByModuleExceptions()
    {
        //Store
        $modulesDirectoryPath = self::$manager->getModulesDirectoryPath();
        self::$manager->setModulesDirectoryPath(null);
        try {
            self::$manager->getModulesDirectoryPath();
        } catch (ZFCToolException $expected) {
            $this->assertTrue(true);
            //Restore
            self::$manager->setModulesDirectoryPath($modulesDirectoryPath);
            return;
        }

        $this->fail('An expected Exception has not been raised.');
    }

    public function testGetMigrationsDirectoryNameExceptions()
    {
        //Store
        $migrationsDirectoryName = self::$manager->getMigrationsDirectoryName();
        self::$manager->setMigrationsDirectoryName(null);
        try {
            self::$manager->getMigrationsDirectoryName();
        } catch (ZFCToolException $expected) {
            $this->assertTrue(true);
            //Restore
            self::$manager->setMigrationsDirectoryName($migrationsDirectoryName);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }


    public function testGetMigrationsDirectoryPathExceptions()
    {
        try {
            self::$manager->getMigrationsDirectoryPath('unknownModule');
        } catch (ZFCToolException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }

    public function testFakeIncorrectMigrationNameException()
    {
        try {
            self::$manager->commit(null, 'fake');
        } catch (IncorrectMigrationNameException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }

    public function testFakeCurrentMigrationExceptions()
    {
        self::$manager->up(null, '99999999_000000_00');

        try {
            self::$manager->commit(null, '99999999_000000_00');
        } catch (CurrentMigrationException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }

    public function testFakeMigrationNotExistsExceptions()
    {
        try {
            self::$manager->commit(null, '12345678_000000_00');
        } catch (MigrationNotExistsException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }

    public function testFakeIncorrectMigrationName()
    {
        try {
            self::$manager->commit(null, null);
        } catch (IncorrectMigrationNameException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }


    public function testFakeMigrationExecutedException()
    {
        self::$manager->up(null, '99999999_000000_01');
        try {
            self::$manager->commit(null, '99999999_000000_00');
        } catch (MigrationExecutedException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }

    public function testFakeMigration()
    {
        $manager = self::$manager;

        $migrations = self::$manager->listMigrations();
        $this->assertTrue(is_array($migrations));
        $this->assertCount(5, $migrations);
        $this->assertArrayHasKey('name', $migrations[0]);
        $this->assertArrayHasKey('type', $migrations[0]);
        $this->assertEquals($manager::MIGRATION_TYPE_READY, $migrations[0]['type']);
        $this->assertEquals('99999999_000000_00', $migrations[0]['name']);

        self::$manager->commit(null, '99999999_000000_00');

        $migrations = self::$manager->listMigrations();
        $this->assertTrue(is_array($migrations));
        $this->assertCount(5, $migrations);
        $this->assertArrayHasKey('name', $migrations[0]);
        $this->assertArrayHasKey('type', $migrations[0]);
        $this->assertEquals($manager::MIGRATION_TYPE_LOADED, $migrations[0]['type']);
        $this->assertEquals('99999999_000000_00', $migrations[0]['name']);
    }


    public function testListMigrations()
    {
        $manager = self::$manager;
        // List migrations with modules scan
        $migrations = self::$manager->listMigrations(null, true);
        $this->assertTrue(is_array($migrations));
        $this->assertCount(10, $migrations);

        foreach ($migrations as $migration) {
            $this->assertArrayHasKey('name', $migration);
            $this->assertArrayHasKey('type', $migration);
            $this->assertEquals($manager::MIGRATION_TYPE_READY, $migration['type']);
        }

        $migrations = self::$manager->listMigrations();
        $this->assertTrue(is_array($migrations));
        $this->assertCount(5, $migrations);
        $this->assertArrayHasKey('name', $migrations[0]);
        $this->assertArrayHasKey('type', $migrations[0]);
        $this->assertEquals($manager::MIGRATION_TYPE_READY, $migrations[0]['type']);
        $this->assertEquals('99999999_000000_00', $migrations[0]['name']);

        $migrations = self::$manager->listMigrations(self::FIXTURE_MODULE);
        $this->assertTrue(is_array($migrations));
        $this->assertCount(5, $migrations);
        $this->assertArrayHasKey('name', $migrations[0]);
        $this->assertArrayHasKey('type', $migrations[0]);
        $this->assertEquals($manager::MIGRATION_TYPE_READY, $migrations[0]['type']);
        $this->assertEquals('99999999_100000_00', $migrations[0]['name']);

        self::$manager->up(self::FIXTURE_MODULE, '99999999_100000_00');

        $migrations = self::$manager->listMigrations(self::FIXTURE_MODULE);
        $this->assertTrue(is_array($migrations));
        $this->assertCount(5, $migrations);
        $this->assertArrayHasKey('name', $migrations[0]);
        $this->assertArrayHasKey('type', $migrations[0]);
        $this->assertEquals($manager::MIGRATION_TYPE_LOADED, $migrations[0]['type']);
        $this->assertEquals('99999999_100000_00', $migrations[0]['name']);
    }

    public function testListTypeConflictMigrations()
    {
        $manager = self::$manager;
        $migration = '99999999_000000_00';
        self::$manager->up(null, '99999999_000000_01');
        $migrationTable = self::$manager->getMigrationsSchemaTable();
        self::$db->query(
            'DELETE FROM `' . $migrationTable . '` WHERE `migration` = "' . $migration . '";',
            Adapter::QUERY_MODE_EXECUTE
        );
        //Test migration not exist in DB, but exist from file system and have old name
        $migrations = self::$manager->listMigrations();
        $this->assertTrue(is_array($migrations));
        $this->assertEquals($manager::MIGRATION_TYPE_CONFLICT, $migrations[0]['type']);
    }

    public function testListTypeNotExistMigrations()
    {
        $manager = self::$manager;
        $migration = '99999999_000000_00';
        self::$manager->up(null, $migration);
        $path = self::$manager->getMigrationsDirectoryPath();
        $migrationPath = $path . '/' . $migration . '.php';
        $renamedMigrationPath = $path . '/' . $migration . '.php.moved';

        rename($migrationPath, $renamedMigrationPath);
        //Test migration exist in DB, but removed from file system
        $migrations = self::$manager->listMigrations();
        $this->assertTrue(is_array($migrations));
        $this->assertEquals($manager::MIGRATION_TYPE_NOT_EXIST, $migrations[0]['type']);

        rename($renamedMigrationPath, $migrationPath);
    }

    public function testCreate()
    {
        $migrationPath = self::$manager->create();

        if (is_file($migrationPath)) {
            $fileName = basename($migrationPath, '.php');
            $migrationFile = file_get_contents($migrationPath);
            $this->assertContains('use', $migrationFile);
            $this->assertContains('class Migration_' . $fileName . ' extends AbstractMigration', $migrationFile);
            $this->assertContains('public function up()', $migrationFile);
            $this->assertContains('public function down()', $migrationFile);
            unlink($migrationPath);
        }
    }

    public function testCreateWithBody()
    {
        $migrationBody = array(
            'up' => array(
                'DROP TABLE IF EXISTS `album`',

                'CREATE TABLE `album` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `artist` varchar(100) NOT NULL,
                  `title` varchar(100) NOT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8'
            ),
            'down' => array(
                'DROP TABLE IF EXISTS album'
            )
        );
        $migrationPath = self::$manager->create(null, $migrationBody);

        if (is_file($migrationPath)) {
            $migrationFile = file_get_contents($migrationPath);
            $this->assertContains('CREATE TABLE `album`', $migrationFile);
            $this->assertContains('$this->query("DROP TABLE IF EXISTS `album`");', $migrationFile);
            unlink($migrationPath);
        }
    }

    /**
     * @dataProvider providerUpSuccess
     * @param $module
     * @param $migration
     * @param $tableFilter
     * @param $expected
     */
    public function testUpSuccess($module, $migration, $tableFilter, $expected)
    {
        self::$manager->up($module, $migration);

        $result = self::$db->query("SHOW TABLES LIKE '" . $tableFilter . "';", Adapter::QUERY_MODE_EXECUTE);

        $this->assertEquals($expected, $result->count());


        foreach ($result->toArray() as $data) {
            foreach ($data as $tableName) {
                self::$db->query("DROP TABLE `" . $tableName . "`", Adapter::QUERY_MODE_EXECUTE);
            }
        }
    }


    /**
     * Data provider for testUpSuccess
     * @return array
     */
    public function providerUpSuccess()
    {
        return array(
            array(null, null, 'items_0%', 5),
            array(null, '99999999_000000_02', 'items_0%', 3),
            array(self::FIXTURE_MODULE, null, 'items_s%', 5),
            array(self::FIXTURE_MODULE, '99999999_100000_01', 'items_s%', 2)
        );
    }


    /**
     * Test for method `generate`
     */

    public function testGenerateMigrationSuccess()
    {
        $tableName = 'test_table';

        //Test empty diff
        $result = self::$manager->generateMigration(null, '', $tableName);
        $this->assertFalse($result);

        $db = new Mysql(self::$db);
        $db->createTable($tableName);
        $db->createColumn($tableName, 'col1', AbstractMigration::TYPE_INT);
        $db->createColumn($tableName, 'col2', AbstractMigration::TYPE_VARCHAR, 50);

        //Test diff
        $diff = self::$manager->generateMigration(null, '', $tableName, true);

        $compareTo = array(
            'down' => array(Database::dropTable($tableName)),
            'up' => array(
                Database::dropTable($tableName),
                Database::createTable($tableName)
            )
        );

        $this->assertEquals($compareTo, $diff);

        //Test create
        $migrationPath = self::$manager->generateMigration(null, '', $tableName);

        $this->assertTrue(is_file($migrationPath));

        $migrationFile = file_get_contents($migrationPath);
        $this->assertContains('CREATE TABLE `' . $tableName . '`', $migrationFile);
        $this->assertContains('$this->query("DROP TABLE IF EXISTS `' . $tableName . '`");', $migrationFile);
        unlink($migrationPath);

        $db->dropTable($tableName);
    }


    /**
     * @dataProvider providerUpExceptions
     * @param $migration
     */
    public function testUpExceptions($migration)
    {
        self::$manager->up();

        try {
            self::$manager->up(null, $migration);
        } catch (\Exception $expected) {
            $this->assertTrue(true);
            return;
        }

        $this->fail('An expected Exception has not been raised.');
    }

    public function testGetMessages()
    {
        self::$manager->clearMessages();

        $messages = self::$manager->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertCount(0, $messages);

        $migration = '99999999_000000_00';
        self::$manager->up(null, $migration);

        $messages = self::$manager->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertCount(1, $messages);
        $this->assertEquals("Upgrade to revision `$migration`", $messages[0]);
    }

    /**
     * Data provider for testUpExceptions
     * @return array
     */
    public function providerUpExceptions()
    {
        return array(
            array('some_name'), // Invalid migration name
            array('99999999_000000_04'), // Current migration
            array('99999999_000000_03'), // Older then current migration
        );
    }

    public function testDownIncorrectMigrationNameException()
    {
        self::$manager->up(null, '99999999_000000_00');
        try {
            self::$manager->down(null, 'fake');
        } catch (IncorrectMigrationNameException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }

    public function testDownYoungMigrationException()
    {
        self::$manager->up(null, '99999999_000000_00');

        try {
            self::$manager->down(null, '99999999_000000_01');
        } catch (YoungMigrationException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }


    public function testDownNoMigrationsForExecutionException()
    {
        try {
            self::$manager->down(null, '99999999_000000_00');
        } catch (NoMigrationsForExecutionException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }

    /**
     * @dataProvider providerDownSuccess
     * @param $module
     * @param $migration
     * @param $tableFilter
     * @param $expected
     */
    public function testDownSuccess($module, $migration, $tableFilter, $expected)
    {
        self::$manager->up($module);

        $result = self::$db->query("SHOW TABLES LIKE '" . $tableFilter . "';", Adapter::QUERY_MODE_EXECUTE);

        $this->assertEquals(5, $result->count());

        self::$manager->down($module, $migration);

        $result = self::$db->query("SHOW TABLES LIKE '" . $tableFilter . "';", Adapter::QUERY_MODE_EXECUTE);

        $this->assertEquals($expected, $result->count());


        foreach ($result->toArray() as $data) {
            foreach ($data as $tableName) {
                self::$db->query("DROP TABLE `" . $tableName . "`", Adapter::QUERY_MODE_EXECUTE);
            }
        }
    }


    /**
     * Data provider for testDownSuccess
     * @return array
     */
    public function providerDownSuccess()
    {
        return array(
            array(null, null, 'items_0%', 0),
            array(null, '99999999_000000_02', 'items_0%', 2),
            array(self::FIXTURE_MODULE, null, 'items_s%', 0),
            array(self::FIXTURE_MODULE, '99999999_100000_01', 'items_s%', 1)
        );
    }


    /**
     * @dataProvider providerRollbackSuccess
     * @param $module
     * @param $step
     * @param $tableFilter
     * @param $expected
     */
    public function testRollbackSuccess($module, $step, $tableFilter, $expected)
    {
        self::$manager->up($module);

        $result = self::$db->query("SHOW TABLES LIKE '" . $tableFilter . "';", Adapter::QUERY_MODE_EXECUTE);

        $this->assertEquals(5, $result->count());

        self::$manager->rollback($module, $step);

        $result = self::$db->query("SHOW TABLES LIKE '" . $tableFilter . "';", Adapter::QUERY_MODE_EXECUTE);

        $this->assertEquals($expected, $result->count());

        foreach ($result->toArray() as $data) {
            foreach ($data as $tableName) {
                self::$db->query("DROP TABLE `" . $tableName . "`", Adapter::QUERY_MODE_EXECUTE);
            }
        }
    }


    /**
     * Data provider for testRollbackSuccess
     * @return array
     */
    public function providerRollbackSuccess()
    {
        return array(
            array(null, '1', 'items_0%', 4),
            array(null, '3', 'items_0%', 2),
            array(self::FIXTURE_MODULE, '1', 'items_s%', 4),
            array(self::FIXTURE_MODULE, '3', 'items_s%', 2)
        );
    }
}

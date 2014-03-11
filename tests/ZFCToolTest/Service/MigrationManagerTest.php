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
    protected $manager;

    /**
     * @var \Zend\Db\Adapter\Adapter
     */
    protected $db;

    protected function setUp()
    {
        try {
            /** @var $serviceManager \Zend\ServiceManager\ServiceManager */
            $serviceManager = Bootstrap::getServiceManager();
            $this->manager = new MigrationManager($serviceManager);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            exit;
        }

        $this->db = $serviceManager->get('Zend\Db\Adapter\Adapter');
    }


    protected function tearDown()
    {
        $result = $this->db->query("SHOW TABLES LIKE 'items_%';", Adapter::QUERY_MODE_EXECUTE);

        foreach ($result->toArray() as $data) {
            foreach ($data as $tableName) {
                $this->db->query("DROP TABLE `" . $tableName . "`", Adapter::QUERY_MODE_EXECUTE);
            }
        }

        $this->db->query(
            "DROP TABLE `" . $this->manager->getMigrationsSchemaTable() . "`",
            Adapter::QUERY_MODE_EXECUTE
        );
    }

    public function testSetModulesDirectoryPath()
    {
        $this->manager->setModulesDirectoryPath('/test/path');
        $this->assertEquals('/test/path', $this->manager->getModulesDirectoryPath());
    }


    public function testSetProjectDirectoryPath()
    {
        $this->manager->setProjectDirectoryPath('/test/path');
        $this->assertEquals('/test/path', $this->manager->getProjectDirectoryPath());
    }


    public function testGetProjectDirectoryPathExceptions()
    {
        $this->manager->setProjectDirectoryPath(null);
        try {
            $this->manager->getProjectDirectoryPath();
        } catch (ZFCToolException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }


    public function testGetModulesDirectoryPathByModuleExceptions()
    {
        $this->manager->setModulesDirectoryPath(null);
        try {
            $this->manager->getModulesDirectoryPath();
        } catch (ZFCToolException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }


    public function testGetMigrationsDirectoryNameExceptions()
    {
        $this->manager->setMigrationsDirectoryName(null);
        try {
            $this->manager->getMigrationsDirectoryName();
        } catch (ZFCToolException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }


    public function testGetMigrationsDirectoryPathExceptions()
    {
        try {
            $this->manager->getMigrationsDirectoryPath('unknownModule');
        } catch (ZFCToolException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }

    public function testFakeIncorrectMigrationNameException()
    {
        try {
            $this->manager->commit(null, 'fake');
        } catch (IncorrectMigrationNameException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }

    public function testFakeCurrentMigrationExceptions()
    {
        $this->manager->up(null, '99999999_000000_00');

        try {
            $this->manager->commit(null, '99999999_000000_00');
        } catch (CurrentMigrationException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }

    public function testFakeMigrationNotExistsExceptions()
    {
        try {
            $this->manager->commit(null, '12345678_000000_00');
        } catch (MigrationNotExistsException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }

    public function testFakeIncorrectMigrationName()
    {
        try {
            $this->manager->commit(null, null);
        } catch (IncorrectMigrationNameException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }


    public function testFakeMigrationExecutedException()
    {
        $this->manager->up(null, '99999999_000000_01');
        try {
            $this->manager->commit(null, '99999999_000000_00');
        } catch (MigrationExecutedException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }

    public function testFakeMigration()
    {
        $manager = $this->manager;

        $migrations = $this->manager->listMigrations();
        $this->assertTrue(is_array($migrations));
        $this->assertCount(5, $migrations);
        $this->assertArrayHasKey('name', $migrations[0]);
        $this->assertArrayHasKey('type', $migrations[0]);
        $this->assertEquals($manager::MIGRATION_TYPE_READY, $migrations[0]['type']);
        $this->assertEquals('99999999_000000_00', $migrations[0]['name']);

        $this->manager->commit(null, '99999999_000000_00');

        $migrations = $this->manager->listMigrations();
        $this->assertTrue(is_array($migrations));
        $this->assertCount(5, $migrations);
        $this->assertArrayHasKey('name', $migrations[0]);
        $this->assertArrayHasKey('type', $migrations[0]);
        $this->assertEquals($manager::MIGRATION_TYPE_LOADED, $migrations[0]['type']);
        $this->assertEquals('99999999_000000_00', $migrations[0]['name']);
    }


    public function testListMigrations()
    {
        $manager = $this->manager;
        $migrations = $this->manager->listMigrations();
        $this->assertTrue(is_array($migrations));
        $this->assertCount(5, $migrations);
        $this->assertArrayHasKey('name', $migrations[0]);
        $this->assertArrayHasKey('type', $migrations[0]);
        $this->assertEquals($manager::MIGRATION_TYPE_READY, $migrations[0]['type']);
        $this->assertEquals('99999999_000000_00', $migrations[0]['name']);

        $migrations = $this->manager->listMigrations(self::FIXTURE_MODULE);
        $this->assertTrue(is_array($migrations));
        $this->assertCount(5, $migrations);
        $this->assertArrayHasKey('name', $migrations[0]);
        $this->assertArrayHasKey('type', $migrations[0]);
        $this->assertEquals($manager::MIGRATION_TYPE_READY, $migrations[0]['type']);
        $this->assertEquals('99999999_100000_00', $migrations[0]['name']);

        $this->manager->up(self::FIXTURE_MODULE, '99999999_100000_00');

        $migrations = $this->manager->listMigrations(self::FIXTURE_MODULE);
        $this->assertTrue(is_array($migrations));
        $this->assertCount(5, $migrations);
        $this->assertArrayHasKey('name', $migrations[0]);
        $this->assertArrayHasKey('type', $migrations[0]);
        $this->assertEquals($manager::MIGRATION_TYPE_LOADED, $migrations[0]['type']);
        $this->assertEquals('99999999_100000_00', $migrations[0]['name']);
    }

    public function testListTypeConflictMigrations()
    {
        $manager = $this->manager;
        $migration = '99999999_000000_00';
        $this->manager->up(null, '99999999_000000_01');
        $migrationTable = $this->manager->getMigrationsSchemaTable();
        $this->db->query(
            'DELETE FROM `' . $migrationTable . '` WHERE `migration` = "' . $migration . '";',
            Adapter::QUERY_MODE_EXECUTE
        );
        //Test migration not exist in DB, but exist from file system and have old name
        $migrations = $this->manager->listMigrations();
        $this->assertTrue(is_array($migrations));
        $this->assertEquals($manager::MIGRATION_TYPE_CONFLICT, $migrations[0]['type']);
    }

    public function testListTypeNotExistMigrations()
    {
        $manager = $this->manager;
        $migration = '99999999_000000_00';
        $this->manager->up(null, $migration);
        $path = $this->manager->getMigrationsDirectoryPath();
        $migrationPath = $path . '/' . $migration . '.php';
        $renamedMigrationPath = $path . '/' . $migration . '.php.moved';

        rename($migrationPath, $renamedMigrationPath);
        //Test migration exist in DB, but removed from file system
        $migrations = $this->manager->listMigrations();
        $this->assertTrue(is_array($migrations));
        $this->assertEquals($manager::MIGRATION_TYPE_NOT_EXIST, $migrations[0]['type']);

        rename($renamedMigrationPath, $migrationPath);
    }

    public function testCreate()
    {
        $migrationPath = $this->manager->create();

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
        $migrationPath = $this->manager->create(null, $migrationBody);

        if (is_file($migrationPath)) {
            $migrationFile = file_get_contents($migrationPath);
            $this->assertContains('CREATE TABLE `album`', $migrationFile);
            $this->assertContains('$this->query(\'DROP TABLE IF EXISTS `album`\');', $migrationFile);
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
        $this->manager->up($module, $migration);

        $result = $this->db->query("SHOW TABLES LIKE '" . $tableFilter . "';", Adapter::QUERY_MODE_EXECUTE);

        $this->assertEquals($expected, $result->count());


        foreach ($result->toArray() as $data) {
            foreach ($data as $tableName) {
                $this->db->query("DROP TABLE `" . $tableName . "`", Adapter::QUERY_MODE_EXECUTE);
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
        $result = $this->manager->generateMigration(null, '', $tableName);
        $this->assertFalse($result);

        $db = new Mysql($this->db);
        $db->createTable($tableName);
        $db->createColumn($tableName, 'col1', AbstractMigration::TYPE_INT);
        $db->createColumn($tableName, 'col2', AbstractMigration::TYPE_VARCHAR, 50);

        //Test diff
        $diff = $this->manager->generateMigration(null, '', $tableName, true);

        $compareTo = array(
            'down' => array(Database::dropTable($tableName)),
            'up' => array(
                Database::dropTable($tableName),
                Database::createTable($tableName)
            )
        );

        $this->assertEquals($compareTo, $diff);

        //Test create
        $migrationPath = $this->manager->generateMigration(null, '', $tableName);

        $this->assertTrue(is_file($migrationPath));

        $migrationFile = file_get_contents($migrationPath);
        $this->assertContains('CREATE TABLE `' . $tableName . '`', $migrationFile);
        $this->assertContains('$this->query(\'DROP TABLE IF EXISTS `' . $tableName . '`\');', $migrationFile);
        unlink($migrationPath);

        $db->dropTable($tableName);
    }


    /**
     * @dataProvider providerUpExceptions
     * @param $migration
     */
    public function testUpExceptions($migration)
    {
        $this->manager->up();

        try {
            $this->manager->up(null, $migration);
        } catch (\Exception $expected) {
            $this->assertTrue(true);
            return;
        }

        $this->fail('An expected Exception has not been raised.');
    }

    public function testGetMessages()
    {
        $migration = '99999999_000000_00';
        $this->manager->up(null, $migration);

        $messages = $this->manager->getMessages();
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
        $this->manager->up(null, '99999999_000000_00');
        try {
            $this->manager->down(null, 'fake');
        } catch (IncorrectMigrationNameException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }

    public function testDownYoungMigrationException()
    {
        $this->manager->up(null, '99999999_000000_00');

        try {
            $this->manager->down(null, '99999999_000000_01');
        } catch (YoungMigrationException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }


    public function testDownNoMigrationsForExecutionException()
    {
        try {
            $this->manager->down(null, '99999999_000000_00');
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
        $this->manager->up($module);

        $result = $this->db->query("SHOW TABLES LIKE '" . $tableFilter . "';", Adapter::QUERY_MODE_EXECUTE);

        $this->assertEquals(5, $result->count());

        $this->manager->down($module, $migration);

        $result = $this->db->query("SHOW TABLES LIKE '" . $tableFilter . "';", Adapter::QUERY_MODE_EXECUTE);

        $this->assertEquals($expected, $result->count());


        foreach ($result->toArray() as $data) {
            foreach ($data as $tableName) {
                $this->db->query("DROP TABLE `" . $tableName . "`", Adapter::QUERY_MODE_EXECUTE);
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
        $this->manager->up($module);

        $result = $this->db->query("SHOW TABLES LIKE '" . $tableFilter . "';", Adapter::QUERY_MODE_EXECUTE);

        $this->assertEquals(5, $result->count());

        $this->manager->rollback($module, $step);

        $result = $this->db->query("SHOW TABLES LIKE '" . $tableFilter . "';", Adapter::QUERY_MODE_EXECUTE);

        $this->assertEquals($expected, $result->count());

        foreach ($result->toArray() as $data) {
            foreach ($data as $tableName) {
                $this->db->query("DROP TABLE `" . $tableName . "`", Adapter::QUERY_MODE_EXECUTE);
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

<?php
/**
 * User: naxel
 * Date: 11.03.14 15:46
 */

namespace ZFCToolTest\Controller;

use ZFCToolTest\Bootstrap;
use ZFCTool\Service\MigrationManager;
use Zend\Db\Adapter\Adapter;
use Zend\Test\PHPUnit\Controller\AbstractConsoleControllerTestCase;
use ZFCTool\Service\Migration\Adapter\Mysql;
use ZFCTool\Service\Database;
use ZFCTool\Service\Migration\AbstractMigration;

class MigrationControllerTest extends AbstractConsoleControllerTestCase
{
    const FIXTURE_MODULE = 'simplemodule';

    const TABLE_NAME = 'test_table';

    protected $verbose = false;

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

    public static function tearDownAfterClass()
    {
        self::$db->query(
            "DROP TABLE `" . self::$manager->getMigrationsSchemaTable() . "`",
            Adapter::QUERY_MODE_EXECUTE
        );
    }

    public function setUp()
    {
        $this->setApplicationConfig(Bootstrap::getConfig());
        parent::setUp();
        //Disabling output in console
        (!$this->verbose) && ob_start();
    }

    public function tearDown()
    {
        (!$this->verbose) && ob_end_clean();
        parent::tearDown();
    }

    public function testLsMigrations()
    {
        // dispatch url
        $this->dispatch('ls migrations');

        $this->assertResponseStatusCode(0);
        $this->assertActionName('list');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('listing-migration');
    }

    public function testListMigrations()
    {
        // dispatch url
        $this->dispatch('list migrations');

        $this->assertResponseStatusCode(0);
        $this->assertActionName('list');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('listing-migration');
    }

    public function testListingMigrations()
    {
        // dispatch url
        $this->dispatch('listing migrations');

        $this->assertResponseStatusCode(0);
        $this->assertActionName('list');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('listing-migration');
    }

    public function testLsMigrationsWithModule()
    {
        // dispatch url
        $this->dispatch('ls migrations' . ' --module=' . self::FIXTURE_MODULE);

        $this->assertResponseStatusCode(0);
        $this->assertActionName('list');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('listing-migration');
    }

    public function testCreateEmptyMigration()
    {
        // dispatch url
        $this->dispatch('gen migration -e');

        $this->assertResponseStatusCode(0);
        $this->assertActionName('generate');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('generate-migration');
        $response = ob_get_contents();

        preg_match("/[\S]*\.php/i", $response, $matches);
        $this->assertNotEmpty($matches);
        $migrationPath = $matches[0];
        $this->assertTrue(is_file($migrationPath));

        unlink($migrationPath);
    }

    public function testCreateEmptyMigrationForModule()
    {
        // dispatch url
        $this->dispatch('generate migration -e --module=' . self::FIXTURE_MODULE);

        $this->assertResponseStatusCode(0);
        $this->assertActionName('generate');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('generate-migration');
        $response = ob_get_contents();

        $this->assertContains('Only for module "' . self::FIXTURE_MODULE . '":', $response);

        preg_match("/[\S]*\.php/i", $response, $matches);
        $this->assertNotEmpty($matches);
        $migrationPath = $matches[0];
        $this->assertTrue(is_file($migrationPath));

        unlink($migrationPath);
    }


    public function testGenerateMigration()
    {
        $db = new Mysql(self::$db);

        $db->query(Database::dropTable(self::TABLE_NAME));
        $db->createTable(self::TABLE_NAME);
        $db->createColumn(self::TABLE_NAME, 'col1', AbstractMigration::TYPE_INT);
        $db->createColumn(self::TABLE_NAME, 'col2', AbstractMigration::TYPE_TEXT);

        // dispatch url
        $this->dispatch('gen migration --whitelist=' . self::TABLE_NAME);

        $this->assertResponseStatusCode(0);
        $this->assertActionName('generate');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('generate-migration');
        $response = ob_get_contents();

        preg_match("/[\S]*\.php/i", $response, $matches);
        $this->assertNotEmpty($matches);
        $migrationPath = $matches[0];
        $this->assertTrue(is_file($migrationPath));

        unlink($migrationPath);

        $db->query(Database::dropTable(self::TABLE_NAME));
    }

    public function testGenerateAndCommitMigration()
    {
        $db = new Mysql(self::$db);

        $db->query(Database::dropTable(self::TABLE_NAME));
        $db->createTable(self::TABLE_NAME);
        $db->createColumn(self::TABLE_NAME, 'col1', AbstractMigration::TYPE_INT);
        $db->createColumn(self::TABLE_NAME, 'col2', AbstractMigration::TYPE_TEXT);

        // dispatch url
        $this->dispatch('gen migration -c --whitelist=' . self::TABLE_NAME);

        $this->assertResponseStatusCode(0);
        $this->assertActionName('generate');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('generate-migration');
        $response = ob_get_contents();

        preg_match("/[\S]*\.php/i", $response, $matches);
        $this->assertTrue(isset($matches[0]));
        $migrationPath = $matches[0];
        $this->assertTrue(is_file($migrationPath));

        preg_match("/\d\d\d\d\d\d\d\d_\d\d\d\d\d\d_\d\d/i", $migrationPath, $matches);
        $this->assertNotEmpty($matches);
        $migration = $matches[0];

        $lastMigration = self::$manager->getLastMigration();
        $this->assertArrayHasKey('migration', $lastMigration);
        $this->assertNotEquals(0, $lastMigration['migration']);
        $this->assertEquals($lastMigration['migration'], $migration);

        self::$manager->down(null);

        unlink($migrationPath);
    }


    public function testCommitMigration()
    {
        $migration = '99999999_100000_00';
        // dispatch url
        $this->dispatch('ci migration ' . $migration . ' --module=' . self::FIXTURE_MODULE);
        $this->assertActionName('commit');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('commit-migration');
        $response = ob_get_contents();

        preg_match("/\d\d\d\d\d\d\d\d_\d\d\d\d\d\d_\d\d/i", $response, $matches);
        $this->assertNotEmpty($matches);

        $this->assertEquals($migration, $matches[0]);
        $lastMigration = self::$manager->getLastMigration(self::FIXTURE_MODULE);
        $this->assertArrayHasKey('migration', $lastMigration);
        $this->assertNotEquals(0, $lastMigration['migration']);
        $this->assertEquals($lastMigration['migration'], $migration);

        self::$db->query(
            "DROP TABLE `" . self::$manager->getMigrationsSchemaTable() . "`",
            Adapter::QUERY_MODE_EXECUTE
        );
    }

    public function testShowMigration()
    {
        // dispatch url
        $this->dispatch('show migration');

        $this->assertResponseStatusCode(0);
        $this->assertActionName('show');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('show-migration');
    }

    public function testUpDownDb()
    {
        // dispatch url
        $this->dispatch('up db');

        $this->assertResponseStatusCode(0);
        $this->assertActionName('up');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('up-db');

        // dispatch url
        $this->dispatch('down db');

        $this->assertResponseStatusCode(0);
        $this->assertActionName('down');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('down-db');
    }

    public function testUpDownModuleDb()
    {
        // dispatch url
        $this->dispatch('up db --module=' . self::FIXTURE_MODULE);

        $this->assertResponseStatusCode(0);
        $this->assertActionName('up');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('up-db');

        // dispatch url
        $this->dispatch('down db --module=' . self::FIXTURE_MODULE);

        $this->assertResponseStatusCode(0);
        $this->assertActionName('down');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('down-db');
    }

    public function testRollbackModuleDb()
    {
        // dispatch url
        $this->dispatch('up db --module=' . self::FIXTURE_MODULE);

        $this->assertResponseStatusCode(0);
        $this->assertActionName('up');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('up-db');

        // dispatch url
        $this->dispatch('back db --step=5 --module=' . self::FIXTURE_MODULE);

        $this->assertResponseStatusCode(0);
        $this->assertActionName('rollback');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('rollback-db');
    }

    public function testRollbackModuleDbEmptyStep()
    {
        // dispatch url
        $this->dispatch('rollback db');

        $this->assertResponseStatusCode(0);
        $this->assertActionName('rollback');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('rollback-db');

        $response = ob_get_contents();
        $this->assertContains('No migrations to rollback.', $response);
    }


    public function testDiffModuleDb()
    {
        $db = new Mysql(self::$db);

        $db->query(Database::dropTable(self::TABLE_NAME));
        $db->createTable(self::TABLE_NAME);
        $db->createColumn(self::TABLE_NAME, 'col1', AbstractMigration::TYPE_INT);
        $db->createColumn(self::TABLE_NAME, 'col2', AbstractMigration::TYPE_TEXT);

        // dispatch url
        $this->dispatch('diff db --module=' . self::FIXTURE_MODULE . ' --whitelist=' . self::TABLE_NAME);

        $this->assertResponseStatusCode(0);
        $this->assertActionName('diff');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('diff-db');

        $response = ob_get_contents();
        $this->assertContains('Queries (2) :', $response);
        $db->query(Database::dropTable(self::TABLE_NAME));
    }

    public function testEmptyDiffModuleDb()
    {
        // dispatch url
        $this->dispatch('diff db --module=' . self::FIXTURE_MODULE . ' --whitelist=' . self::TABLE_NAME);

        $this->assertResponseStatusCode(0);
        $this->assertActionName('diff');
        $this->assertControllerName('ZFCTool\Controller\Migration');
        $this->assertControllerClass('MigrationController');
        $this->assertMatchedRouteName('diff-db');

        $response = ob_get_contents();
        $this->assertContains('Your database has no changes from last revision!', $response);
    }
}

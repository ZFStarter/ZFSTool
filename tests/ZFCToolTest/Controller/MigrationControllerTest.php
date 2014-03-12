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

class MigrationControllerTest extends AbstractConsoleControllerTestCase
{
    const FIXTURE_MODULE = 'simplemodule';

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

//    public function testGenMigration()
//    {
//        // dispatch url
//        $this->dispatch('gen migration');
//
//        $this->assertResponseStatusCode(0);
//        $this->assertActionName('generate');
//        $this->assertControllerName('ZFCTool\Controller\Migration');
//        $this->assertControllerClass('MigrationController');
//        $this->assertMatchedRouteName('generate-migration');
//    }

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
}

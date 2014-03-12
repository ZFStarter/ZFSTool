<?php
/**
 * User: naxel
 * Date: 12.03.14 10:28
 */

namespace ZFCToolTest\Controller;

use Zend\Test\PHPUnit\Controller\AbstractConsoleControllerTestCase;
use Zend\Db\Adapter\Adapter;
use ZFCToolTest\Bootstrap;
use ZFCTool\Service\DumpManager;
use ZFCTool\Service\Migration\Adapter\Mysql;
use ZFCTool\Service\Database;
use ZFCTool\Service\Migration\AbstractMigration;

class DumpControllerTest extends AbstractConsoleControllerTestCase
{
    const FIXTURE_MODULE = 'simplemodule';

    const DUMP_FILE_NAME = 'testdump';

    const TABLE_NAME = 'test_table';

    protected $verbose = false;

    /**
     * @var DumpManager
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
        self::$manager = new DumpManager($serviceManager);
        self::$db = $serviceManager->get('Zend\Db\Adapter\Adapter');
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

    public function testCreateDumpSuccess()
    {
        $db = new Mysql(self::$db);

        $db->query(Database::dropTable(self::TABLE_NAME));
        $db->createTable(self::TABLE_NAME);
        $db->createColumn(self::TABLE_NAME, 'col1', AbstractMigration::TYPE_INT);
        $db->createColumn(self::TABLE_NAME, 'col2', AbstractMigration::TYPE_TEXT);

        // dispatch url
        $this->dispatch('create dump --name=' . self::DUMP_FILE_NAME . ' --whitelist=' . self::TABLE_NAME);

        $this->assertResponseStatusCode(0);
        $this->assertActionName('create');
        $this->assertControllerName('ZFCTool\Controller\Dump');
        $this->assertControllerClass('DumpController');
        $this->assertMatchedRouteName('create-dump');

        $path = self::$manager->getDumpsDirectoryPath();
        $this->assertTrue(is_file($path . DIRECTORY_SEPARATOR . self::DUMP_FILE_NAME));
    }

    /**
     * @depends testCreateDumpSuccess
     */
    public function testImportDumpSuccess()
    {
        $dumpFullPath = self::$manager->getDumpsDirectoryPath()
            . DIRECTORY_SEPARATOR . self::DUMP_FILE_NAME;
        if (is_file($dumpFullPath)) {

            // dispatch url
            $this->dispatch('import dump ' . self::DUMP_FILE_NAME);

            $this->assertResponseStatusCode(0);
            $this->assertActionName('import');
            $this->assertControllerName('ZFCTool\Controller\Dump');
            $this->assertControllerClass('DumpController');
            $this->assertMatchedRouteName('import-dump');

            $result = self::$db->query("SHOW TABLES LIKE '" . self::TABLE_NAME . "';", Adapter::QUERY_MODE_EXECUTE);

            $this->assertEquals(1, $result->count());
            unlink($dumpFullPath);
            $db = new Mysql(self::$db);
            $db->dropTable(self::TABLE_NAME);
        } else {
            $this->fail('Dump file not exist!');
        }
    }
}

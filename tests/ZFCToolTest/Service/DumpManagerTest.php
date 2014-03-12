<?php
/**
 * User: naxel
 * Date: 06.03.14 12:48
 */

namespace ZFCToolTest\Service;

use ZFCToolTest\Bootstrap;
use ZFCTool\Service\DumpManager;
use PHPUnit_Framework_TestCase;

use ZFCTool\Service\Migration\Adapter\Mysql;
use ZFCTool\Service\Database;
use ZFCTool\Service\Migration\AbstractMigration;
use Zend\Db\Adapter\Adapter;
use ZFCTool\Exception\ZFCToolException;
use ZFCTool\Exception\EmptyDumpException;
use ZFCTool\Exception\DumpNotFound;

class DumpManagerTest extends \PHPUnit_Framework_TestCase
{
    const FIXTURE_MODULE = 'simplemodule';

    const DUMP_FILE_NAME = 'test.sql';

    const TABLE_NAME = 'test_table';

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
            //Restore
            self::$manager->setProjectDirectoryPath($projectDirectoryPath);
            $this->assertTrue(true);
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
            //Restore
            self::$manager->setModulesDirectoryPath($modulesDirectoryPath);
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }


    public function testGetDumpsDirectoryNameExceptions()
    {
        //Store
        $dumpsDirectoryName = self::$manager->getDumpsDirectoryName();
        self::$manager->setDumpsDirectoryName(null);
        try {
            self::$manager->getDumpsDirectoryName();
        } catch (ZFCToolException $expected) {
            $this->assertTrue(true);
            //Restore
            self::$manager->setDumpsDirectoryName($dumpsDirectoryName);
            return;
        }

        $this->fail('An expected Exception has not been raised.');
    }


    public function testGetDumpsDirectoryPathExceptions()
    {
        try {
            self::$manager->getDumpsDirectoryPath('unknownModule');
        } catch (ZFCToolException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }

    public function testCreateSuccess()
    {
        $db = new Mysql(self::$db);

        $db->query(Database::dropTable(self::TABLE_NAME));
        $db->createTable(self::TABLE_NAME);
        $db->createColumn(self::TABLE_NAME, 'col1', AbstractMigration::TYPE_INT);
        $db->createColumn(self::TABLE_NAME, 'col2', AbstractMigration::TYPE_TEXT);

        $db->query(Database::dropTable('test_black_table1'));
        $db->createTable('test_black_table1');

        $db->query(Database::dropTable('test_black_table2'));
        $db->createTable('test_black_table2');

        $testData = array(
            'id' => '1',
            'col1' => '11',
            'col2' => '<p>ZFCTool - Zend Framework 2 command line Tool</p>'
        );

        $db->insert(self::TABLE_NAME, $testData);

        $dumpName = self::$manager->create(
            self::FIXTURE_MODULE, self::DUMP_FILE_NAME, self::TABLE_NAME, 'test_black_table1,test_black_table2'
        );

        $compareTo = Database::dropTable(self::TABLE_NAME) . ';' . PHP_EOL
            . Database::createTable(self::TABLE_NAME) . ';' . PHP_EOL
            . Database::insert(self::TABLE_NAME, $testData) . ';' . PHP_EOL;

        $this->assertEquals(self::DUMP_FILE_NAME, $dumpName);

        $dumpFullPath = self::$manager->getDumpsDirectoryPath(self::FIXTURE_MODULE)
            . DIRECTORY_SEPARATOR . $dumpName;

        if (file_exists($dumpFullPath)) {
            $dump = file_get_contents($dumpFullPath);
            $this->assertEquals($compareTo, $dump);
        } else {
            $this->fail('Dump file not exist!');
        }

        //Test generate name and creating file
        $dumpName = self::$manager->create(null, null, self::TABLE_NAME, 'test_black_table1,test_black_table2');
        $dumpFullPath = self::$manager->getDumpsDirectoryPath() . DIRECTORY_SEPARATOR . $dumpName;

        if (file_exists($dumpFullPath)) {
            $dump = file_get_contents($dumpFullPath);
            $this->assertEquals($compareTo, $dump);
            unlink($dumpFullPath);
        } else {
            $this->fail('Dump file not exist!');
        }

        $db->dropTable(self::TABLE_NAME);
        $db->dropTable('test_black_table1');
        $db->dropTable('test_black_table2');
    }


    public function testCreateEmptyDumpException()
    {
        try {
            self::$manager->create(null, null, 'fake');
        } catch (EmptyDumpException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }


    /**
     * @depends testCreateSuccess
     */
    public function testImportSuccess()
    {
        $dumpFullPath = self::$manager->getDumpsDirectoryPath(self::FIXTURE_MODULE)
            . DIRECTORY_SEPARATOR . self::DUMP_FILE_NAME;
        if (is_file($dumpFullPath)) {

            self::$manager->import(self::DUMP_FILE_NAME, self::FIXTURE_MODULE);

            $result = self::$db->query("SHOW TABLES LIKE '" . self::TABLE_NAME . "';", Adapter::QUERY_MODE_EXECUTE);

            $this->assertEquals(1, $result->count());
            unlink($dumpFullPath);
            $db = new Mysql(self::$db);
            $db->dropTable(self::TABLE_NAME);
        } else {
            $this->fail('Dump file not exist!');
        }
    }


    public function testImportFail()
    {
        try {
            self::$manager->import('', null);
        } catch (DumpNotFound $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail("No exception!");
    }
}

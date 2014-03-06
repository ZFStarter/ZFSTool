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
    protected $manager;

    /**
     * @var \Zend\Db\Adapter\Adapter
     */
    protected $db;


    protected function setUp()
    {
        /** @var $serviceManager \Zend\ServiceManager\ServiceManager */
        $serviceManager = Bootstrap::getServiceManager();
        $this->manager = new DumpManager($serviceManager);
        $this->db = $serviceManager->get('Zend\Db\Adapter\Adapter');
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


    public function testGetDumpsDirectoryNameExceptions()
    {
        $this->manager->setDumpsDirectoryName(null);
        try {
            $this->manager->getDumpsDirectoryName();
        } catch (ZFCToolException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }


    public function testGetDumpsDirectoryPathExceptions()
    {
        try {
            $this->manager->getDumpsDirectoryPath('unknownModule');
        } catch (ZFCToolException $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('An expected Exception has not been raised.');
    }

    public function testCreateSuccess()
    {
        $db = new Mysql($this->db);

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

        $dumpName = $this->manager->create(
            self::FIXTURE_MODULE, self::DUMP_FILE_NAME, self::TABLE_NAME, 'test_black_table1,test_black_table2'
        );

        $compareTo = Database::dropTable(self::TABLE_NAME) . ';' . PHP_EOL
            . Database::createTable(self::TABLE_NAME) . ';' . PHP_EOL
            . Database::insert(self::TABLE_NAME, $testData) . ';' . PHP_EOL;

        $this->assertEquals(self::DUMP_FILE_NAME, $dumpName);

        $dumpFullPath = $this->manager->getDumpsDirectoryPath(self::FIXTURE_MODULE)
            . DIRECTORY_SEPARATOR . $dumpName;

        if (file_exists($dumpFullPath)) {
            $dump = file_get_contents($dumpFullPath);
            $this->assertEquals($compareTo, $dump);
        } else {
            $this->fail('Dump file not exist!');
        }

        //Test generate name and creating file
        $dumpName = $this->manager->create(null, null, self::TABLE_NAME, 'test_black_table1,test_black_table2');
        $dumpFullPath = $this->manager->getDumpsDirectoryPath() . DIRECTORY_SEPARATOR . $dumpName;

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
            $this->manager->create(null, null, 'fake');
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
        $dumpFullPath = $this->manager->getDumpsDirectoryPath(self::FIXTURE_MODULE)
            . DIRECTORY_SEPARATOR . self::DUMP_FILE_NAME;
        if (is_file($dumpFullPath)) {

            $this->manager->import(self::DUMP_FILE_NAME, self::FIXTURE_MODULE);

            $result = $this->db->query("SHOW TABLES LIKE '" . self::TABLE_NAME . "';", Adapter::QUERY_MODE_EXECUTE);

            $this->assertEquals(1, $result->count());
            unlink($dumpFullPath);
            $db = new Mysql($this->db);
            $db->dropTable(self::TABLE_NAME);
        } else {
            $this->fail('Dump file not exist!');
        }
    }


    public function testImportFail()
    {
        try {
            $this->manager->import('', null);
        } catch (DumpNotFound $expected) {
            $this->assertTrue(true);
            return;
        }
        $this->fail("No exception!");
    }
}

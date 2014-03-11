<?php
/**
 * User: naxel
 * Date: 11.03.14 15:46
 */

namespace ZFCToolTest\Controller;

use ZFCToolTest\Bootstrap;
use ZFCTool\Service\MigrationManager;
use Zend\Test\PHPUnit\Controller\AbstractConsoleControllerTestCase;

class MigrationControllerTest extends AbstractConsoleControllerTestCase
{
    const FIXTURE_MODULE = 'simplemodule';

    public function setUp()
    {
        $this->setApplicationConfig(Bootstrap::getConfig());
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

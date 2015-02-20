<?php

namespace ZFCTool;

use Zend\Console\Adapter\AdapterInterface as ConsoleAdapterInterface;
use Zend\EventManager\EventInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module implements ConsoleUsageProviderInterface, AutoloaderProviderInterface, ConfigProviderInterface
{
    const NAME = 'ZFCTool - Zend Framework 2 command line Tool';

    /**
     * @var ServiceLocatorInterface
     */
    protected $sm;

    public function onBootstrap(EventInterface $e)
    {
        $this->sm = $e->getApplication()->getServiceManager();
    }

    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    public function getConsoleBanner(ConsoleAdapterInterface $console)
    {
        return self::NAME;
    }

    public function getConsoleUsage(ConsoleAdapterInterface $console)
    {
        return array(
            'Migrations:',

            'ls migrations [--module] [-s]' => '- List of exist migrations',
            array('--module', '(Optional) Module name'),
            array('-s', '(Optional) Scan modules folders for migrations'),

            'up db <to> [--module] [-s]' => '- Update DB to selected migration',
            array('--module', '(Optional) Module name'),
            array('to', '(Optional) Migration name'),
            array('-s', '(Optional) Scan modules folders for migrations'),

            'down db <to> [--module] [-s]' => '- Downgrade selected migration from DB',
            array('--module', '(Optional) Module name'),
            array('to', '(Optional) Migration name'),
            array('-s', '(Optional) Scan modules folders for migrations'),

            'show migration [--module]' => '- Show current migration',
            array('--module', '(Optional) Module name'),

            'gen migration [--module] [--whitelist] [--blacklist] [-c] [-e]' => '- Generate new migration',
            array('--module', '(Optional) Module name'),
            array('--whitelist', '(Optional) White list of tables'),
            array('--blacklist', '(Optional) Black list of tables'),
            array('-c', '(Optional) Create and commit migration'),
            array('-e', '(Optional) Create empty migration'),

            'ci migration <to> [--module] [-s]' => '- Commit selected migration to DB',
            array('--module', '(Optional) Module name'),
            array('to', 'To migration'),
            array('-s', '(Optional) Scan modules folders for migrations'),

            'back db [--module] [--step] [-s]' => '- Rollback DB',
            array('--module', '(Optional) Module name'),
            array('--step', 'Count of rollback migrations'),
            array('-s', '(Optional) Scan modules folders for migrations'),

            'diff db [--module] [--whitelist] [--blacklist]' => '- Show generated queries without creating migration',
            array('--module', '(Optional) Module name'),
            array('--whitelist', '(Optional) White list of tables'),
            array('--blacklist', '(Optional) Black list of tables'),


            'Dump:',

            'ls dump [--module] [-s]' => '- List of exist dump',
            array('--module', '(Optional) Module name'),
            array('-s', '(Optional) Scan modules folders for dumps'),

            'create dump [--module] [--name] [--whitelist] [--blacklist]' => '- Creating dump',
            array('--module', '(Optional) Module name'),
            array('--name', '(Optional) Dump file name'),
            array('--whitelist', '(Optional) White list of tables'),
            array('--blacklist', '(Optional) Black list of tables'),
            'import dump <name> [--module]' => '- import already created dump',
            array('--module', 'Module name'),
            array('name', 'Dump file name'),
        );
    }
}

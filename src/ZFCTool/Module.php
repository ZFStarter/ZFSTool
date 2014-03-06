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
            'Migration:',
            'generate migration [--module] [--whitelist] [--blacklist]' => '- Generate new migration',
            array('--module', '(Optional) Module name'),
            array('--whitelist', '(Optional) White list of tables'),
            array('--blacklist', '(Optional) Black list of tables'),
            'diff migration [--module] [--whitelist] [--blacklist]' => '- Show generated queries without creating migration',
            array('--module', '(Optional) Module name'),
            array('--whitelist', '(Optional) White list of tables'),
            array('--blacklist', '(Optional) Black list of tables'),
            'listing migration [--module]' => '- List of exist migrations',
            array('--module', '(Optional) Module name'),
            'current migration [--module]' => '- Show current migration',
            array('--module', '(Optional) Module name'),
            'create migration [--module]' => '- Create template for migration',
            array('--module', '(Optional) Module name'),
            'up migration <to> [--module]' => '- Update DB to selected migration',
            array('--module', '(Optional) Module name'),
            array('to', 'To migration'),
            'down migration <to> [--module]' => '- Downgrade DB to selected migration',
            array('--module', '(Optional) Module name'),
            array('to', 'To migration'),
            'rollback migration [--module] [--step]' => '- Rollback migrations',
            array('--module', '(Optional) Module name'),
            array('--step', 'Count of rollback migrations'),
            'fake migration <to> [--module]' => '- Fake update DB to selected migration',
            array('--module', '(Optional) Module name'),
            array('to', 'To migration'),

            'Dump:',
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

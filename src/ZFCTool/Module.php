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
            'check generate migration [--module] [--whitelist] [--blacklist]' => '- Generate new migration',
            array('--module', 'Module name'),
            'diff migration [--module] [--whitelist] [--blacklist]' => '- Show generated queries without creating migration',
            array('--module', 'Module name'),
            'listing migration [--module]' => '- List of exist migrations',
            array('--module', '(Optional) Module name'),
            'current migration [--module]' => '- Show current migration',
            array('--module', 'Module name'),
            'create migration [--module]' => '- Create template for migration',
            array('--module', 'Module name'),
            'up migration [--module] [--to]' => '- Update DB to selected migration',
            array('--module', 'Module name'),
            'down migration [--module] [--to]' => '- Downgrade DB to selected migration',
            array('--module', 'Module name'),
            'rollback migration [--module] [--step]' => '- Rollback migrations',
            array('--module', 'Module name'),
            'fake migration [--module] [--to]' => '- Fake update DB to selected migration',
            array('--module', 'Module name'),

            'Dump:',
            'create dump [--module] [--name] [--whitelist] [--blacklist]' => '- Creating dump',
            array('--module', 'Module name'),
            array('--name', 'Dump file name'),
            array('--whitelist', 'White list of tables'),
            array('--blacklist', 'Black list of tables'),
            'import dump [--name] [--module]' => '- import already created dump',
            array('--module', 'Module name'),
            array('--name', 'Dump file name'),
        );
    }
}

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
            'check migration [module]' => '---',
            array('module', 'Module name'),
            'check generate migration [module] [whitelist] [blacklist] [label] [description]' => '---',
            array('module', 'Module name'),
            'diff migration [module] [whitelist] [blacklist]' => '---',
            array('module', 'Module name'),
            'listing migration [module]' => '---',
            array('module', '(Optional) Module name'),
            'current migration [module]' => '---',
            array('module', 'Module name'),
            'create migration [module] [label] [description]' => '---',
            array('module', 'Module name'),
            'up migration [module] [to]' => '---',
            array('module', 'Module name'),
            'down migration [module] [to]' => '---',
            array('module', 'Module name'),
            'rollback migration [module] [step]' => '---',
            array('module', 'Module name'),
            'fake migration [module] [to]' => '---',
            array('module', 'Module name'),

            'Dump:',
            'create dump [module] [name] [whitelist] [blacklist]' => '---',
            array('module', 'Module name'),
            'import dump [name] [module]' => '---',
            array('module', 'Module name')
        );
    }
}

<?php

namespace ZFCTool\Service;

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ModuleFactory
 * @package ZFCTool\Service
 */
class ModuleFactory implements AbstractFactoryInterface
{
    protected $instances = array();

    protected $provides = array(
        'MigrationManager' => 'ZFCTool\Service\MigrationManager',
        'DumpManager' => 'ZFCTool\Service\DumpManager',
    );

    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $name
     * @param                         $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return isset($this->provides[$requestedName]);
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $name
     * @param                         $requestedName
     * @return mixed
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (!isset($this->instances[$requestedName])) {
            $this->instances[$requestedName] = new $this->provides[$requestedName]($serviceLocator);
        }
        return $this->instances[$requestedName];
    }
}

<?php
return array(

    'ZFCTool' => array(
        'migrations' => array(
            // Migrations schema table name
            'migrationsSchemaTable' => 'migrations',
            // Path to project directory
            'projectDirectoryPath' => getcwd(),
            // Path to modules directory
            'modulesDirectoryPath' => 'module',
            // Migrations directory name
            'migrationsDirectoryName' => 'migrations'
        )
    ),
    // -----=-----=-----=-----=-----=-----=-----=-----=-----=-----=-----=-----=-----=-----=-----=-----=

    'controllers' => array(
        'invokables' => array(
            'ZFCTool\Controller\Migration' => 'ZFCTool\Controller\MigrationController',
        ),
    ),

    'console' => array(
        'router' => array(
            'routes' => array(
                'listing-migration' => array(
                    'options' => array(
                        'route' => 'listing migration [<module>]',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Migration',
                            'action' => 'list'
                        )
                    )
                ),
                'create-migration' => array(
                    'options' => array(
                        'route' => 'create migration [<module>]',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Migration',
                            'action' => 'create'
                        )
                    )
                ),

                'generate-migration' => array(
                    'options' => array(
                        'route' => 'generate migration [<module>]',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Migration',
                            'action' => 'generate'
                        )
                    )
                ),
                'fake-migration' => array(
                    'options' => array(
                        'route' => 'fake migration [<module>] [<to>]',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Migration',
                            'action' => 'fake'
                        )
                    )
                ),
                'down-migration' => array(
                    'options' => array(
                        'route' => 'down migration [<to>] [<module>]',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Migration',
                            'action' => 'down'
                        )
                    )
                ),
                'up-migration' => array(
                    'options' => array(
                        'route' => 'up migration [<to>] [<module>]',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Migration',
                            'action' => 'up'
                        )
                    )
                ),
                'current-migration' => array(
                    'options' => array(
                        'route' => 'current migration [<module>]',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Migration',
                            'action' => 'current'
                        )
                    )
                ),
                'rollback-migration' => array(
                    'options' => array(
                        'route' => 'rollback migration [<module>] [<step>]',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Migration',
                            'action' => 'rollback'
                        )
                    )
                ),
                'diff-migration' => array(
                    'options' => array(
                        'route' => 'diff migration [<blacklist>]',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Migration',
                            'action' => 'diff'
                        )
                    )
                ),
            )
        )
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'ZFCTool\Service\ModuleFactory',
        ),
    )
);

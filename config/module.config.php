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
            'migrationsDirectoryName' => 'migrations',
        ),
        'dumps' => array(
            // Dumps directory name
            'dumpsDirectoryName' => 'dumps'
        )
    ),
    // -----=-----=-----=-----=-----=-----=-----=-----=-----=-----=-----=-----=-----=-----=-----=-----=

    'controllers' => array(
        'invokables' => array(
            'ZFCTool\Controller\Migration' => 'ZFCTool\Controller\MigrationController',
            'ZFCTool\Controller\Dump' => 'ZFCTool\Controller\DumpController',
        ),
    ),

    'console' => array(
        'router' => array(
            'routes' => array(
                'listing-migration' => array(
                    'options' => array(
                        'route' => '(listing|list|ls) migrations [--module=] [--scanfolders|-s]:scanfolders',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Migration',
                            'action' => 'list'
                        )
                    )
                ),
                'generate-migration' => array(
                    'options' => array(
                        'route' => '(generate|gen) migration [--module=] [--commit|-c]:commit [--empty|-e]:empty [--whitelist=] [--blacklist=]',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Migration',
                            'action' => 'generate'
                        )
                    )
                ),
                'commit-migration' => array(
                    'options' => array(
                        'route' => '(commit|ci) migration <to> [--module=] [--scanfolders|-s]:scanfolders',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Migration',
                            'action' => 'commit'
                        )
                    )
                ),
                'down-db' => array(
                    'options' => array(
                        'route' => 'down db [<to>] [--module=] [--scanfolders|-s]:scanfolders',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Migration',
                            'action' => 'down'
                        )
                    )
                ),
                'up-db' => array(
                    'options' => array(
                        'route' => 'up db [<to>] [--module=] [--scanfolders|-s]:scanfolders',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Migration',
                            'action' => 'up'
                        )
                    )
                ),
                'show-migration' => array(
                    'options' => array(
                        'route' => 'show migration [--module=]',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Migration',
                            'action' => 'show'
                        )
                    )
                ),
                'rollback-db' => array(
                    'options' => array(
                        'route' => '(rollback|back) db [--module=] [--step=] [--scanfolders|-s]:scanfolders',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Migration',
                            'action' => 'rollback'
                        )
                    )
                ),
                'diff-db' => array(
                    'options' => array(
                        'route' => 'diff db [--module=] [--whitelist=] [--blacklist=]',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Migration',
                            'action' => 'diff'
                        )
                    )
                ),
                'create-dump' => array(
                    'options' => array(
                        'route' => 'create dump [--module=] [--name=] [--whitelist=] [--blacklist=]',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Dump',
                            'action' => 'create'
                        )
                    )
                ),
                'import-dump' => array(
                    'options' => array(
                        'route' => 'import dump <name> [--module=]',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Dump',
                            'action' => 'import'
                        )
                    )
                ),
                'listing-dump' => array(
                    'options' => array(
                        'route' => '(listing|list|ls) dump [--module=] [--scanfolders|-s]:scanfolders',
                        'defaults' => array(
                            'controller' => 'ZFCTool\Controller\Dump',
                            'action' => 'list'
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
        'factories' => array(
            'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\AdapterServiceFactory',
        ),
    )
);

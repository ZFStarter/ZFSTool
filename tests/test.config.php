<?php
return array(
    'ZFCTool' => array(
        'migrations' => array(
            'migrationsSchemaTable' => '~migrations',
            'projectDirectoryPath' => realpath(dirname(__FILE__) . '/_env'),
            'modulesDirectoryPath' => realpath(dirname(__FILE__) . '/_env/module'),
            'migrationsDirectoryName' => 'migrations',
        ),
        'dumps' => array(
            'dumpsDirectoryName' => 'dumps'
        )
    )
);

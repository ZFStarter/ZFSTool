<?php
if (getenv('TRAVIS')) {
    return array(
        'db' => array(
            //For travis
            'username' => 'travis',
            'password' => '',
            'dsn' => 'mysql:dbname=zfc2_tool_test;host=0.0.0.0',
        ),
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
} else {
    return array();
}

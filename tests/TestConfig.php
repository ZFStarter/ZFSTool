<?php
return array(
	'modules' => array(
        'ZFCTool'
	),
	'module_listener_options' => array(
		'config_glob_paths'    => array(
			'../config/autoload/{,*.}{global,local}.php',
			'test.config.php',
		),
		'module_paths' => array(
			'module',
			'vendor',
		),
	)
);
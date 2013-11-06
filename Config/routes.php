<?php
	Router::connect('/layout/css-min/:file', array(
		'controller' => 'minified_assets', 
		'action' => 'css',
		'plugin' => 'layout'
	), array('pass' => array('file')));

	Router::connect('/layout/js-min/:file', array(
		'controller' => 'minified_assets', 
		'action' => 'js',
		'plugin' => 'layout'
	), array('pass' => array('file')));

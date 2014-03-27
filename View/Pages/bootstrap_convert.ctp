<?php
Configure::write('debug', 2);

/***
 * STYLE UPDATES
 ****/
# Directories
$dirs = array(
	APP,
);
//Adds all Plugins
$plugins = array_filter(glob(APP . 'Plugin' . DS . '*', GLOB_ONLYDIR));
foreach ($plugins as $plugin) {
	$dirs[] = $plugin . DS;
}


$replace = array(
	'$dropdown-link-color-active' => '$dropdown-link-active-color',
	'$dropdown-link-background-active' => '$dropdown-link-active-bg',
	'$dropdown-link-color-hover' => '$dropdown-link-hover-color',
	'$dropdown-link-background-hover' => '$dropdown-link-hover-bg',
	'$dropdown-background' => '$dropdown-bg',
	'$horizontal-component-offset' => '$component-offset-horizontal',
	'$base-font-size' => '$font-size-base',
	'$base-font-family' => '$font-family-base',
	
	'$btn-primary-background' => '$btn-primary-bg',
	//'$table-border' => '$table-border-color',
	'$white' => '#FFF',
	'$black' => '#000',
	
	'$success-background' => '$state-success-bg',
	'$state-error-bg' => '$state-danger-bg',
	'$error-background' 	=> '$state-error-bg',
	'$error-text' 			=> '$state-danger-text',
	'$error-border' 		=> '$state-danger-border',
	
	'$warning-background' 	=> '$state-warning-bg',
	'$warning-text' 		=> '$state-warning-text',
	'$warning-border' 		=> '$state-warning-border',
	
	'$success-background' 	=> '$state-success-bg',
	'$success-text' 		=> '$state-success-text',
	'$success-border' 		=> '$state-success-border',
	
	'$info-background'	 	=> '$state-info-bg',
	'$info-text' 			=> '$state-info-text',
	'$info-border' 			=> '$state-info-border',
	
	'$btn-danger-background' => '$btn-danger-bg',
	'$btn-info-background' => '$btn-info-bg',
	'$btn-warning-background' => '$btn-warning-bg',

);

foreach ($dirs as $dir) {
	//Update SASS variabls
	/*
	$sassDir = $dir . 'webroot' . DS . 'scss' . DS;
	if (is_dir($sassDir)) {
		$files = array_filter(glob($sassDir . '*.scss'));
		foreach ($files as $file) {
			$content = file_get_contents($file);
			$content = preg_replace_callback(
				'/([a-z])([A-Z])/', 
				function($matches) {
					return $matches[1] . '-' . strtolower($matches[2]);
				}, 
				$content
			);
			//Reinstates caps lock for hex colors
			$content = preg_replace_callback(
				'/#[A-Za-z0-9\-\_]+/',
				function ($matches) {
					if (preg_match('/^#[A-Fa-f0-9\-]{1,7}$/', $matches[0])) {
						$matches[0] = str_replace('-', '', $matches[0]);
					}
					return strtolower($matches[0]);
				},
				$content
			);
			
			//Strips functions that have been worked into CSS
			$content = preg_replace('/\@include border\-radius\(([^\)]+)\)/', 'border-radius: $1', $content);
			
			//Replaces new variable names
			$content = str_replace(array_keys($replace), $replace, $content);
			file_put_contents($file, $content);
		}
	}
	*/
	
	$viewDir = $dir . 'View' . DS;
	$depth = 3;
	if (is_dir($viewDir)) {
		$subDir = $viewDir;
		for ($i = 1; $i <= $depth; $i++) {
			$subDir = $viewDir . str_repeat('*' . DS, $i) . '*.ctp';
			debug($subDir);
			$files = array_filter(glob($subDir));
			foreach ($files as $file) {
				$content = file_get_contents($file);
				$replace = array(
					'/span([0-9]+)/' => 'col-sm-$1',
					'/row\-fluid/' => 'row',
					'/container\-fluid/' => 'container',
					'/btn\-large/' => 'btn-lg',
					'/hero\-unit/' => 'jumbotron',
					'/input-small/' => 'input-sm',
					'/input-large/' => 'input-lg',
				);
				$content = preg_replace(array_keys($replace), $replace, $content, -1, $count);
				if ($count) {
					file_put_contents($file, $content);
					debug(compact('file', 'count'));
				}
			}
		}
	}
}
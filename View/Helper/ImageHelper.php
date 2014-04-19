<?php
class ImageHelper extends LayoutAppHelper {
	var $name = 'Image';
	var $helpers = array('Html');

	//Model field in a result with image source
	var $imageField = 'filename';
	
	//The path to the images folder on the server
	var $root = true;

	//Optional external server path
	var $externalServer;
	
	//The path from images to the specific image type directory
	var $base = false;
	
	//Further sub-directory, usually specifiying what size of image ('/thumb/', '/small/')
	var $defaultDir = false;
	
	var $defaultFile = false;
	
	//If false, prevent caching the image when displayed
	var $cache = true;
	
	function __construct(View $view, $settings = array()) {
		$this->_set($settings);
		parent::__construct($view, $settings);
		if ($this->root === true) {
			$this->root = IMAGES;
		}
		if (!empty($settings['externalServer'])) {
			$this->externalServer = $settings['externalServer'];
		}
	}

	function getExternalServer($options = array()) {
		$root = !empty($options['externalServer']) ? $options['externalServer'] : $this->externalServer;
		if (substr($root,-5) == '/img/') {
			if (!empty($options['plugin'])) {
				$root = substr($root,0,-5) . '/' . strtolower($options['plugin']) . '/img/';
			}
		}
		return $root;
	}
	
	/** 
	 * Finds the html path to an image
	 *
	 * @param string $file The unique part of the image filename, usually stored in the
	 * 		database
	 * @param array $options Path options
	 * @return string HTML-safe path to image
	 **/
	function src($file, $options = array()) {
		//Src-Specific options
		$srcOptions = array(
			'cache' => $this->cache,
			'addDate' => true,
		);
		
		foreach ($srcOptions as $key => $val) {
			if (isset($options[$key])) {
				$val = $options[$key];
				unset($options[$key]);		//Prevents it from being passed to path function
			}
			$$key = $val;
		}		
		
		$src = $this->path($file, array('useRoot' => false, 'ds' => '/') + $options);
		if (!$cache) {
			$options['modified'] = date('Ymdhis');
		}
		if ($addDate && !empty($options['modified'])) {
			$src .= '?u=' . $options['modified'];
		}
		return $src;
	}
	
	/**
	 * Finds the complete path to an image
	 *
	 * @param string $file The unique part of the image filename, usually stored in the 
	 * 		database
	 * @param array $options Additional options
	 * 		- root: 	the system root to the image directory
	 *		- base: 	the image directory
	 *		- dir: 		a sub-directory within the image directory (ie: small/, thumb/, etc)
	 *		- ds: 		Directory separator
	 * @return string path to image
	 **/	
	function path($file, $options = array()) {
		$options = array_merge(array(
			'root' => $this->root,
			'useRoot' => true,
			'externalServer' => $this->externalServer,
			'base' => $this->base,
			'dir' => $this->dir,
			'ds' => DS,
			'isFile' => true,
			'defaultFile' => $this->defaultFile,
			'imageField' => $this->imageField,
		), $options);
		if (!empty($options['externalServer'])) {
			$options['useRoot'] = false;
			$options['isFile'] = false;
			$root = $options['externalServer'];
			if (substr($root,-5) == '/img/') {
				if (!empty($options['plugin'])) {
					$plugin = Inflector::singularize(Inflector::tableize($options['plugin']));
					$root = substr($root,0,-5) . "/$plugin/img/";
					unset($options['plugin']);
				}
			}
			$options['externalServer'] = $root;
		}
		if (is_array($file)) {
			if (isset($file[$options['imageField']])) {
				$file = $file[$options['imageField']];
			} else {
				$file = null;
			}
		}
		$paths = array('root', 'externalServer', 'base', 'dir');
		//Removes Root if we're skipping it
		if ($options['useRoot'] === false) {
			array_shift($paths);
		}
		$dirs = array();
		foreach ($paths as $path) {
			if (!empty($options[$path])) {
				$dirs[] = $options[$path];
			}
		}
		if ($options['isFile']) {
			$fullpath = $this->path($file, array('isFile' => false, 'useRoot' => true) + $options);
			if (!is_file($fullpath)) {
				$file = null;
			}
		}
		if (empty($file)) {
			if (!empty($options['defaultFile'])) {
				$file = $options['defaultFile'];
			} else {
				return null;
			}
		}		
		$dirs[] = $file;
		$path = $this->joinDirs($dirs, $options['ds']);
		if (!empty($options['plugin']) && empty($options['useRoot'])) {
			$path = $options['plugin'] . '.' . $path;
		}
		return $path;
	}
	
	function thumb($file, $options = array()) {
		$options = array_merge(array('dir' => $this->defaultDir), $options);
		return $this->image($file, $options);
	}
	
	function image($file, $options = array()) {
		if (!empty($options) && !is_array($options)) {
			$options = array('dir' => $options);
		}
		$imgFields = array('src', 'alt', 'class', 'id', 'width', 'height', 'fullBase');
		foreach ($options as $key => $val) {
			if (strpos($key, 'data-') === 0) {
				$imgFields[] = $key;
			}
		}
		
		$url = Param::keyCheck($options, 'url', true, false);
		$image = '';
		if (!empty($options['src'])) {
			$src = $options['src'];
		} else {
			$src = $this->src($file, $options);
		}
		if ($src) {
			$image = $this->Html->image($src, $this->narrowOptions($options, $imgFields));
			if ($url) {
				$linkOptions = array('escape' => false);
				if (!empty($options['alt'])) {
					$linkOptions['title'] = $options['alt'];
				}
				$image = $this->Html->link($image, $url, $linkOptions);
			}
		}
		return $image;
	}
	
	private function joinDirs($dirs = array(), $ds = DS) {
		$path = '';
		foreach ($dirs as $k => $dir) {
			if (empty($dir)) {
				continue;
			}
			if ($k > 0 && $dir[0] != $ds) {
				$path .= $ds;
			}
			$path .= $dir;
		}
		return $this->setDs($path, $ds);
	}
	
	private function setDs($path, $ds = DS) {
		return preg_replace('@[\\\\/]@', $ds, $path);
	}

	private function narrowOptions($options = array(), $fields = array()) {
		$return = array();
		foreach ($fields as $field) {
			if (isset($options[$field])) {
				$return[$field] = $options[$field];
			}
		}
		return $return;
	}	
}
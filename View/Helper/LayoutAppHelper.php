<?php
//App::uses('AppHelper', 'View/Html');
App::uses('Hash', 'Utility');

class LayoutAppHelper extends AppHelper {
	protected $bootstrap = true;
	protected $localBootstrap = true;

	protected $_tagAttributes = array(
		'id','class','style','title',
	);
	
	var $defaultHelpers = array();
	var $defaultCss = array();
	var $defaultJs = array();
	
	public function __construct(View $View, $settings = array()) {
		$this->mergeDefaultHelpers();
	
		if (CakePlugin::loaded('TwitterBootstrap')) {
			$this->bootstrap = true;
			foreach (array('Html', 'Form', 'Paginator') as $helper) {
				if (
					!isset($this->helpers[$helper]) &&
					(($key = array_search($helper, $this->helpers)) !== false)
				) {
					unset($this->helpers[$key]);
					$this->helpers[$helper] = array();
				}
				$this->helpers[$helper]['className'] = 'TwitterBootstrap.Bootstrap'.$helper;
			}
		}
		$this->_tagAttributes = array_combine($this->_tagAttributes, $this->_tagAttributes);
		parent::__construct($View, $settings);
	}
	
	public function beforeRender($viewFile) {
		if (!empty($this->defaultCss)) {
			$this->Html->css($this->defaultCss, null, array('inline' => false));
		}
		if (!empty($this->defaultJs)) {
			$this->Html->script($this->defaultJs, array('inline' => false));
		}
		parent::beforeRender($viewFile);
	}

	public function setDefaultHelper($helper, $config = array()) {
		if (is_array($helper)) {
			$helper = Hash::normalize($helper);
			foreach ($helper as $k => $v) {
				$this->setDefaultHelper($k, $v);
			}
		} else {
			$this->defaultHelpers[$helper] = $config;
		}
	}

	public function resetDefaultHelpers() {
		$this->defaultHelpers = array();
	}
	
	protected function mergeDefaultHelpers() {
		if (!empty($this->defaultHelpers)) {
			$helpers = Hash::normalize($this->defaultHelpers);
			foreach ($helpers as $helper => $config) {
				list($plugin, $name) = pluginSplit($helper);
				if (!isset($this->helpers[$helper]) && get_class($this) != $name . 'Helper') {
					$this->helpers[$helper] = $config;
				}
			}
		}
	}

	function getCurrentModel() {
		$models = array_keys($this->request->models);
		return $models[0];
	}
	
	function isTagAttribute($tag) {
		return isset($this->_tagAttributes[$tag]);
	}
	
/**
 * Filters out any keys from an array not found in key array
 *
 * @var Array $array Array to check for keys
 * @var Array $keys Keys to make sure exist in $array
 *
 * @return Array Filtered $array
 **/
	protected function keyFilter($array, $keys) {
		$keys = array_flip($keys);
		foreach ($array as $key => $value) {
			if (!isset($keys[$key])) {
				unset($array[$key]);
			}
		}
		return $array;
	}

	protected function _getResult($result, $alias = null) {
		if (empty($alias)) {
			$alias = $this->modelName;
		}
		return !empty($result[$alias]) ? $result[$alias] : $result;
	}	
}
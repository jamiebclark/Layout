<?php
//App::uses('AppHelper', 'View/Html');
class LayoutAppHelper extends AppHelper {
	protected $bootstrap = false;
	protected $localBootstrap = true;

	protected $_tagAttributes = array(
		'id','class','style','title',
	);
	
	var $defaultHelpers = array('CakeAssets.Asset');
	var $defaultCss = array();
	var $defaultJs = array();
	
	function __construct(View $View, $settings = array()) {
		foreach ($this->defaultHelpers as $helper => $config) {
			if (is_numeric($helper)) {
				$helper = $config;
				$config = array();
			}
			list($plugin, $name) = pluginSplit($helper);
			if (!isset($this->helpers[$helper]) && $name != $this->name) {
				$this->helpers[$helper] = $config;
			}
		}
	
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
	
	function beforeRender($viewFile) {
		if ($this->name == 'Asset') {
			$Asset =& $this;
		} else {
			$Asset =& $this->Asset;
		}
		$Asset->css($this->defaultCss);
		$Asset->js($this->defaultJs);

		parent::beforeRender($viewFile);
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
<?php
class LayoutAppHelper extends AppHelper {

	protected $bootstrap = false;

	var $defaultHelpers = array('Layout.Asset');
	var $defaultCss = array('Layout.style');
	var $defaultJs = array('Layout.script');
	
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
	
	debug('Loading');
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
			debug($this->helpers);
		}
		parent::__construct($View, $settings);
	}
	
	function beforeRender($writeFile) {
		if ($this->name == 'Asset') {
			$Asset =& $this;
		} else {
			$Asset =& $this->Asset;
		}
		$Asset->css($this->defaultCss);
		$Asset->js($this->defaultJs);

		parent::beforeRender($writeFile);
	}
	
	function getCurrentModel() {
		$models = array_keys($this->request->models);
		return $models[0];
	}
}
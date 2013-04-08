<?php
class LayoutAppHelper extends AppHelper {

	protected $bootstrap = false;
	
	function __construct(View $View, $settings = array()) {
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
		parent::__construct($View, $settings);			
	}
}
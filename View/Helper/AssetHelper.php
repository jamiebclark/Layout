<?php
App::uses('LayoutAppHelper', 'Layout.View/Helper');
class AssetHelper extends LayoutAppHelper {
	var $name = 'Asset';
	var $helpers = array('Html');
	
	//Assets to be loaded whenever helper is called, broken down by category
	public $defaultAssets = array(
		'jquery' => array(
			'css' => array('Layout.jquery/ui/ui-lightness/jquery-ui-1.10.3.custom.min'),
			'js' => array(
				'//ajax.googleapis.com/ajax/libs/jquery/1.10.0/jquery.min.js',
				'//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js'
			),
		),
		'bootstrap' => array(
			'css' => array('Layout.bootstrap', 'Layout.bootstrap-responsive'),
			'js' => array('Layout.bootstrap/bootstrap.min'),
		),
		'default' => array(
			'css' => array('Layout.style'),
			'js' => array('Layout.script')
		)
	);
	//After constructor, all assets will be stored here
	private $_defaultAssets = array();

	private $_assetTypes = array('css', 'js');
	private $_assets = array();
	private $_usedAssets = array();
	private $_blocked = array();

	
	function __construct(View $view, $settings = array()) {
		parent::__construct($view, $settings);
		
		foreach ($this->defaultAssets as $assetGroupKey => $assetGroup) {
			if (isset($settings[$assetGroupKey])) {
				foreach ($this->_assetTypes as $type) {
					if (isset($settings[$assetGroupKey][$type])) {
						$this->defaultAssets[$assetGroupKey][$type] = $settings[$assetGroupKey][$type];
					}
				}
				unset($settings[$assetGroupKey]);
			}
		}
		$this->_set($settings);
		$this->setDefaultAssets();
		foreach ($this->_assetTypes as $type) {
			if (!empty($this->_defaultAssets[$type])) {
				$this->$type($this->_defaultAssets[$type]);
			}
			if (!empty($options[$type])) {
				$this->$type($options[$type]);
			}
		}
	}
	
	function js($file, $config = array()) {
		$type = 'js';
		if (!empty($config['afterBlock'])) {
			$type = 'jsAfterBlock';
			unset($config['afterBlock']);
		}
		return $this->_addFile($type, $file, $config);
	}
	
	function css($file, $config = array()) {
		return $this->_addFile('css', $file, $config);
	}	
	
	function block($script, $config = array()) {
		return $this->_addFile('block', $script, $config);
	}
	
	function removeCss($file) {
		return $this->_removeFile('css', $file);
	}
	
	function removeJs($file) {
		return $this->_removeFile('js', $file);
	}

	function output($inline = false, $repeat = false) {
		$assetOrder = array('css', 'js', 'block', 'jsAfterBlock');
		$eol = "\n\t";
		$out = $eol . '<!--- ASSETS -->'. $eol;
		foreach ($assetOrder as $type) {
			if (!empty($this->_assets[$type])) {
				foreach ($this->_assets[$type] as $file => $config) {
					if (isset($this->_usedAssets[$type][$file]) && !$repeat) {
						continue;
					}
					$out .= $this->_output($type, $file, $config, $inline) . $eol;
					$this->_usedAssets[$type][$file] = $config;
				}
			}
		}
		$out .= '<!--- END ASSETS -->'. $eol;
		return $out;
	}
	
	/**
	 * Adds a file to the asset cache
	 *
	 * @param string $type  The type of asset (css or js)
	 * @param array|string $files The path to the file or files to be added
	 * @param array $configAll Settings to be passed to all file
	 * @return boolean On success
	 **/
	protected function _addFile($type, $files, $configAll = array()) {
		if (!is_array($files)) {
			$files = array($files);
		}
		if (!isset($this->_assets[$type])) {
			$this->_assets[$type] = array();
		}
		$typeFiles =& $this->_assets[$type];
		$prependCount = 0;
		foreach ($files as $file => $config) {
			if (is_numeric($file)) {
				$file = $config;
				$config = array();
			}
			if ($file === false) {
				continue;
			}
			if (isset($this->_blocked[$type][$file])) {
				continue;
			}
			
			$config = array_merge($configAll, $config);
			if (!empty($config['prepend'])) {
				unset($config['prepend']);
				$insert = array($file => $config);
				if (empty($typeFiles)) {
					$this->_assets[$type] += $insert;
				} else if (empty($prependCount)) {
					$this->_assets[$type] = $insert + $typeFiles;
					$prependCount++;
				} else {
					$before = array_slice($typeFiles,0,$prependCount);
					$after = array_slice($typeFiles,$prependCount);
					$this->_assets[$type] = $before + $insert + $after;
					$prependCount++;
				}
			} else {
				$typeFiles[$file] = $config;
			}
		}
		return true;
	}
	
	protected function _removeFile($type, $files) {
		if (!is_array($files)) {
			$files = array($files);
		}
		foreach ($files as $file) {
			if (isset($this->_assets[$type][$file])) {
				unset($this->_assets[$type][$file]);
			}
			$this->_blocked[$type][$file] = $file;
		}
	}
	
	protected function _output($type, $file, $config = array(), $inline = false) {
		$options = compact('inline');
		if (!empty($config['plugin'])) {
			$options['plugin'] = $config['plugin'];
			unset($config['plugin']);
		}
		if ($type == 'css') {
			$keys = array('media');
			foreach ($keys as $key) {
				if (!empty($config[$key])) {
					$options[$key] = $config[$key];
				}
			}
			$out = $this->Html->css($file, null, $options);
			if (!empty($config['if'])) {
				$out = sprintf('<!--[if %s]>%s<![endif]-->', $config['if'], $out);
			}
		} else if ($type == 'js' || $type == 'jsAfterBlock') {
			$out = $this->Html->script($file, $options);
		} else if ($type == 'block') {
			$out = $this->Html->scriptBlock($file, $options);
		}
		return $out;
	}

	private function setDefaultAssets() {
		$default = array('css' => array(), 'js' => array());
		foreach ($this->defaultAssets as $assetGroup) {
			foreach ($assetGroup as $type => $assets) {
				if (is_array($assets)) {
					$default[$type] = array_merge($default[$type], $assets);
				} else {
					$default[$type][] = $assets;
				}
			}
		}
		$this->_defaultAssets = $default;
	}
}
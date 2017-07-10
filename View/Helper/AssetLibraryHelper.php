<?php
App::uses('PluginConfig', 'Layout.Lib');

class AssetLibraryHelper extends LayoutAppHelper {
	public $helpers = ['Html'];

	private $_libaries = [];
	private $_assetTypes = ['js', 'css'];

	public function __construct($View, $options = []) {
		parent::__construct($View, $options);
		$config = PluginConfig::init('Layout');
		$this->_libraries = array_merge(
			$config['AssetLibrary'], 
			(array) $options
		);
	}


	public function css($file, $block = null, $inline = false) {
		$output = $this->_setCss($file, $block, $inline);
		return $inline ? $output : $this;
	}

	public function js($file, $block = null, $inline = false) {
		$output = $this->_setJs($file, $block, $inline);
		return $inline ? $output : $this;
	}

	public function library($libKey, $assetType = null, $inline = false) {
		$output = $this->_setLibrary($libKey, $assetType, $inline);
		return $inline ? $output : $this;
	}


	private function _setCss($file, $block = null, $inline = false) {
		return $this->Html->css($file, null, ['inline' => $inline, 'block' => $this->_getBlock($block, 'css')]);
	}

	private function _setJs($file, $block = null, $inline = false) {
		return $this->Html->script($file, ['inline' => $inline, 'block' => $this->_getBlock($block, 'script')]);
	}

	public function getLibraryFiles($libKey, $filterAssetType = null) {
		$files = [];
		foreach ($this->_assetTypes as $assetType) {
			if (!empty($filterAssetType) && $filterAssetType != $assetType) {
				continue;
			}
			if (empty($files[$assetType])) {
				$files[$assetType] = [];
			}
			if (!empty($this->_libraries[$libKey][$assetType])) {
				$file = $this->_libraries[$libKey][$assetType];
				if (is_array($file)) {
					$file = $file[0];
				}
				$files[$assetType][] = $file;
			}
		}
		return $files;
	}

	private function _setLibrary($libKey, $filterAssetType = null, $inline = false, $output = '') {
		if (is_array($libKey)) {
			foreach ($libKey as $libVal) {
				$output = $this->_setLibrary($libVal, $filterAssetType, $inline, $output);
			}
		} else {
			if (empty($this->_libraries[$libKey])) {
				throw new Exception("Asset library $libKey not found");
			}
			foreach ($this->_assetTypes as $type) {
				if (!empty($filterAssetType) && $type != $filterAssetType) {
					continue;
				}
				if (!empty($this->_libraries[$libKey][$type])) {
					foreach ($this->_libraries[$libKey][$type] as $k => $file) {
						if (is_array($file)) {
							list($file, $block) = $file;
						} else {
							$block = null;
						}
						$output .= call_user_func_array(
							[$this, '_set' . ucfirst($type)], 
							[$file, $block, $inline]
						);
					}
				}
			}
		}
		return $output;	
	}

	private function _getBlock($block = null, $default = null) {
		return !empty($block) ? $block : $default;
	}
}
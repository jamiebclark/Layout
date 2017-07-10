<?php
class LayoutAssetHelper extends LayoutAppHelper {
	const JQUERY = '3.2.1';
	const JQUERY_UI = '1.12.1';
	const JQUERY_UI_THEME = 'smoothness';

	public $helpers = ['Html'];

	private $_libaries = [];

	public function __construct($View, $options = []) {
		parent::__construct($View, $options);
		$this->_libraries = [
			'jquery' => [
				'js' => [
					['//ajax.googleapis.com/ajax/libs/jquery/' . self::JQUERY . '/jquery.min.js', 'jsFirst']
				],
			],
			'jquery-ui' => [
				'js' => [
					['//ajax.googleapis.com/ajax/libs/jqueryui/' . self::JQUERY_UI . '/jquery-ui.min.js', 'jsFirst']
				],
				'css' => [
					['//ajax.googleapis.com/ajax/libs/jqueryui/' . self::JQUERY_UI . '/themes/' . self::JQUERY_UI_THEME . '/jquery-ui.css', 'cssFirst']
				]
			]
		];

	}

	public function css($file, $block = null) {
		$this->Html->css($file, null, ['inline' => false, 'block' => $this->_getBlock($block, 'css')]);
		return $this;
	}

	public function js($file, $block = null) {
		$this->Html->script($file, ['inline' => false, 'block' => $this->_getBlock($block, 'script')]);
		return $this;
	}

	public function library($libKey) {
		if (is_array($libKey)) {
			foreach ($libKey as $libVal) {
				$this->library($libVal);
			}
		} else {
			if (empty($this->_libraries[$libKey])) {
				throw new Exception("Asset library $libKey not found");
			}
			foreach (['js', 'css'] as $type) {
				if (!empty($this->_libraries[$libKey][$type])) {
					foreach ($this->_libraries[$libKey][$type] as $k => $file) {
						if (is_array($file)) {
							list($file, $block) = $file;
						} else {
							$block = null;
						}
						call_user_func_array([$this, $type], [$file, $block]);
					}
				}
			}
		}
		return $this;
	}

	private function _getBlock($block = null, $default = null) {
		return !empty($block) ? $block : $default;
	}
}
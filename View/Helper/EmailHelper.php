<?php
App::uses('LayoutAppHelper', 'Layout.View/Helper');
App::uses('EmailText', 'Layout.Lib');

class EmailHelper extends LayoutAppHelper {
	public $name = 'Email';
	
	public $helpers = array(
		'Html',
		'Layout.DisplayText',
	);

	protected $_engine;
	
	public function __construct($View, $settings = array()) {
		if (!empty($settings['helpers'])) {
			$this->helpers = array_merge($this->helpers, (array) $settings['helpers']);
		}
		$this->_engine = 'EmailText';
		parent::__construct($View, $settings);
	}

/**
 * Call methods from String utility class
 *
 * @param string $method Method to call.
 * @param array $params Parameters to pass to method.
 * @return mixed Whatever is returned by called method, or false on failure
 */
	public function __call($method, $params) {
		return call_user_func_array(array($this->_engine, $method), $params);
	}

	
	function image($url, $options = array()) {
		//Makes sure the image has the aboslute path
		if (substr($url, 0, 1) != '/') {
			$url = Router::url('/img/' . $url, true);
		}
		if (!empty($options['url'])) {
			$options['url'] = Router::url($options['url'], true);
		}
		if (empty($options['style'])) {
			$options['style'] = '';
		}
		$options['style'] .= 'border:0;';
	
		return $this->Html->image($url, $options);
	}
	
	function link($title, $url, $options = array(), $confirm = null) {
		$url = Router::url($url, true);
		return $this->Html->link($title, $url, $options, $confirm);
	}
	
	
	//Formats text for being displayed in Plain-text emails, but then re-formats to be displayed in an HTML page
	function textHtml($text) {
		return $this->Html->tag('code', nl2br($this->text($text)));
	}
	
	public function evalVars($text) {
		extract($this->viewVars);
		return $this->_engine->evalVars($text);
	}	
	
	function loadHelpers($helpers = array()) {
		if (!is_array($helpers)) {
			preg_match('/[a-zA-Z_0-9]+/', $helpers, $helpers);
		}
		if (!empty($helpers)) {
			foreach ($helpers as $helper) {
				$this->_loadHelper($helper);
			}
		}
	}
	
	function _loadHelper($helper) {
		if (empty($this->{$helper})) {
			App::uses($helper, 'Helper');
			$this->helpers[] = $helper;
			$this->{$helper} = $this->_View->loadHelper($helper);
		}
		return $this->{$helper};
	}

}
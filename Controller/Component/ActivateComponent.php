<?php
/**
 * Used with any model containing an "active" boolean field
 *
 **/
App::uses('Url', 'Layout.Lib');
App::uses('InflectorPlus', 'Layout.Lib');

class ActivateComponent extends Component {
	var $name = 'Activate';
	var $controller;
	var $settings = array();
	
	/*
	var $userType = array();	//Optional setting to only let specific user types activate
	
	var $model;
	var $field;
	var $format;
	var $paramOn;
	var $paramOff;
	*/
	
	var $sessionOutput;
	var $_named = array();
	
	function __construct(ComponentCollection $collection, $settings = array()) {
		$this->settings = $settings;
		$settings = $this->_getSettings($settings);
		parent::__construct($collection, $settings);
	}

	function initialize(Controller $controller) {
		$this->controller =& $controller;
		if (!empty($this->controller->request->params['named'])) {
			$this->_named = $this->controller->request->params['named'];
		}
		
		$model = !empty($controller->modelClass) ? $controller->modelClass : null;
		
		$this->settings['model'] = $model;

		$this->settings['humanName'] = InflectorPlus::humanize($model);
		
		if (!method_exists($this->controller, '_beforeActivate') || $this->controller->_beforeActivate()) {
			$this->paramCheck();
		}
		parent::initialize($controller);
	}
	
	//Scans incoming paramaters for actions
	public function paramCheck($settings = array()) {
		if (!empty($this->_named)) {
			$settings = $this->_getSettings($settings);
			list($paramOn, $paramOff) = is_array($settings['param']) ? $settings['param'] : array($settings['param'], $settings['param']);
			if (!empty($this->_named[$paramOn])) {
				$this->activate($this->_named[$paramOn], $settings);
			} else if (!empty($this->_named[$paramOff])) {
				$this->deactivate($this->_named[$paramOff], $settings);
			}
		}
	}
	
	public function activate($id, $settings = array(), $setOn = true) {
		$settings = $this->_getSettings($settings);
		extract($settings);
		
		$Model =& $this->controller->{$model};		$data = array(			$Model->primaryKey => $id,			$field => $this->_getVal($setOn, $format),		);
		$success = $Model->save($data, array('validate' => false, 'callbacks' => false));

		$this->_setFlash($success, $id, $setOn, $settings);
		$this->_redirect($setOn, $settings);		
	}
	
	public function deactivate($id, $settings = array()) {
		return $this->activate($id, $settings, false);	
	}
	
	//Sets Session message
	private function _setFlash($success, $id, $setOn, $settings) {
		if ($success) {
			$msg = 'Successfully marked';
			$class = 'alert-success';
			if (method_exists($this->controller, '_afterActivate')) {
				$this->controller->_afterActivate($id, $setOn);
			}
		} else {
			$msg = 'There was an error marking';
			$class = 'alert-error';
		}
		$msg .= sprintf(' <a href="%s">%s</a> ', Router::url(array('action' => 'view', $id)), $settings['humanName']);
		$msg .= $this->_pickSetting($settings, 'verb', $setOn);
		
		if ($this->sessionOutput) {
			$this->controller->Session->setFlash($msg, 'default', compact('class'));
		}
	}
	
	//Redirects after activation completes
	private function _redirect($setOn, $settings) {
		$redirect = $this->controller->referer(null, true);
		if (empty($redirect) || $redirect == '/') {
			$redirect = array('action' => 'index');
		} else {
			$redirect = Url::urlArray($redirect);
		}

		$param = $this->_pickSetting($settings, 'param', $setOn);
		$paramOther = $this->_pickSetting($settings, 'param', !$setOn);
		unset($redirect[$param]);
		unset($redirect[$paramOther . '_finished']);
		
		$redirect[$param . '_finished'] = 1;

		return $this->controller->redirect($redirect);
	}

	//Adds default settings
	private function _getSettings($settings = array()) {
		$default = array(
			'model' => null,
			'param' => array('activate', 'deactivate'),
			'field' => 'active',
			'verb' => false,
			'sessionOutput' => true,
			'format' => 'boolean',
		);
		$settings = array_merge($default, $this->settings, $settings);
		//Legacy Variable Name
		if (!empty($settings['column'])) {
			$settings['field'] = $settings['column'];
		}
		if (is_array($settings['param'])) {
			list($paramOn, $paramOff) = $settings['param'] + array(null, null);
			$settings += compact('paramOn', 'paramOff');
		}
		if (empty($settings['verb'])) {
			$settings['verb'] = array($paramOn, $paramOff);
		} else if (!is_array($settings['verb'])) {
			$settings['verb'] = array($settings['verb'], 'not ' . $settings['verb']);
		}
		return $settings;
	}
	
	private function _getVal($setOn = true, $format = null) {
		$val = true;
		if ($format == 'date') {
			$val = $setOn ? date('Y-m-d H:i:s') : null;
		} else {
			$val = $setOn ? 1 : 0;
		}
		return $val;
	}
	
	// Finds a setting that is saved as a 2 item array, key 0 for "on", key 1 for "off"
	private function _pickSetting($settings = array(), $field, $setOn = true) {
		if (!isset($settings[$field])) {
			return null;
		} else if (!is_array($settings[$field])) {
			return $settings[$field];
		} else {
			return $setOn ? $settings[$field][0] : $settings[$field][1];
		}
	}
}
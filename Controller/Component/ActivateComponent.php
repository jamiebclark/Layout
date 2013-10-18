<?php
/**
 * Used with any model containing an "active" boolean field
 *
 **/
App::uses('Url', 'Layout.Lib');

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
	
	function __construct(ComponentCollection $collection, $settings = array()) {
		$this->settings = $settings;
		$settings = array_merge(array(
			'model' => null,
			'param' => array('activate', 'deactivate'),
			'field' => 'active',
			'verb' => false,
			'sessionOutput' => true,
			'format' => 'boolean',
		), $settings);
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
		//debug($settings);
		parent::__construct($collection, $settings);
	}

	function startup(Controller $controller) {
		$this->controller =& $controller;
		$model = !empty($controller->modelClass) ? $controller->modelClass : null;
		
		$this->settings['model'] = $model;
		$this->settings['humanName'] = InflectorPlus::humanize($model);
		
		if (!method_exists($this->controller, '_beforeActivate') || $this->controller->_beforeActivate()) {
			$this->paramCheck();
		}
	}
	
	//Scans incoming paramaters for actions
	function paramCheck() {
		list($paramOn, $paramOff) = array($this->settings['paramOn'], $this->settings['paramOff']);
		if (!empty($this->controller->request->params['named'][$paramOn])) {
			$this->activate($this->controller->request->params['named'][$paramOn]);
		} else if (!empty($this->controller->request->params['named'][$paramOff])) {
			$this->deactivate($this->controller->request->params['named'][$paramOff]);
		}
	}
	
	function activate($id, $setOn = true) {
		extract($this->settings);
		$Model =& $this->controller->{$model};
				$data = array(			$Model->primaryKey => $id,			$field => $this->_getVal($setOn),		);
		$success = $Model->save($data, array('validate' => false, 'callbacks' => false));
		
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
		$msg .= sprintf(' <a href="%s">%s</a> ', Router::url(array('action' => 'view', $id)), $humanName);
		$msg .= $this->_pickSetting('verb', $setOn);
		
		$redirect = $this->controller->referer();
		if ($redirect == '/') {
			$redirect = array('action' => 'index');
		} else {
			$redirect = Url::urlArray($redirect);
		}
		$param = $this->_pickSetting('param', $setOn);
		unset($redirect[$param]);
		$redirect[$param . '_finished'] = 1;
		
		if ($this->sessionOutput) {
			$this->controller->Session->setFlash($msg, 'default', compact('class'));
		}
		$this->controller->redirect($redirect);
	}
	
	function deactivate($id) {
		return $this->activate($id, true);	
	}
	
	function _getVal($setOn = true) {
		$format = $this->settings['format'];
		$val = true;
		if ($format == 'date') {
			$val = $setOn ? date('Y-m-d H:i:s') : null;
		} else {
			$val = $setOn ? 1 : 0;
		}
		return $val;
	}
	
	// Finds a setting that is saved as a 2 item array, key 0 for "on", key 1 for "off"
	private function _pickSetting($field, $setOn = true) {
		if (!isset($this->settings[$field])) {
			return null;
		} else if (!is_array($this->settings[$field])) {
			return $this->settings[$field];
		} else {
			return $setOn ? $this->settings[$field][0] : $this->settings[$field][1];
		}
	}
}
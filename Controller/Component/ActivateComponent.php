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
		parent::__construct($collection, $settings);
	}

	function startup(&$controller) {
		$this->controller =& $controller;
		$model = !empty($controller->modelClass) ? $controller->modelClass : null;
		
		$this->settings['model'] = $model;
		$this->settings['humanName'] = Inflector::humanize($model);
		
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
	
	function activate($id, $reverse = false) {
		extract($this->settings);
		
		$Model =& $this->controller->{$model};
		$on = !$reverse;
		
		/*
		$Model->updateAll(array(
			$Model->alias . '.' . $this->field => !$reverse ? 1 : 0,
		), array(
			$Model->alias . '.' . $Model->primaryKey => $id,
		));
		*/
				$data = array(			$Model->primaryKey => $id,			$field => $this->_getVal($on),		);		$success = $Model->save($data);
		
		if ($success) {
			$msg = 'Successfully marked';
			$class = 'alert-success';
		} else {
			$msg = 'There was an error marking';
			$class = 'alert-error';
		}
		$msg .= sprintf(' <a href="%s">%s</a> ', Router::url(array('action' => 'view', $id)), $humanName);
		$msg .= $on ? $paramOn : $paramOff;
		$redirect = $this->controller->referer();
		if ($redirect == '/') {
			$redirect = array('action' => 'index');
		} else {
			$redirect = Url::urlArray($redirect);
		}
		$redirect['active'] = $id;
		
		if ($this->sessionOutput) {
			$this->controller->Session->setFlash($msg, 'default', compact('class'));
		}
		$this->controller->redirect($redirect);
	}
	
	function deactivate($id) {
		return $this->activate($id, true);	
	}
	
	function _getVal($on = true) {
		$format = $this->settings['format'];
		$val = true;
		if ($format == 'date') {
			$val = $on ? 'NOW()' : null;
		} else {
			$val = $on ? 1 : 0;
		}
		return $val;
	}
}
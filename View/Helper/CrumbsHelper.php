<?php
App::uses('InflectorPlus', 'Layout.Lib');
App::uses('Prefix', 'Layout.Lib');
App::uses('LayoutAppHelper', 'Layout.View/Helper');
class CrumbsHelper extends LayoutAppHelper {
	public $name = 'Crumbs';
	public $helpers = array('Html', 'Layout.Iconic');
	
	public $hide = false;
	public $title; //Title of the current page being view with crumbs
	
	var $baseCrumbs;
	var $defaultCrumbs;
	var $parentCrumbs;
	var $controllerCrumbs;
	var $actionCrumbs;
	var $userSetCrumbs;

	var $controllerTitle;
	
	var $controllerParentVar = array();
	
	/* The crumbs are made of the following parts
		- Base Crumbs
		- Default Crumbs * Legacy system, replaced by Controller / Action
		- Controller Crumbs
		- Action Crumbs
		
		- User-added Crumbs
	*/
	var $crumbTypes = array('base', 'default', 'parent', 'controller', 'path', 'action', 'userSet');
	var $legacyTypes = array('default');

	public $divider = null; //'&gt;''
	
	var $_crumbs = array();
	
	public function __construct(View $view, $settings = array()) {
		parent::__construct($view, $settings);

		if (empty($settings['controllerTitle'])) {
			$urlBase = $this->_getUrlBase($settings);
			$settings['controllerTitle'] = InflectorPlus::humanize($urlBase['controller']);
		}

		$vars = array();
		foreach ($this->crumbTypes as $type) {
			$key = $type . 'Crumbs';
			if (isset($settings[$key])) {
				$vars[$type] = $settings[$key];
				unset($settings[$key]);
			} 
		}
		$this->_set($settings);

		foreach ($vars as $type => $vars) {
			$this->_setCrumbType($type, $vars);
		}
		$this->addVars($settings);
	}

	public function add($title, $link = null, $options = null) {
		if (is_array($title)) {
			$crumb = $title + array(null, null, null);
		} else {
			$crumb = array($title, $link, $options);
		}
		$append = array($crumb);
		return $this->userSetCrumbs(compact('append'));	
	}
	
	public function debug() {
		$out = array();
		foreach ($this->crumbTypes as $type) {
			$out[$type] = $this->{$type . 'Crumbs'};
		}
		debug($out);
	}
	
	public function addVars($vars = array(), $options = array()) {
		foreach ($this->crumbTypes as $type) {
			if (isset($vars[$type . 'Crumbs'])) {
				$this->_setCrumbType($type, array('crumbs' => $vars[$type . 'Crumbs']), $options);
			}
		}
		if (!empty($vars['crumbs'])) {
			$this->addCrumbs($vars['crumbs']);
		}
		
		if (!empty($vars['parent'])) {
			$parentModel = !empty($vars['parentModel']) ? $vars['parentModel'] : $this->getModel();
			$this->setParent($parentModel, $vars['parent']);
		}
		return true;
	}
	
	public function addCrumbs($crumbs = array(), $options = array()) {
		foreach ($this->crumbTypes as $type) {
			if (!empty($options[$type . 'Crumbs'])) {
				$this->_setCrumbType($type, array('crumbs' => $options[$type . 'Crumbs']));
			}
		}
		if (!is_array($crumbs)) {
			$crumbs = array($crumbs);
		}
		foreach ($crumbs as $crumb) {
			$this->add($crumb);
		}
		return true;
	}
	
	public function output($options = array()) {
		$options = array_merge(array(
			'home' => $this->Iconic->icon('home'),
			'homeUrl' => '/',
			'before' => '',
			'after' => '',
			'crumbs' => array(),
			//'wrap' => 'li',
			'separator' => '<font>&gt;</font>',
		), $options);

		extract($options);

		$home = array($this->_getHomeUrl($home, $homeUrl));

		if ($this->hide && (!isset($hide) || $hide !== false)) {
			return null;
		}
		
		if (!empty($wrap)) {
			if (is_array($wrap)) {
				list($wrap, $wrapOptions) = $wrap;
			} else {
				$wrapOptions = array();
			}
			$wrapOpen = $this->Html->tag($wrap, null, $wrapOptions);
			$wrapClose = '</' . $wrap . '>';
			$before .= $wrapOpen;
			$after .= $wrapClose;
			$separator = $wrapClose . $wrapOpen;
		}
		if ($crumbs = $this->getCrumbs($crumbs, $home)) {
			if ($this->bootstrap) {
				$out = '';
				foreach ($crumbs as $crumb) {
					if (!empty($this->divider)) {
						$crumb .= $this->Html->tag('span', $this->divider, array('class' => 'divider'));
					}
					$out .= $this->Html->tag('li', $crumb);
				}
				return $this->Html->tag('ul', $out, array('class' => 'breadcrumb'));
			} else {
				return $this->Html->div('crumbs', $before . join($separator, $crumbs) . $after);
			}
		} else {
			return null;
		}
	}
	
	private function getCrumbs($crumbs, $home = array()) {
		$setCrumbs = array();
		if (count($this->Html->_crumbs)) {
			$setCrumbs = $this->Html->_crumbs;
		} else {
			foreach ($this->crumbTypes as $type) {
				$addCrumb = null;
				if (isset(${$type . 'Crumbs'})) {
					$addCrumb = ${$type . 'Crumbs'};
				} else {
					$addCrumb = $this->_setCrumbType($type, array(
						'skipDefault' => in_array($type, $this->legacyTypes),
					));
				}
				$setCrumbs = $this->_mergeCrumbs($setCrumbs, $addCrumb);
			}
		}
		
		$crumbs = $this->_mergeCrumbs($home, $setCrumbs, $crumbs);
		if (!empty($crumbs) && $crumbs != $home) {
			$out = array();
			$lastKey = count($crumbs) - 1;
			foreach ($crumbs as $k => $crumb) {
				if (is_array($crumb) && !empty($crumb[1]) && $k < $lastKey) { //Ensures last crumb is never a link
					$out[] = $this->Html->link($crumb[0], $crumb[1], $crumb[2]);
				} else {
					$out[] = $crumb[0];
				}
			}
			return $out;
		}
		return null;
	}
	
	function baseCrumbs($options = array()) {
		return $this->_setCrumbType('base', $options);
	}
	
	function defaultCrumbs($options = array()) {
		return $this->_setCrumbType('default', $options);
	}
	
	function setParent($model, $result, $options = array()) {
		if (!empty($result[$model])) {
			$result = $result[$model];
		}
		$controller = Inflector::tableize($model);
		$crumbs = array();
		$crumbs[] = array(InflectorPlus::humanize($controller), compact('controller') + array('action' => 'index'));
		$crumbs[] = array($result['title'], compact('controller') + array('action' => 'view', $result['id']));
		
		if (!empty($options['controllerVar'])) {
			$varName = ($options['controllerVar']==1 || $options['controllerVar']===true) ? 0 : $options['controllerVar'];
			$this->controllerParentVar = array($varName, $result['id']);
		}
		
		return $this->parentCrumbs(compact('crumbs'));
	}
	
	function parentCrumbs($options = array()) {
		return $this->_setCrumbType('parent', $options);
	}
	
	function controllerCrumbs($options = array()) {
		return $this->_setCrumbType('controller', $options);
	}
	
	function actionCrumbs($options = array()) {
		return $this->_setCrumbType('action', $options);
	}
	
	function userSetCrumbs($options = array()) {
		return $this->_setCrumbType('userSet', $options);
	}


	function hide($set = true) {
		$this->hide = $set;
	}
	
	function title($title) {
		$this->title = $title;
	}
	
	function _getCrumbType($type, $options = array()) {
		$varName = $type . 'Crumbs';
		if (!isset($this->{$varName}) || !empty($options['overwrite'])) {
			$crumbs = $this->_setCrumbType($type, $options);
		} else {
			$crumbs = $this->$varName;
		}
		return $crumbs;
	}
	
	function _setCrumbType($type, $options = array()) {
		$typeVarName = $type . 'Crumbs';

		if (empty($options['crumbs'])) {
			if (is_array($options)) {
				foreach ($options as $k => $v) {
					if (is_numeric($k)) {
						$options['crumbs'][$k] = $v;
						unset($options[$k]);
					}
				}
			} else {
				$options = array('crumbs' => $options);
			}
		}


		if (!empty($options['crumbs']) || (isset($options['crumbs']) && $options['crumbs'] === false)) {
			$crumbs = $options['crumbs'];
		} else if ((!isset($this->{$typeVarName}) || !empty($options['reset'])) && empty($options['skipDefault'])) {
			$crumbs = $this->_getDefaultCrumbType($type, $options);
		} else {
			$crumbs = $this->{$typeVarName};
		}
		if (!empty($options['prepend'])) {
			$crumbs = $this->_mergeCrumbs($options['prepend'], $crumbs);
		}
		if (!empty($options['append'])) {
			$crumbs = $this->_mergeCrumbs($crumbs, $options['append']);
		}
		$this->{$typeVarName} = $crumbs;
		
		//Unsets Controller and Action, using the legacy 'default' format
		if (!empty($crumbs) && $type == 'default') {
			$this->actionCrumbs = false;
			$this->controllerCrumbs = false;
		}
		
		return $crumbs;
	}
	
	function _getDefaultCrumbType($type, $options = array()) {
		$crumbs = array();
		if ($type == 'controller') {
			$urlBase = !empty($options['urlBase']) ? $options['urlBase'] : $this->_getUrlBase($options);
			if (!empty($this->controllerParentVar)) {
				list($controllerParentVarName, $controllerParentVar) = $this->controllerParentVar;
				$urlBase[$controllerParentVarName] = $controllerParentVar;
			}
			$crumbs = array(
				array($this->controllerTitle, array('action' => 'index') + $urlBase)
			);
		} else if ($type == 'path') {
			$urlBase = !empty($options['urlBase']) ? $options['urlBase'] : $this->_getUrlBase($options);
			$action = $urlBase['action'];
			if (!empty($this->_View->viewVars['path']) && $modelInfo = $this->getModelInfo()) {
				extract($modelInfo);	//model, primaryKey, displayField
				$path = $this->_View->viewVars['path'];
				$result = $this->_getPassedResult($model);
				$crumbs = array();
				foreach ($path as $row) {
					$row = $row[$model];
					$title = $row[$displayField];
					$id = $row[$primaryKey];
					if ($id != $result[$model][$primaryKey]) {
						$crumbs[] = array($title, array('action' => 'view', $id) + $urlBase);
					}
				}
			}
		} else if ($type == 'action') {
			$urlBase = !empty($options['urlBase']) ? $options['urlBase'] : $this->_getUrlBase($options);
			$action = $urlBase['action'];
			if ($modelInfo = $this->getModelInfo()) {
				extract($modelInfo);	//model, primaryKey, displayField
				$result = $this->_getPassedResult($model);
				if (!empty($result) && !empty($result[$model][$primaryKey])) {
					if ($action == 'view' && !empty($this->title)) {
						$title = $this->title;
					} else if (!empty($result[$model][$displayField])) {
						$title = $result[$model][$displayField];
					} else {
						$title = $this->Html->tag('em', 'blank');
					}
					$crumbs[] = array(
						$title, 
						array('action' => 'view', $result[$model][$primaryKey]) + $urlBase,
						array('escape' => false)
					);
				}
				
				if ($action != 'view' && $action != 'index') {
					$crumbs[] = array(
						!empty($this->title) ? $this->title : InflectorPlus::humanize($action),
						$urlBase,
					);
				}
			}
		} else if ($type == 'default') {
			$crumbs = $this->_mergeCrumbs($this->_getDefaultCrumbType('controller'), $this->_getDefaultCrumbType('action'));
		}
		return $crumbs;	
	}
	
	function _getHtmlCrumbs() {
		$crumbs = array();
		if (!empty($this->Html->_crumbs)) {
			$crumbs = $this->Html->_crumbs;
		}
		if ($crumbs == array('',null,null)) {
			return array();
		}
		return $crumbs;		
	}
	
	function _mergeCrumbs() {
		$args = func_get_args();
		$crumbs = array();
		foreach ($args as $crumb) {
			if (empty($crumb)) {
				continue;
			}
			if (!is_array($crumb)) {
				$crumb = array($crumb);
			}
			foreach ($crumb as $c) {
				if (empty($c) || $c == array(null, null, null)) {
					continue;
				}
				if (!is_array($c)) {
					$c = array($c);
				}
				$crumbs[] = ($c + array(null, null, null));
			}
		}
		return $crumbs;
	}
	
	//Checks the passed variables to see if there has been a query result passed to view
	function _getPassedResult($model) {
		$viewVars =& $this->_View->viewVars;
		$varName = InflectorPlus::varNameSingular($model);
		if (isset($viewVars[$varName]) && is_array($viewVars[$varName])) {
			$result = $viewVars[$varName];
		} else if (isset($this->request->data[$model])) {
			$result = array($model => $this->request->data[$model]);
		} else {
			$result = null;
		}
		return $result;
	}
	
	private function getModelInfo() {
		$model = $primaryKey = $displayField = null;
		if (!empty($this->request->params['models'])) {
			if ($Model = ClassRegistry::init($this->getModel())) {
				$model = $Model->alias;
				$displayField = $Model->displayField;
				$primaryKey = $Model->primaryKey;
			}
		}
		return compact('model', 'displayField', 'primaryKey');
	}
	
	private function getModel() {
		$models = array_values($this->request->params['models']);
		$modelInfo = array_shift($models);
		$model = !empty($modelInfo['plugin']) ? $modelInfo['plugin'] . '.' : '';
		$model .= $modelInfo['className'];

		return $model;
	}	
	
	function _getHomeUrl($home, $url = array()) {
		if (!empty($home)) {
			if (empty($url) && !empty($this->homeUrl)) {
				$url = $this->homeUrl;
			}
			if (!empty($url)) {
				$home = !is_array($home) ? array($home, $url) : array(1 => $url) + $home;
			}
			if (is_array($home)) {
				$home += array(null, null, array());
				$home[2] += array(
					'escape' => false,
					'title' => 'Home',
					'class' => 'home',
				);
			}
		} else {
			$home = null;
		}
		return $home;	
	}
	
	function _getUrlBase($options = array()) {
		$options = array_merge(array(
			'controller' => $this->request->params['controller'],
			'action' => $this->request->params['action'],
		), $options);
		extract($options);

		if (!empty($this->request->params['prefix'])) {
			$action = Prefix::removeFromAction($action, $this->request->params['prefix']);
		}

		$urlBase = compact('controller', 'action');
		if (!isset($prefix)) {
			$prefix = !empty($this->request->params['prefix']) ? $this->request->params['prefix'] : false;
		}
		if ($prefix) {
			$urlBase[$prefix] = true;
		}
		
		if (!empty($options['urlAdd'])) {
			$urlBase = $options['urlAdd'] + $urlBase;
		}
		return $urlBase;	
	}
}
<?php
App::uses('TextCleanup', 'Layout.Lib');
class TextCleanupBehavior extends ModelBehavior {
	var $settings = array();
	
	function setup(Model $Model, $settings = array()) {
		if (empty($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array();
		}
		
		$settings = array_merge(array(
			'beforeSave' => array('ms'),
			'afterFind' => array('ms', 'stripslashes'),
		), $settings);
		if (!empty($settings)) {
			$this->settings[$Model->alias] = array_merge(
				$this->settings[$Model->alias],
				(array) $settings
			);
		}
	}
	
	function afterFind(Model $Model, $result, $primary = true) {
		$result = $this->_findCleanup($Model, $result, 'afterFind');
		return $result;
	}
	
	function beforeSave(Model $Model, $options = array()) {
		$Model->data = $this->_findCleanup($Model, $Model->data, 'beforeSave');
		return true;
	}
	
	
	function _findCleanup(Model $Model, $results, $callback) {
		$settings = Param::keyValCheck($this->settings[$Model->alias], $callback);
		if ($settings) {
			if (isset($results[$Model->alias]['id'])) {
				$results = $this->_cleanup($results, $settings);
			} else {
				
				if (!empty($results[$Model->alias])) {
					$check =& $results[$Model->alias];
				} else {
					$check =& $results;
				}
				if (!empty($check)) {
					foreach ($check as $k => $result) {
						if (!empty($result[$Model->alias])) {
							$check[$k][$Model->alias] = $this->_cleanup($result[$Model->alias], $settings);
						} else {
							$check[$k] = $this->_cleanup($result, $settings);
						}
					}
				}
			}
		}
		return $results;
	}

	//Takes a result, loads any stored rules, and applies the rules one by one
	function _cleanup($result, $settings) {
		$clean = array();
		foreach ($settings as $key => $value) {
			if (is_numeric($key)) {
				$clean['*'][] = $value;
			} else {
				if (!is_array($value)) {
					$clean[$value] = array($key);
				} else {
					foreach ($value as $col) {
						$clean[$col][] = $key;
					}
				}
			}
		}
		if (is_array($result)) {
			foreach ($result as $col => $value) {
				$rules = array();
				if (isset($clean[$col])) {
					$rules = $clean[$col];
				}
				if (isset($clean['*'])) {
					$rules = array_merge($rules, $clean['*']);
				}
				$result[$col] = $this->_cleanColumn($value, $rules);
			}
		}
		return $result;
	}
	
	//Applies an array of rules to one specific value
	function _cleanColumn($value, $rules) {
		foreach ($rules as $rule) {
			if ($rule == 'ms') {
				$value = TextCleanup::ms($value);
			} else if (function_exists($rule)) {
				if (!is_array($value)) {
					$value = call_user_func($rule, $value);
				}
			} else {
				ddebug('Not found function');
			}
		}
		return $value;
	}
}

<?php
/***************************************
 * Select List Behavior
 * Date: 12/14/2013
 *
 * Used to efficiently create list results used for select form options
 *
 *************************************/
App::uses('Param', 'Layout.Lib');

class SelectListBehavior extends ModelBehavior {
	public $settings = array();
	
	public function setup(Model $Model, $settings = array()) {
		if (empty($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array();
		}
		
		$settings = array_merge(array(
			'conditions' => null,
			'blank' => true,
			'label' => Inflector::humanize(Inflector::singularize(Inflector::tableize($Model->alias))),
			'key' => $Model->alias . '.' . $Model->primaryKey,
			'value' => $Model->alias . '.' . $Model->displayField,
			'slug' => false,
			
			//Tree variables
			'keyPath' => null,
			'valuePath' => null,
			'spacer' => ' - ',
			'recursive' => null
		), (array) $settings);
		
		if (!empty($settings)) {
			$this->settings[$Model->alias] = array_merge(
				$this->settings[$Model->alias],
				(array) $settings
			);
		}
	}
	
	public function selectList(Model $Model, $options = array()) {
		$options = array_merge($this->settings[$Model->alias], (array) $options);
		extract($options);
		
		$skipRoot = Param::keyValCheck($options, 'skipRoot', true);
		
		$list = array();
		if ($blank) {
			$list[''] = isset($blankMessage) ? $blankMessage : ' -- Select ' . $label . ' -- ';
		}
		if ($slug = Param::keyValCheck($options, 'slug')) {
			$key = $Model->alias . '.slug';
			$keyPath = '{n}.' . $Model->alias . '.slug';
		}
		$fields = array('CONCAT(' . $key . ') AS listKey', 'CONCAT(' . $value . ') AS listValue');
		if (!empty($optGroup)) {
			$fields[] = 'CONCAT(' . $optGroup .') AS optGroup';
		}
		/**
		 TODO:
		 Make this a little more elegant for checking both Tree and SuperTree.
		 **/
		if (array_key_exists('Tree', $Model->actsAs) || array_key_exists('SuperTree', $Model->actsAs)) {
			if ($path = Param::keyValCheck($options, 'path')) {				$list += $this->generatePathList($Model, compact('fields', 'conditions', 'recursive', 'skipRoot'));
			} else {
				$list += $Model->generateTreeList($conditions, $keyPath, $valuePath, $spacer, $recursive);
			}
		} else {
			$result = $Model->find('all', compact('fields', 'conditions', 'recursive', 'order'));	
			foreach ($result as $row) {
				if (!empty($optGroup)) {
					$list[$row[0]['optGroup']][$row[0]['listKey']] = $row[0]['listValue'];
				} else {
					$list[$row[0]['listKey']] = $row[0]['listValue'];
				}
			}
		}
		//debug($list);
		return $list;
	}
	
	public function generatePathList(Model $Model, $options = array()) {
		$skipRoot = Param::keyValCheck($options, 'skipRoot', true);
		$options['fields'][] = '*';
		$result = $Model->find('threaded', $options);
//		debug($result);
		return $this->_buildpathlist($result, array(), '', $skipRoot);
	}
	
	private function _buildpathlist($result, $list = array(), $prefix = '', $skipRoot = false) {
		foreach ($result as $row) {
			$id = $row[0]['listKey'];
			$value = $row[0]['listValue'];

			$displayValue = empty($prefix) && $skipRoot ? '' : $prefix . $value;
			$list[$id] = $displayValue;
			
			if (!empty($row['children'])) {
				$list = $this->_buildpathlist($row['children'], $list, $displayValue . ' \ ');
			}
		}
		return $list;
	}
}

<?php
/**
 * Field Order Behavior
 * 
 * Enables a model to sort its fields based on a field in the table.
 * Optionally that order field can be sub-divided by other fields within the table.
 *
 **/
 
class FieldOrderBehavior extends ModelBehavior {
	public $name = 'FieldOrder';
	public $settings = array();

	private $_cacheId;
	private $_cacheConditions;
	
	public function setup(Model $Model, $settings=array()) {
		if(!is_array($settings) && !empty($settings)) {
			$settings = array(
				'subKeyFields' => array($settings)
			);
		}
		//Default settings
		$settings = array_merge(array(
			'orderField' => 'sub_order',
			'subKeyFields' => null
			), $settings
		);
		if (!empty($settings['subKeyFields']) && !is_array($settings['subKeyFields'])) {
			$settings['subKeyFields'] = array($settings['subKeyFields']);
		}
		if (empty($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = array();
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], $settings);
		$this->setModelOrder($Model);
	}
	
	public function setModelOrder(Model $Model) {
		$settings =& $this->settings[$Model->alias];
		if (count($settings['subKeyFields']) > 0) {
			$order = array();
			foreach ($settings['subKeyFields'] as $field) {
				$order[] = $Model->alias . '.' . $field;
			}
			$Model->order = $order;
		} else {
			$Model->order = array();
		}
		$Model->order[] = $Model->alias . '.' . $settings['orderField'];
	}
	
	/*
	function afterDelete(Model $Model) {
		if (is_numeric($Model->id)) {
			$this->reorder($Model,$Model->id);
		}
	}
	*/
	
	public function afterSave(Model $Model, $created, $options = array()) {
		if ($created) {
			$this->moveLast($Model, $Model->id);
		}
		return true;
	}
	
	public function beforeDelete(Model $Model, $cascade = true) {
		//Makes sure to move the element to end of the list before deleting
		$this->moveLast($Model, $Model->id);
		return true;
	}
		
	//Change order of element by reducing it's order
	public function moveUp(Model $Model, $id, $delta = 1) {
		return $this->adjustOrder($Model, $id, $delta * -1);
	}
	
	//Change order of element advancing it's order
	public function moveDown(Model $Model, $id, $delta = 1) {
		return $this->adjustOrder($Model, $id, $delta);
	}
	
	//Change order of element by moving it to the beginning of the list
	public function moveFirst(Model $Model, $id) {
		return $this->setOrder($Model, $id, 0);
	}
	
	//Change order of element by moving it to the end of the list
	public function moveLast(Model $Model, $id) {
		return $this->setOrder($Model, $id, 99999999);
	}
	
	//Changes the order of the element by a value relative to its current position on the list
	public function adjustOrder(Model $Model, $id, $delta = 1) {
		$settings =& $this->settings[$Model->alias];
		//Loads info on id
		$Model->create();
		$result = $Model->read(null, $id);
		if (empty($result)) {
			return false;
		}
		$order = $result[$Model->alias][$settings['orderField']];
		
		$newOrder = $order + $delta;
		
		return $this->setOrder($Model, $id, $newOrder);
	}
	
	//Changes the order of the element by an absolute value
	public function setOrder(Model $Model, $id = null, $newOrder = null) {
		$settings =& $this->settings[$Model->alias];
		$result = $this->_getPeers($Model, $id);
		return $this->_reorderResult($Model, $result, $id, $newOrder);
	}
	
	//Reorders a table on a specific field based on a set of conditions and order commands
	public function updateOrderField(Model $Model, $orderField, $conditions = array(), $order = array()) {
		if (empty($order)) {
			$order = array($orderField);
		}
		$result = $Model->find('all', compact('conditions', 'order') + array('recursive' => -1));
		return $this->_reorderResult($Model, $result, null, null, $orderField);
	}
	
	private function _reorderResult($Model, $result, $id = null, $newOrder = null, $orderField = null) {
		$data = array();
		$settings =& $this->settings[$Model->alias];
		if (empty($orderField)) {
			$orderField = $settings['orderField'];
		}
		
		if (!empty($id)) {
			$total = count($result);
			if ($newOrder < 1) {
				$newOrder = 1;
			} else if ($newOrder > $total) {
				$newOrder = $total;
			}
		}
		$count = 0;
		foreach ($result as $row) {
			$rowId = $row[$Model->alias][$Model->primaryKey];
			if ($id == $rowId) {
				$setCount = $newOrder;
			} else {
				$setCount = ++$count;
				if ($count == $newOrder) {
					$setCount = ++$count;
				}
			}
			$data[] = array(
				$Model->primaryKey => $rowId,
				$orderField => $setCount,
			);
		}
		$success = $Model->saveAll($data, array('callbacks' => false, 'validate' => false));
		$Model->read(null, $id);
		return $success;
	}

	private function _getPeers(Model $Model, $id) {
		$settings =& $this->settings[$Model->alias];
		$conditions = array();
		$order = array();
		if(is_array($settings['subKeyFields'])) {
			$result = $Model->read(null, $id);
			foreach($settings['subKeyFields'] as $field) {
				if (!empty($result[$Model->alias][$field])) {
					$conditions[$Model->escapeField($field)] = $result[$Model->alias][$field];
					$order[] = $Model->escapeField($field);
				}
			}
		}
		$order[] = $Model->escapeField($settings['orderField']);
		return $Model->find('all', compact('conditions', 'order'));
	}
}
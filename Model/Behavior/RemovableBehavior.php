<?php
/***********
 * Removable Behavior
 * Created on 9/12/2012
 * Used with SaveAll to allow for deleting of associated models
 *
 * Works two ways:
 * Pass "remove" as true and it will look for the primary key and delete it
 * Pass "remove_id" as the primary key and it will delete that one
 *
/***********/

class RemovableBehavior extends ModelBehavior {
	function beforeSave(&$Model, $options) {
		$data =& $Model->getData();
		$removeId = null;
		if (!empty($data['remove']) && !empty($data[$Model->primaryKey])) {
			$removeId = $data[$Model->primaryKey];
		} else if (!empty($data['remove_id'])) {
			$removeId = $data['remove_id'];
		}
		
		if (!empty($removeId)) {
			$Model->delete($removeId);
			$data = array();
		}
		return parent::beforeSave($Model, $options);		
	}
}

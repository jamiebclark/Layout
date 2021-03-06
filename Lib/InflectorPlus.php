<?php
App::uses('Inflector', 'Utility');

class InflectorPlus {
	public static function &getInstance() {
		static $instance = array();
		if (!$instance) {
			$instance[0] = new InflectorPlus();
		}
		return $instance[0];
	}

	
	//Returns a string in camelcase format with first character lowercase
	public static function varName($model, $plural = false) {
		if (ctype_lower(substr($model,0,1))) {
		//It's a table / controller
			$model = Inflector::camelize(Inflector::singularize($model));
		}
		$varName = strtolower(substr($model,0,1)) . substr($model, 1, strlen($model));
		if ($plural) {
			$varName = Inflector::pluralize($varName);
		}
		return $varName;
	}
	
	public static function varNameSingular($model) {
		$self = InflectorPlus::getInstance();
		return $self->varName($model, false);
	}
	
	public static function varNamePlural($model) {
		$self = InflectorPlus::getInstance();
		return $self->varName($model, true);
	}
	
	public static function foreignKey($model) {
		return Inflector::underscore($model) . '_id';
	}

	public static function humanize($model) {
		if (!ctype_lower(substr($model,0,1))) {
			$model = trim(preg_replace('/([A-Z])/', ' $1', $model));
		} else {
			$model = Inflector::humanize($model);
		}
		return $model;
	}
	
	public static function modelize($table) {
		return Inflector::singularize(Inflector::camelize($table));
	}
}
<?php

/**
 * Watches specific columns, if they are blank on add, do not save. If they are blank on edit, remove that entry
 *
 **/
class BlankDeleteBehavior extends ModelBehavior {
	public $name = 'BlankDelete';
	public $settings;
	
	private $confirmDelete = false;

	//In order to handle child models, BlankDelete must be attached to all models
	//This keeps track of which models actually have BlankDelete behavior settings
	private $hasBlankDelete = array();
	
	function setup(Model $Model, $settings = array()) {
		if (!empty($settings)) {
			$this->hasBlankDelete[$Model->alias] = true;
			if (!isset($this->settings[$Model->alias])) {
				$this->settings[$Model->alias] = array(
					//All columns must match to meet criteria
					'and' => $Model->displayField,
					//Any columns must match to meet criteria
					'or' => array()
				);
			}
			if (!is_array($settings)) {
				$settings = array('and' => $settings);
			} else if (!isset($settings['and']) && !isset($settings['or'])) {
				$settings = array('and' => $settings);
			}
			$this->settings[$Model->alias] = $settings;
		}
	}
	
	function beforeValidate(Model $Model ,$options = array()) {
		$Model->data = $this->checkBlankDelete($Model);
		return parent::beforeValidate($Model, $options);
	}
	
	function beforeSave(Model $Model, $options = array()) {
		$this->confirmDelete = false;
		$Model->data = $this->checkBlankDelete($Model);
		return parent::beforeSave($Model, $options);
	}
	
	function afterSave(Model $Model, $created, $options = array()) {
		if ($this->confirmDelete) {
			$Model->delete($Model->id);
		}
		return parent::afterSave($Model, $created, $options);
	}

/**
 * Checks if a model actually has BlankDelete settings
 **/
	function hasBlankDelete(Model $Model) {
		return !empty($this->hasBlankDelete[$Model->alias]);
	}
	
/** 
 * Scans data and looks for empty field values matching the BlankDelete settings for the Model
 *
 * @param Model $Model
 * @param Array $passedData The data array passed through the request
 *
 * @return Newly formatted data
 **/
	function checkBlankDelete(Model $Model, $passedData = null) {
		$isAssociated = true;
		if (empty($passedData)) {
			$passedData =& $Model->data;
			$isAssociated = false;
		}

		$isBlank = false;
		$data = array();
		
		//Looks for sub-models
		if (is_array($passedData) && !empty($passedData)) {
			foreach ($passedData as $key => $val) {
				if (is_array($val) && !is_numeric($key) && ctype_upper($key{0}) && $key != $Model->alias) {
					if (is_object($Model->{$key})) {
						$SubModel = $Model->{$key};
					} else {
						foreach (CakePlugin::loaded() as $plugin) {
							if ($SubModel = ClassRegistry::init("$plugin.$key", true)) {
								break;
							}
						}
					}

					if (isset($this->settings[$key])) {
						//Has Many
						if (isset($Model->hasMany[$SubModel->alias]) && isset($val[0])) {
							foreach ($val as $subKey => $subVal) {
								if (!($passedData[$key][$subKey] = $SubModel->checkBlankDelete($subVal))) {
									unset($passedData[$key][$subKey]);
								}
							}
							$passedData[$key] = array_values($passedData[$key]); //Re-numbers
						} else {
							$passedData[$key] = $SubModel->checkBlankDelete($val);
						}
						if (empty($passedData[$key])) {
							unset($passedData[$key]);
						}
					}
				}
			}
			if (!empty($passedData[$Model->alias])) {
				$data =& $passedData[$Model->alias];
			} else {
				$data =& $passedData;
			}
		}

		if ($Model->hasBlankDelete()) {
			$settings =& $this->settings[$Model->alias];
			
			if (!empty($settings['or'])) {
				if (!is_array($settings['or'])) {
					$settings['or'] = array($settings['or']);
				}
				foreach ($settings['or'] as $column) {
					if ($this->isFound($column, $data) === false) {
						$isBlank = true;
					}
				}
			}
			if (!empty($settings['and'])) {
				if (!is_array($settings['and'])) {
					$settings['and'] = array($settings['and']);
				}
				$andBlank = false;
				foreach ($settings['and'] as $column) {
					if ($this->isFound($column, $data) !== false) {
						$andBlank = false;
						break;
					} else {
						$andBlank = true;
					}
				}
				if ($andBlank) {
					$isBlank = true;
				}
			}
			
			if ($isBlank) {
				$this->confirmDelete = false;
				if (!empty($data['id'])) {
					$Model->delete($data['id']);
				}
				$Model->validationErrors = null;
				if (!empty($passedData)) {
					$passedData = array();
				} else {
					$passedData = array();
				}
			}
		}
		return $passedData;
	}
	
	private function isFound($field, $array) {
		if (!array_key_exists($field, $array)) {
			$return = null;
		} else {
			$return = !empty($array[$field]) && !$this->isBlank($array[$field]);
		}
		return $return;
	}

	private function isBlank($val) {
		// Value is type file
		if (is_array($val) && isset($val['tmp_name'])) {
			return empty($val['tmp_name']);
		}
		if (is_array($val)) {
			$val = implode('', $val);
		}
		$val = trim($val);
		$blankVals = array(
			null,
			'',
			false,
			'0000-00-00',
			'0000-00-00 00:00:00',
			'1999-11-30',
			'1999-11-30 00:00:00',
		);
		$isBlank = (array_search($val, $blankVals, true) !== false);
		return $isBlank;
	}
}
<?php
/**
 * FormLayout Component scans info created with the FormLayout Helper
 * 
 * 
 **/
 
class FormLayoutComponent extends Component {
	var $controller;
	
	public function startup(Controller $controller) {
		$this->controller = $controller;
		if (!isset($this->settings['autoParse']) || $this->settings['autoParse'] !== false) {
			$this->parse();
		}
		return true;
	}
	
	public function parse() {
		$this->parseData();
		$this->parseHabtmIds();		
	}
	
	public function parseData($passModel = null) {
		if(!empty($this->controller->request->data)) {
			$this->controller->request->data = $this->parseDataFunction(
				array('parseRemoveData','parseDateData'), 
				$this->controller->request->data,
				$passModel
			);
		}
	}
	
/**
 * Instead of forcing a data array form:
 *		OLD WAY: array('SubModel' => array('SubModel' => array(id1, id2, id3))), 
 * You can instead format your HABTM data the way you would hasMany: 
 * 		NEW WAY: array('SubModel' => array('id' => id1))
 * This allows the form input to automatically detect the default value based on a database find('all') call
 **/
	private function parseHabtmIds() {
		if (!empty($this->controller->request->data) && !empty($this->controller->modelClass)) {
			$data =& $this->controller->request->data;
			$Model =& $this->initModel($this->controller->modelClass);
			if (!empty($Model->hasAndBelongsToMany)) {
				foreach ($Model->hasAndBelongsToMany as $subModel => $subModelAttrs) {
					if (isset($data[$subModel])) {
						//$SubModel = $this->initModel($subModel);
						$SubModel =& $Model->{$subModel};

						$ids = array();
						if (!empty($data[$subModel][$subModel])) {
							$ids = $data[$subModel][$subModel];
							unset($data[$subModel][$subModel]);
						}
						if (!empty($data[$subModel])) {
							foreach ($data[$subModel] as $key => $val) {
								if (is_array($val)) {
									if (isset($val[$SubModel->primaryKey])) {
										$ids[] = $val[$SubModel->primaryKey];
									}
								} else {
									$ids[] = $val;
								}
							}
						}
						$data[$subModel] = !empty($ids) ? array($subModel => $ids) : array();	
					}
				}
			}
		}
	}
	
	private function &initModel($model) {
		$init = ClassRegistry::init($model, true);
		if (!$init) {
			$plugins = CakePlugin::loaded();
			foreach ($plugins as $plugin) {
				if ($init = ClassRegistry::init("$plugin.$model", true)) {
					return $init;
				}
			}
		}
		return $init;
	}
	
	
/**
 * Checks the data for fields 'remove_id' or 'remove' and deletes the corresponding ID
 *
 **/
	public function parseRemoveData ($modelData, $model) {
		if (!($Model = $this->initModel($model))) {
			return $modelData;
		}
		$primaryKey = $Model->primaryKey;
		
		$remove = false;
		$removeId = false;
		if (isset($modelData['remove_id'])) {
			$remove = true;
			$removeId = $modelData['remove_id'];
		} else if (!empty($modelData['remove'])) {
			$remove = true;
			if (isset($modelData[$primaryKey])) {
				$removeId = $modelData[$primaryKey];
			}
		}
		if (!empty($removeId)) {
			$Model->delete($removeId);
		}
		if ($remove) {
			return false;
		}
		return $modelData;
	}/** * Avoids CakePHP's automatic removal of arrays passed into database fields * CakePHP uses a deconstruct() function through its set() function which scans data, * and sets any complex data types not conforming to its own data fields (year,month,day,etc) * to null. This happens before beforeValidate can be called. This preempts that and accommodates * the array('date' => '4/12/2010', 'time' => '8:00pm') format used with FormLayout * * @param $val the  * @return Newly formatted data **/	public function parseDateData($modelData, $model) {
		foreach ($modelData as $key => $val) {
			if (is_array($val)) {				$dateVal = '';				if (isset($val['time']) || isset($val['date'])) {
					$format = '';					if (isset($val['date'])) {						$dateVal .= $val['date'];
						$format = 'Y-m-d';					}					if (isset($val['time'])) {						$dateVal .= (!empty($dateVal) ? ' ' : '') . $val['time'];
						$format .= ' H:i:s';					}					$modelData[$key] = !empty($dateVal) ? date($format, strtotime($dateVal)) : null;				}			}		}		return $modelData;	}	
/**
 * Finds model data within a data array and passes it to user-specified functions
 * 
 * @param string|array $fn Either a single function of an array of functions to pass the model data through
 * @param array $data The data array from the Controller request
 * @param string $passModel The model to check
 * @return array Updated data array
 **/
	private function parseDataFunction($fn, $passData, $passModel = false) {
		if (is_array($fn)) {
			foreach ($fn as $subFn) {
				$passData = $this->parseDataFunction($subFn, $passData, $passModel);
			}
			return $passData;
		}
		$data =& $passData;
		if (!empty($passModel)) {
			//return $this->parseDataFunction($fn, $passData, $passModel));
			$data = array($passModel => $data);
			/*
			$models = explode('.', $passModel);
			foreach ($models as $subModel) {
				debug($subModel);
				if (empty($data[$subModel]) || !is_array($data[$subModel])) {
					return $passData;
				}
				//$data =& $data[$subModel];
			}
			*/
		}
		foreach ($data as $model => $modelData) {
			if (is_array($modelData)) {
				/*
				if (is_numeric($model)) {
					if ($return = $this->parseDataFunction($fn, $modelData, $model)) {
						$data[$model] = $return;
					} else {
						unset($data[$model]);
					}
				} else 
				*/
				if (isset($modelData[0])) {	//hasMany values
					foreach ($modelData as $k => $subModelData) {
						$return = $this->parseDataFunction($fn, $subModelData, $model);
						if ($return) {
							$data[$model][$k] = $return;
						} else {
							unset($data[$model][$k]);
						}
					}
					$data[$model] = array_values($data[$model]);
				} else {
					$data[$model] = $this->{$fn}($modelData, $model);
				}
			}
		}
		//return $passData;
//		$return = $data;
		$return = !empty($passModel) ? $data[$passModel] : $data;
		return $return;
	}
}
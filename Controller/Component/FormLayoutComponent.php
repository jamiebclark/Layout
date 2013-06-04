<?php
/**
 * FormLayout Component scans info created with the FormLayout Helper
 * 
 * 
 **/
 
class FormLayoutComponent extends Component {
	var $controller;
	
	function startup(Controller $controller) {
		//debug($controller->request->data);
		$this->controller = $controller;
		$this->parseData();

		//debug($controller->request->data);		return true;
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
	
	private function initModel($model) {
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
						$format .= ' g:i:s';					}					$modelData[$key] = date($format, strtotime($dateVal));				}			}		}		return $modelData;	}	
/**
 * Finds model data within a data array and passes it to user-specified functions
 * 
 * @param string|array $fn Either a single function of an array of functions to pass the model data through
 * @param array $data The data array from the Controller request
 * @param string $passModel The model to check
 * @return array Updated data array
 **/
	private function parseDataFunction($fn, $data, $passModel = false) {
		if (is_array($fn)) {
			foreach ($fn as $subFn) {
				$data = $this->parseDataFunction($subFn, $data, $passModel);
			}
			return $data;
		}
		$data = !empty($passModel) ? array($passModel => $data) : $data;
		foreach ($data as $model => $modelData) {
			if (is_array($modelData)) {
				if (is_numeric($model)) {
					if ($return = $this->parseDataFunction($fn, $modelData, $model)) {
						$data[$model] = $return;
					} else {
						unset($data[$model]);
					}
				} else if (isset($modelData[0])) {
					foreach ($modelData as $k => $subModelData) {
						if ($return = $this->parseDataFunction($fn, $subModelData, $model)) {
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
		$return = !empty($passModel) ? $data[$passModel] : $data;
		return $return;
	}
}
<?php
/**
 * FormLayout Component scans info created with the FormLayout Helper
 * 
 * 
 **/
 
class FormLayoutComponent extends Component {
	
	function startup(Controller $controller) {
		if(!empty($controller->request->data)) {			$controller->request->data = $this->scanDataFunction(
				array('removableData','deconstructData'), 
				$controller->request->data
			);
		}		return true;
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
	
	function removableData ($modelData, $model) {
		$Model = $this->initModel($model);
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
		if (isset($removeId)) {
			$Model->delete($removeId);
		}
		if ($remove) {
			return false;
		}
		return $modelData;
	}/** * Avoids CakePHP's automatic removal of arrays passed into database fields * CakePHP uses a deconstruct() function through its set() function which scans data, * and sets any complex data types not conforming to its own data fields (year,month,day,etc) * to null. This happens before beforeValidate can be called. This preempts that and accommodates * the array('date' => '4/12/2010', 'time' => '8:00pm') format used with FormLayout * * @param $val the  * @return Newly formatted data **/	private function deconstructData($modelData, $model) {
		foreach ($modelData as $key => $val) {			if (is_array($val)) {				$dateVal = '';				if (isset($val['time']) || isset($val['date'])) {					if (isset($val['date'])) {						$dateVal .= $val['date'];					}					if (isset($val['time'])) {						$dateVal .= (!empty($dateVal) ? ' ' : '') . $val['time'];					}					$modelData[$key] = $dateVal;				}			}		}		return $modelData;	}	
	private function scanDataFunction($fn, $data, $passModel = false) {
		if (is_array($fn)) {
			foreach ($fn as $subFn) {
				$data = $this->scanDataFunction($subFn, $data, $passModel);
			}
			return $data;
		}
		$data = !empty($passModel) ? array($passModel => $data) : $data;
		foreach ($data as $model => $modelData) {
			if (is_array($modelData)) {
				if (is_numeric($model)) {
					if ($return = $this->scanDataFunction($fn, $modelData, $model)) {
						$data[$model] = $return;
					} else {
						unset($data[$model]);
					}
				} else if (isset($modelData[0])) {
					foreach ($modelData as $k => $subModelData) {
						if ($return = $this->scanDataFunction($fn, $subModelData, $model)) {
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
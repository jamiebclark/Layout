<?php
/**
 * Table Component
 *
 * Works to manipulate data that has been submitted with the Table Helper 
 *
 **/
class TableComponent extends Component {
	var $controller;
	var $components = array('Session');
	
	var $settings = array();
	
	function __construct(ComponentCollection $collection, $settings = array()) {
		$this->settings = $settings;
		parent::__construct($collection, $settings);
	}

	function initialize(&$controller) {
		$this->controller =& $controller;
		
		//debug($controller->data);
		//debug($controller->request->params);
		
		$this->setLimit();
		
		//$this->saveData();
		$this->setCheckbox();
	}
	
	function setLimit() {
		if (!empty($_GET['limit']) && is_numeric($_GET['limit'])) {
			$this->controller->paginate['limit'] = $_GET['limit'];
		}
	}
	
	//Looks for in-table form edits
	function saveData() {
		$result = null;
		if (!empty($this->controller->request->data['TableEdit'])) {
			$model = !empty($settings['model']) ? $settings['model'] : $this->controller->modelClass;
			
			$result = true;
			$successCount = 0;
			$errorCount = 0;
			
			foreach ($this->controller->request->data['TableEdit'] as $key => $data) {
				$this->controller->{$model}->create();
				if (!($success = $this->controller->{$model}->saveAll($data))) {
					$this->validationErrors['TableEdit'][$key] = $this->controller->{$model}->validationErrors;
					$errorCount++;
				} else {
					unset($this->controller->request->data['TableEdit'][$key]);
					$successCount++;
				}
				$result *= $success;
			}
			$flashType = 'success';
			$msg = 'Successfully updated ' . $this->getHumanModel($model, $errorCount || $successCount>1);

			if ($errorCount) {
				$flashType = 'error';
				$msg .= ". Could not update $errorCount " . $this->getHumanModel($model,$errorCount != 1);
			}
			$this->flash($msg, $flashType);
			
			if ($result) {
				$this->controller->redirect($this->controller->referer());
			}
		}
		return $result;
	}
	
	//Scans for passed checked info
	function setCheckbox() {
		$data = array();
		$model = $this->controller->modelClass;

		//debug($this->controller->request->data);
		if (
			isset($this->controller->request->data['with_checked']) &&
			!empty($this->controller->request->data['checked_action']) && 
			!empty($this->controller->request->data['table_checkbox'])
		) {
			$ids = array_values($this->controller->request->data['table_checkbox']);
			$action = $this->controller->request->data['checked_action'];
			$data =& $this->controller->request->data;
		} else if (
			!empty($_POST['with_checked']) && 
			!empty($_POST['table_checkbox']) &&
			!empty($_POST['checked_action'])
		) {
			$data =& $_POST;
			$ids = array_values($_POST['table_checkbox']);
			$count = 1;
			while (!empty($_POST[$count])) {
				$ids[] = $_POST[$count];
				$count++;
			}
			$action = $_POST['checked_action'];
		}
		$options = array();
		if (!empty($data['useModel'])) {
			$options['model'] = $data['useModel'];
		}
		if (!empty($ids) && !empty($action)) {
			$return = $this->withChecked($action, $ids, $options);
			if (!empty($return['message'])) {
				$this->flash($return['message'], $return['success']);
			}
			if (!empty($return['redirect'])) {
				if ($return['redirect'] === true) {
					$return['redirect'] = $this->controller->referer();
				}
				$this->controller->redirect($return['redirect']);
			}
			return true;
		}
		return false;
	}
	
	
	function withChecked($action, $ids, $options = array()) {
		$function = '_withChecked';
		$redirect = true;
		$message = false;
		if (method_exists($this->controller, $function)) {
			$options = $this->controller->{$function}($action, $ids);
		}
		$model = !empty($options['model']) ? $options['model'] : $this->controller->modelClass;
		if (empty($options['result'])) {
			if (!empty($this->controller->{$model})) {
				$Model =& $this->controller->{$model};
			} else {
				App::import('Model', $model);
				$Model =& new $model();
			}
			$verb = 'Set';
			if (empty($options['conditions'])) {
				$options['conditions'] = array(
					$model . '.id' => $ids,
				);
			}			
			if ($action == 'approve') {
				$options['verb'] = 'Approved';
				$options['updateAll'] = array($model . '.approved' => 1);
			} else if ($action == 'unapprove') {
				$options['verb'] = 'Unapproved';
				$options['updateAll'] = array($model . '.approved' => 0);
			} else if ($action == 'active') {
				$options['verb'] = 'Activated';
				$options['updateAll'] = array($model . '.active' => 1);
			} else if ($action == 'inactive') {
				$options['verb'] = 'Deactivated';
				$options['updateAll'] = array($model . '.active' => 0);
			} else if ($action == 'delete') {
				$options['delete'] = true;
			} else if ($action == 'duplicate') {
				$options['result'] = true;
				$options['redirect'] = array(
					'controller' => 'duplicates',
					'action' => 'view',
					'staff' => true,
					$model,
				);
				foreach ($ids as $id) {
					$options['redirect'][] = $id;
				}
			}
			if (!empty($options['delete'])) {
				if ($options['delete'] === true) {
					$options['delete'] = $options['conditions'];
				}
				$Model->order = array();
				$options['result'] = $Model->deleteAll($options['delete']);
				if (empty($options['verb'])) {
					$options['verb'] = 'Deleted';
				}
			} else if (!empty($options['updateAll'])) {
				$options['result'] = $Model->updateAll($options['updateAll'], $options['conditions']);
				if (empty($options['verb'])) {
					$options['verb'] = 'Updated';
				}
			}	
			$options['count'] = $Model->getAffectedRows();
		}
		$success = null;
		if (isset($options['result']) && (!isset($options['count']) || !empty($options['count']))) {
			$success = $options['result'] !== false;
		}
		if (!empty($options['message'])) {
			$message = $options['message'];
		} else {
			$message = (!empty($options['verb']) ? $options['verb'] : 'Adjusted');
			$count = isset($options['count']) ? $options['count'] : 0;
			$message .= " $count " . $this->getHumanModel($model, $count != 1);
		}
		if (!empty($options['redirect'])) {
			$redirect = $options['redirect'];
		}
		return compact('redirect', 'message', 'success');
	}
	
	private function flash($msg, $type = 'info') {
		if ($type === true) {
			$type = 'success';
		} else if ($type === false) {
			$type = 'error';
		} else if (empty($type)) {
			$type = 'info';
		}
		
		return $this->Session->setFlash(__($msg), 'default', array(
			'class' => 'alert alert-' . $type
		));
	}
	
	private function getHumanModel($model, $plural = false) {
		$table = Inflector::tableize($model);
		$human = Inflector::humanize($table);
		$humanSingle = Inflector::singularize($human);
		
		$humanModelPlural = Inflector::humanize(Inflector::tableize($model));
		return $plural ? $humanModelPlural : Inflector::singularize($humanModelPlural);
	}

}

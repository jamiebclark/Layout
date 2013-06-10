<?php
/**
 * Helper for use with displaying contacts
 *
 **/App::uses('LayoutAppHelper', 'Layout.View/Helper');
class AddressBookFormHelper extends LayoutAppHelper {
	var $name = 'AddressBookForm';
	var $helpers = array('Form', 'Layout.FormLayout');
	
	
	private $numericCount = 0;
	
	function beforeRender($viewFile) {
		$this->Asset->css('Layout.address_book_form');
		$this->Asset->js('Layout.address_book_form');	
		parent::beforeRender($viewFile);
	}
	
	function inputGender($fieldName, $options = array()) {
		return $this->Form->input($fieldName, array(
			'div' => 'input-gender',
			'type' => 'radio',
			'divControls' => 'input-multi-row',
			'options' => array(
				'' => '---',
				'M' => 'Male',
				'F' => 'Female',
			),
			'legend' => false,
		));
	}
	
	private function _numericField($fieldName, $isNumeric, $prefix = '') {
		return $prefix . (($isNumeric) ? $this->numericCount++ : $fieldName);
	}
	
	function inputAddress($model, $options = array()) {
		$options = array_merge(array(
			'prefix' => '',
			'numerical' => false,
			'numeric' => 0,			//If counting, the number to start counting on
			'cityStateLine' => true,
			'addline' => 2,
			'addressPrefix' => '', //Prefixes the address columns, for instance, mail_addline1
			'count' => null,
		), $options);
		extract($options);
		if (empty($prefix)) {
			$prefix = '';
			if (!empty($model)) {
				$prefix = $model . '.';
			}
		}
		if (!empty($numerical)) {
			$numeric = 0;
		}
		if (isset($count)) {
			$prefix .= $count . '.';
		}
		$addressPrefix = $prefix . $addressPrefix;
		
		$states = !empty($this->_View->viewVars['states']) ? $this->_View->viewVars['states'] : array();
		$countries = !empty($this->_View->viewVars['countries']) ? $this->_View->viewVars['countries'] : array();
		
		$out = '';
		$inputRows = array();
		//Being Output
		if (!empty($userId)) {
			$out .= $this->Form->hidden($this->_numericField('user_id', $numerical, $prefix), array('value' => $this->viewVars['loggedUserId']));
		}

		if (!empty($location)) {
			$inputRows[] = array(
				$this->_numericField('location', $numerical, $addressPrefix) => array('label' => 'Location')
			);
		}
		if (!empty($locationName)) {
			$inputRows[] = array(
				$this->_numericField('location_name', $numerical, $addressPrefix) => array('label' => 'Location')
			);
		}
		for ($i = 1; $i <= $addline; $i++) {
			$aPlaceholder = 'Address ' . $i;
			$aLabel = false;
			if ($i == 1) {
				$aLabel = 'Street Address';
				$aPlaceholder = null;
			}
			if ($addline == 2 && $i == 2) {
				$aPlaceholder = 'Apt. or Suite#';
			}
			$inputRows[] = array(	
				$addressPrefix . ($numerical ? $numeric++ : 'addline' . $i) => 
					array('type' => 'text', 'label' => $aLabel, 'placeholder' => $aPlaceholder)
			);
		}
		$inputRows[] = array(
			$this->_numericField('city', $numerical, $addressPrefix) => array(
				'label' => 'City',
				'type' => 'text',
			),
			$this->_numericField('state', $numerical, $addressPrefix) => array(
				'label' => 'State',
				'options' => $states,
			)
		);
		$inputRows[] = array(
			$this->_numericField('zip', $numerical, $addressPrefix) => array(
				'label' => 'Zip',
				'type' => 'text',
			),
			$this->_numericField('country', $numerical, $addressPrefix) => array(
				'default' => 'US', 
				'label' => 'County',
				'options' => $countries,
			),
		);
		$out .= $this->FormLayout->inputRows($inputRows, compact('fieldset', 'legend', 'span', 'placeholder'));	
		return $this->Html->div('input-address', $out);
	}
	
	function inputName($model = 'User', $inputs = array(), $options = array()) {
		$ns = 'input-name';
		$options = array_merge(array(
			'label' => 'Name',
			'count' => null,
		), $options);
		extract($options);
		if (empty($inputs)) {
			$inputs = array(
				'prefix' => array('required' => true, 'small' => true),
				'first_name' => array('required' => true),
				'nick_name' => array('label' => 'Nick Name'),
				'middle_name' => array('small' => true, 'label' => 'Middle'),
				'last_name' => array('required' => true,),
				'suffix' => array('small' => true),
			);
		}
		
		$pre = $model . '.';
		if (is_numeric($count)) {
			$pre .= $count . '.';
		}
		$hasLabel = !empty($label);
		$out = '';
		foreach ($inputs as $name => $options) {
			if (!is_array($options)) {
				$name = $options;
				$options = array();
			}
			if (!empty($options['div'])) {
				$div = $options['div'];
			} else {
				$div = 'input ' . (!empty($options['type']) ? $options['type'] : 'text');
			}
			if (Param::keyCheck($options, 'small', true)) {
				$div .= " $ns-small";
			}
			if (Param::keyCheck($options, 'default', true)) {
				$div .= ' default';
			}
			if (Param::keyCheck($options, 'required', true) || (isset($options['required']) && $options['required'] === true)) {
				$div .= ' required';
			}
			$options['div'] = $div;
			if (!empty($data[$name])) {
				$options['value'] = $data[$name];
			}
			$out .= $this->Form->input($pre . $name, $options);
		}
		$out = $this->Html->div("$ns-inner contain-label", $out);
		if ($hasLabel) {
			$label = $this->Html->tag('label', $label);
			$label = $this->Html->div("$ns-small", $this->Html->tag('label', '&nbsp;')) . $label;
			$label = $this->Html->div("$ns-label control-label", $label);
			$out = $label . $this->Html->div("$ns-content", $out);
		}
		return $this->Html->div($ns, $out);
	}
}
<?php
/**
 * Helper for use with displaying contacts
 *
 **/
App::uses('LayoutAppHelper', 'Layout.View/Helper');
class AddressBookFormHelper extends LayoutAppHelper {
	public $name = 'AddressBookForm';
	public $helpers = array(
		'Form' => array(
			'className' => 'TwitterBootstrap.BootstrapFormHelper',
		), 
		'Layout.Layout', 
		'Layout.FormLayout'
	);
	
	
	private $numericCount = 0;
	
	function beforeRender($viewFile) {
		//$this->Asset->css('Layout.address_book_form');
		$this->Asset->js('Layout.address_book_form');	
		parent::beforeRender($viewFile);
	}
	
	function inputGender($fieldName, $options = array()) {
		return $this->Form->input($fieldName, array(
			'div' => 'input-gender form-group',
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
	
	function inputAddress($model, $options = array(), $inputOptions = array()) {
		$options = array_merge(array(
			'prefix' => '',
			'numerical' => false,
			'numeric' => 0,			//If counting, the number to start counting on
			'cityStateLine' => true,
			'addline' => 2,
			'addressPrefix' => '', //Prefixes the address columns, for instance, mail_addline1
			'count' => null,
			'before' => '',
			'after' => '',
			'beforeFields' => null,
			'afterFields' => null,
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
		//Begin Output
		if (!empty($userId)) {
			$out .= $this->Form->hidden(
				$this->_numericField('user_id', $numerical, $prefix), 
				array('value' => $this->viewVars['loggedUserId'])
			);
		}

		//Certain fields can be added to before fields by passing them by name
		$checkBefore = array('location', 'locationName');
		foreach ($checkBefore as $field) {
			if (!empty($options[$field])) {
				$beforeFields[Inflector::underscore($field)] = $options[$field] === true ? array() : $options[$field];
			}
		}
		
		if (!empty($beforeFields)) {
			foreach ($beforeFields as $field => $config) {
				if (is_numeric($field)) {
					$field = $config;
					$config = array();
				}
				$inputRows[] = array(
					$this->_numericField($field, $numerical, $addressPrefix) =>  $this->_inputOptions($inputOptions, $field, $config)
				);
			}
		}
		
		for ($i = 1; $i <= $addline; $i++) {
			$aPlaceholder = 'Address ' . $i;
			$aLabel = false;
			if ($i == 1) {
				$aLabel = 'Street Address';
				$aPlaceholder = null;
				$offset = false;
			}
			if ($addline == 2 && $i == 2) {
				$aPlaceholder = 'Apt. or Suite#';
				$offset = true;
			}
			$inputRows[] = array(	
				$addressPrefix . ($numerical ? $numeric++ : "addline$i") =>  $this->_inputOptions($inputOptions, "addline$i", array(
					'type' => 'text', 
					'label' => $aLabel, 
					'placeholder' => $aPlaceholder,
					'div' => 'addressbookform-addline',
					'offset' => $offset,
				)
			));
		}
		$inputRows[] = array(
			$this->_numericField('city', $numerical, $addressPrefix) => $this->_inputOptions($inputOptions, 'city', array(
				'label' => 'City',
				'type' => 'text',
				'col-sm' => 8,
				'div' => 'addressbookform-city',
			)),
			$this->_numericField('state', $numerical, $addressPrefix) =>  $this->_inputOptions($inputOptions, 'state', array(
				'label' => 'State',
				'options' => $states,
				'div' => 'addressbookform-state',
			))
		);
		$inputRows[] = array(
			$this->_numericField('zip', $numerical, $addressPrefix) => array(
				'label' => 'Zip',
				'type' => 'text',
				'col-sm' => 8,
				'div' => 'addressbookform-zip',
			),
			$this->_numericField('country', $numerical, $addressPrefix) => array(
				'default' => 'US', 
				'label' => 'County',
				'options' => $countries,
				'div' => 'addressbookform-country',
			),
		);
		$out .= $this->FormLayout->inputRows($inputRows, 
			compact('fieldset', 'legend', 'placeholder') + $this->Layout->colSizes
		);
		$out = $before . $out . $after;
		return $this->Html->div('input-address', $out);
	}
	
	function inputName($model = 'User', $inputs = array(), $options = array()) {
		$ns = 'input-name';
		$options = array_merge(array(
			'label' => 'Name',
			'count' => null,
			'prefix' => null,
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
		if (!isset($prefix)) {
			$prefix = $model . '.';
			if (is_numeric($count)) {
				$prefix .= $count . '.';
			}
		}
		if (empty($prefix)) {
			$prefix = '';
		}
		$hasLabel = !empty($label);
		$out = '';
		
		$this->Form->pauseColWidth();
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
			if (empty($options['label'])) {
				$options['label'] = Inflector::humanize(str_replace('_name', '', $name));
			}
			$options = $this->addClass($options, 'input-lg');
			$out .= $this->Form->input($prefix . $name, $options);
		}
		$this->Form->pauseColWidth(false);
		
		$out = $this->Html->div("$ns-inner contain-label", $out);
		if ($hasLabel) {
			$label = $this->Html->tag('label', $label, $this->Form->addColWidthClass(
				array('class' => "$ns-label control-label"), 
				true
			));
			//$label = $this->Html->div("$ns-small", $this->Html->tag('label', '&nbsp;')) . $label;
			$out = $label . $this->Html->tag('div', 
				$out, 
				$this->Form->addColWidthClass(array('class' => "$ns-content"))
			);
		}
		return $this->Html->div($ns, $out);
	}
	
	private function _numericField($fieldName, $isNumeric, $prefix = '') {
		return $prefix . (($isNumeric) ? $this->numericCount++ : $fieldName);
	}

	/**
	 * Checks a keyed list of options for a specific key and returns the options associated with that key
	 * 
	 * @param Array $optionsList Full list of options
	 * @param String $key The key to check for options
	 * @param Array $existingOptions Existing options with which to merge any results found
	 *
	 * @return Array Complete options merged with existing
	 **/
	private function _inputOptions($optionsList, $key, $existingOptions = array()) {
		if (isset($optionsList[$key])) {
			$existingOptions = array_merge($optionsList[$key], (array) $existingOptions);
		}
		return $existingOptions;
	}

}
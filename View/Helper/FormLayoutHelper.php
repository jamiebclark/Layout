<?php
/**
 * Layout Helper outputs some basic Html objects that help form a better organized view
 *
 **/
App::uses('LayoutAppHelper', 'Layout.View/Helper');

class FormLayoutHelper extends LayoutAppHelper {
	public $name = 'FormLayout';
	public $helpers = array(
		'CakeAssets.Asset',
		'Html', 
		'Form', 
		'Layout.Layout', 
		'Layout.Iconic'
	);

	public $buttonIcons = array(
		'add' => 'plus',
		'update' => 'check',
		'cancel' => 'minus_alt',
		'next' => 'arrow_right',
		'prev' => 'arrow_left',
		'upload' => 'arrow_up',
	);

	public $toggleCount = 0;
	private $_inputCount = array();

	private $_hSpan;
	private $_inputDefaults = null;
	
	public function __construct($View, $settings = array()) {
		parent::__construct($View, $settings);

		//Adds new suffixes to account for inputDate and timeInput
		$this->Form->_fieldSuffixes = array_merge($this->Form->_fieldSuffixes, array('date', 'time'));
	}
	
	public function beforeRender($viewFile) {
		parent::beforeRender($viewFile);
		$this->Asset->js(array(
			'Layout.form_layout',
			'Layout.jquery/jquery.timepicker',
			'Layout.jquery/datepair',
		));
		//$this->Asset->css('Layout.layout');
	}
	
	public function newPassword($name, $options = array()) {
		$pw = $this->_randomString(10);
		$pwMsg = 'Use random password: <strong>' . $pw . '</strong>';
		$after = $this->Html->link(
			$pwMsg,
			array('#' => 'top'),
			array(
				'onclick' => "$(this).prev().attr('value', '$pw');return false;",
				'escape' => false,
				'class' => 'newPassword',
			)
		);
		$options = array_merge(array(
			'type' => 'password',
			'after' => $after,
		), $options);
		return $this->Form->input($name, $options);
	}
	
	//Our old version of input auto complete
	public function inputAutoCompleteOLD($name, $url, $options = array()) {
		if (is_array($url)) {
			$url = Router::url($url);
		}
		if (substr($url,-1) != '/') {
			$url .= '/';
		}
		$options = $this->addClass($options, 'input text inputAutoComplete', 'div');
		$options['type'] = 'text';
		$options['autocomplete'] = 'off';
		$options['multiple'] = false;
		$options['after'] = $this->Html->div('selectDropdownWrapper', $this->Html->div(
			'selectDropdown', 
			'',
			array(
				'url' => $url
			)
		));
		
		if (!empty($options['submit'])) {
			unset($options['submit']);
			$options['after'] .= $this->Form->button(
				$this->Iconic->icon('magnifying_glass'), //$this->Html->image('/img/icn/16x16/magnifier.png'), 
					array(
						'type' => 'submit',
						'div' => false,
					)
			);
		}
		
		return $this->Form->input($name, $options);
	}

	/**
	 * A creates a combination of form elements to allow for an auto complete that generates a checked list
	 * 
	 * @param string $model The model this is generating for
	 * @param string $url The URL where the autocomplete dropdown will be generated
	 * @param Array $attrs Optional additional attributes
	 *
	 **/
	public function inputAutoCompleteMulti($model, $url = null, $attrs = array()) {
		$attrs = array_merge(array(
			'label' => 'Search',
			'displayField' => 'title',
			'primaryKey' => 'id',
			'habtm' => false,
			'options' => array(),
			'selected' => array(),
			'select' => array(),
			'title' => null
		), $attrs);
		extract($attrs);
		$out = '';
		$out .= $this->inputAutoComplete("$model.search", $url, array(
			'label' => $label,
			'placeholder' => 'Type to begin searching',
		));
		$k = 0;

		if (!empty($this->request->data[$model])) {
			$data = $this->resultToList($this->request->data[$model], $model, $primaryKey, $displayField);
			$options = $data + $options;
			$selected = array_merge($selected, array_keys($data));
		}

		if (!empty($options)) {
			$out .= $this->Html->div('input-autocomplete-multi-values', 
				$this->Form->input("$model.$model", array(
					'multiple' => 'checkbox',
					'label' => false,
					'class' => 'checkbox',
				) + compact('options', 'selected'))
			);
			$k = count($options);
		} else {
			$out .= $this->Html->div('input-autocomplete-multi-values', '');
		}
		if (!empty($select)) {
			$out .= $this->Form->input("$model.$model.", array(
				'options' => array('' => ' --- ') + $select,
				'class' => 'input-autocomplete-multi-default-values',
			));
		}
		$objectOptions = array('data-name' => "data[$model][$model][]");
		$out = $this->Html->div('input-autocomplete-multi', $out, $objectOptions);
		if (!empty($title)) {
			$out = $this->Html->tag('h4', $title) . $out;
		}
		return $out;
		/*		
		$i = 0;
		$habtm = empty($field);
		$field = $habtm ? '' : ".$primaryKey";
		
		$modelName = $habtm ? "$model.$model." : "$model.";
		$fieldName = $habtm ? '' : ".$primaryKey";
	
		$checked = $this->resultToList($checked, $mode, $primaryKey, $displayFieldl);
		$unchecked = $this->resultToList($unchecked, $model, $primaryKey, $displayField);
		if (!empty($this->request->data[$model])) {
			$hasData = true;
			$checked += $this->resultToList($this->request->data[$model], $model, $primaryKey, $displayField);
		}
		if (!empty($default)) {
			if (!empty($hasData)) {
				$unchecked += $this->resultToList($default, $model, $primaryKey, $displayField);
			} else {
				$checked += $this->resultToList($default, $model, $primaryKey, $displayField);
			}
		}
		//if (!empty($suggested)) {
		$usedVals = array();
		$allVals = array($checked, $unchecked);

		debug($allVals);
		
		$valsOutput = '';
		foreach ($allVals as $unchecked => $vals) {
			foreach ($vals as $id => $title) {
				if (!empty($usedVals[$id])) {
					continue;
				}
				$usedVals[$id] = $id;
				$valsOutput .= $this->Html->tag('label',
					$this->Form->checkbox("$modelName$i$fieldName", array(
						'hiddenField' => false,
						'checked' => !$unchecked,
						'value' => $id,
					))
				. $title) . "\n";
				$i++;
			}
		}
		$valsOutput = $this->Html->div('vals', $valsOutput);
		if (!empty($options)) {
			$valsOutput .= $this->Form->input("$modelName$i$fieldName", array(
				'type' => 'select',
				'div' => false,
				'label' => false,
				'style' => 'display: none',
				'class' => 'default-vals',
				'options' => array('' => '---') + $this->resultToList($options, $model),
			));
			$i++;
		}
		$out = $this->Form->input("$modelName$i$fieldName", compact('url', 'label') + array(
			'type' => 'text',
			'div' => 'input text input-autocomplete-multi',
			'after' => $valsOutput,
		));
		return $this->Html->div('input-autocomplete-multi', $out);
		*/
	}
	
	/**
	 * Input Hidden
	 * Outputs a list of hidden form inputs
	 *
	 **/
	public function hidden($fields) {
		if (!is_array($fields)) {
			$fields = array($fields);
		}
		$out = '';
		foreach ($fields as $field) {
			$out .= $this->Form->hidden($field);
		}
		return $out;
	}
	
	public function inputAutoCompleteMultiOLD($model, $url = null, $attrs = array()) {
		$attrs = array_merge(array(
			'vals' => array(),
			'label' => 'Search',
			'displayField' => 'title',
			'primaryKey' => 'id',
			'habtm' => false,
			'checked' => array(),
			'unchecked' => array(),
			'default' => array(),
		), $attrs);
		extract($attrs);
		$i = 0;
		
		$habtm = empty($field);
		$field = $habtm ? '' : ".$primaryKey";
		
		$modelName = $habtm ? "$model.$model." : "$model.";
		$fieldName = $habtm ? '' : ".$primaryKey";
	
		$checked = $this->resultToList($checked, $model);
		$unchecked = $this->resultToList($unchecked, $model);
		if (!empty($this->request->data[$model])) {
			$hasData = true;
			$checked += $this->resultToList($this->request->data[$model], $model);
		}
		if (!empty($default)) {
			if (!empty($hasData)) {
				$unchecked += $this->resultToList($default, $model);
			} else {
				$checked += $this->resultToList($default, $model);
			}
		}
		//if (!empty($suggested)) {
		$usedVals = array();
		$allVals = array($checked, $unchecked);

		debug($allVals);
		
		$valsOutput = '';
		foreach ($allVals as $unchecked => $vals) {
			foreach ($vals as $id => $title) {
				if (!empty($usedVals[$id])) {
					continue;
				}
				$usedVals[$id] = $id;
				$valsOutput .= $this->Html->tag('label',
					$this->Form->checkbox("$modelName$i$fieldName", array(
						'hiddenField' => false,
						'checked' => !$unchecked,
						'value' => $id,
					))
				. $title) . "\n";
				$i++;
			}
		}
		$valsOutput = $this->Html->div('vals', $valsOutput);
		if (!empty($options)) {
			$valsOutput .= $this->Form->input("$modelName$i$fieldName", array(
				'type' => 'select',
				'div' => false,
				'label' => false,
				'style' => 'display: none',
				'class' => 'default-vals',
				'options' => array('' => '---') + $this->resultToList($options, $model),
			));
			$i++;
		}
		$out = $this->Form->input("$modelName$i$fieldName", compact('url', 'label') + array(
			'type' => 'text',
			'div' => 'input text input-autocomplete-multi',
			'after' => $valsOutput,
		));
		return $out;
	}
	
	public function inputAutoComplete($searchField = 'title', $url = null, $options = array()) {
		$custom = array(
			'action' => null,
			'idField' => null,
			'display' => false,
			'prefix' => '',
			'addDiv' => false,
			'displayInput' => null,
			'redirectUrl' => null,
			'searchField' => $searchField,
			'displayOptions' => array(),	// Array(ID => TITLE) of possible values
		);
		$options = array_merge(array(
			'label' => null,
			'div' => 'input-autocomplete form-group',
			'value' => null,
		), $custom, $options);
		extract($options);

		$hasValue = !empty($value);
		foreach ($custom as $key => $val) {
			unset($options[$key]);
		}
		
		$url = $this->cleanupUrl($url);
		
		if (!isset($prefix) || $prefix !== false) {
			$prefix = '';
			if (isset($model)) {
				$prefix .= $model . '.';
			}
			if (isset($count)) {
				$prefix .= $count . '.';
			}
		}
		
		$idField = $prefix . $idField;
		$searchField = $prefix . $searchField;
		if (!$hasValue) {
			if (!empty($idField) && $this->Html->value($idField) && !empty($displayOptions[$this->Html->value($idField)])) {
				$value = $displayOptions[$this->Html->value($idField)];
			} else if ($this->Html->value($searchField)) {
				$value = $this->Html->value($searchField);
			}
		}
		
		if (!empty($display) && empty($displayInput)) {
			//$displayInput = $this->Html->div('display fakeInput text', $hasValue ? $value : '', array('style'=> 'display:none;'));
			$displayInput = $this->fakeInput($hasValue ? $value : '', array(
				'class' => 'display text', 
				'style' => 'display: none', 
				'label' => false,
				'wrapInput' => false
			));
		}
		$idInput = !empty($idField) ? $this->Form->hidden($prefix . $idField) : '';

		$return = '';
		if (!empty($action)) {
			$options = $this->addClass($options, 'action-' . $action, 'div');
		}
		if (!empty($addDiv)) {
			$options = $this->addClass($options, $addDiv, 'div');
		}
		if (!empty($redirectUrl)) {
			$redirectUrl = is_array($redirectUrl) ? Router::url($redirectUrl) . '/' : $redirectUrl;
		}
		$options = array_merge($options, array(
			'type' => 'text',
			'before' => $idInput,
			'beforeInput' => $displayInput,
			'data-url' => $url,
			'data-redirect-url' => $redirectUrl,
		) + compact('value'));

		$return .= $this->input($searchField, $options);
		return $return;
	}
	
	public function inputs($inputs) {
		$out = '';
		foreach ($inputs as $fieldName => $inputOptions) {
			if (is_numeric($fieldName)) {
				$fieldName = $inputOptions;
				$inputOptions = array();
			}
			if ($fieldName == 'fieldset' || $fieldName == 'legend') {
				continue;
			}
			$out .= $this->input($fieldName, $inputOptions);
		}
		return $out;
	}
	
	private function _buttonInner($name, $options = array()) {
		if (empty($options) && is_array($name)) {
			list($name, $options) = $name;
		}
		$options = array_merge(array(
			'div' => false,
			'escape' => false,
			'type' => 'submit',
		), $options);
		$options = $this->addClass($options, 'btn btn-default');
		return $this->Form->button($name, $options);
	}
	
	public function input($fieldName, $options = array()) {
		$beforeInput = '';
		$afterInput = '';
		$options = array_merge(array('type' => null), $this->Form->_inputDefaults, (array)$options);

		/*
		//Removed for addition of Bootstrap
		$options = array_merge(array(
			'type' => 'text',
			'div' => 'input',
		), $options);
		$options = $this->addClass($options, $options['type'], 'div');
		*/

		if ($fieldName == 'password' && empty($options['type'])) {
			$options['type'] = 'password';
		}

		// Allows for custom types
		switch ($options['type']) {
			case 'id':
				$options['type'] = 'number';
				$typeTrack = 'id';				
				$options['prepend'] = '#';
			break;
			case 'cash': 
				$options['type'] = 'text';
				$typeTrack = 'cash';
				$options['prepend'] = '$';
				$options['placeholder'] = '0.00';
				$options['step'] = 'any';
			break;
			case 'date':
				$input = $this->inputDate($fieldName, $options);
			break;
			case 'datetime':
				$input = $this->inputDatetime($fieldName, $options);
			break;
			case 'time':
				$input = $this->inputTime($fieldName, $options);
			break;
			case 'float':
				$options['type'] = 'number';
				$typeTrack = 'number';
				$options['placeholder'] = '0.0';
				$options['step'] = 'any';
			break;
			case 'email' :
				$options['append'] = '<i class="fa fa-at"></i>';
			break;
			case 'tel':
			case 'phone':
				$options['append'] = '<i class="fa fa-phone"></i>';
				$options['type'] = 'tel';
			break;
			case 'url':
				$options['append'] = '<i class="fa fa-globe"></i>';
				$options['type'] = 'text';
			break;
			case 'number':
				$typeTrack = 'number';
			break;
			case 'password':
				$options['append'] = '<i class="fa fa-lock"></i>';
			break;
		}
		
		if (!empty($typeTrack)) {
			if (!empty($options['prepend']) || !empty($options['append'])) {
				$options = $this->addClass($options, "form-group-$typeTrack", 'div');
			}
			$options = $this->addClass($options, "input-group-$typeTrack");
		}
		
		if ($search = Param::keyCheck($options, 'search', true)) {
			if (!isset($options['form'])) {
				$options['form'] = true;
			}
			
			if (!isset($options['submit'])) {
				$options['submit'] = array(
					$this->Iconic->icon('magnifying_glass'), 
					array(
						'div' => false,
						'type' => 'search',
					)
				);
			}
			$options = $this->addClass($options, 'search-input', 'div');
		}
		
		if ($submit = Param::keyCheck($options, 'submit', true)) {
			$options['inputAppend'] = $submit;
			/*
			$default = array('div' => false, null);
			if (is_array($submit)) {
				if (!isset($submit[1])) {
					$submit[1] = $default;
				} else {
					$submit[1] = array_merge($default, $submit[1]);
				}
			} else {
				$submit = array($submit, $default);
			}
			
			if (empty($options['before'])) {
				$options['before'] = '';
			}
			if (empty($options['after'])) {
				$options['after'] = '';
			}
			$options['after'] .= $this->submit($submit[0], $submit[1]);
			$options = $this->addClass($options, 'contain-button', 'div');
			*/
		}
		
		if ($form = Param::keyCheck($options, 'form', true)) {
			list($formName, $formOptions) = array(null, array());
			if (is_array($form)) {
				$formOptions = $form;
			} else {
				$formName = $form;
			}
			$beforeInput = $this->Form->create($formName === true ? null : $formName, $formOptions);
			$afterInput = $this->Form->end();
		}
	
		if (empty($input)) {
			$input = $this->Form->input($fieldName, $this->inputOptions($options));
		}
		
		return $beforeInput . $input . $afterInput;
	}

	public function inputOptions($options = array()) {
		if ($inputAppend = Param::keyCheck($options, 'inputAppend', true)) {
			$options = $this->_appendOption($options, 'appendButton', $this->_buttonInner($inputAppend));
		}

		if ($appendButton = Param::keyCheck($options, 'appendButton', true)) {
			$options = $this->_appendOption($options, 'beforeInput', '<div class="input-group">');
			$options = $this->_appendOption($options, 'afterInput', '<span class="input-group-btn">' . $appendButton . '</span></div>', true);
		}
		if ($prependButton = Param::keyCheck($options, 'prependButton', true)) {
			$options = $this->_appendOption($options, 'beforeInput', '<div class="input-group"><span class="input-group-btn">' . $prependButton . '</span>');
			$options = $this->_appendOption($options, 'afterInput', '</div>', true);
		}

		if ($prepend = Param::keyCheck($options, 'prepend', true)) {
			$options = $this->_appendOption($options, 'beforeInput', '<div class="input-group"><span class="input-group-addon">' . $prepend . '</span>');
			$options = $this->_appendOption($options, 'afterInput', '</div>', true);
		}
		if ($append = Param::keyCheck($options, 'append', true)) {
			$options = $this->_appendOption($options, 'beforeInput', '<div class="input-group">');
			$options = $this->_appendOption($options, 'afterInput', '<span class="input-group-addon">' . $append . '</span></div>', true);
		}


		return $options;
	}

/**
 * Appends a value to an option key
 * 
 * @param array $options The existing options
 * @param string $prop The property key
 * @param string $value The value to append
 * @param bool $prepend If true it will prepend the value instead of appending
 * @return array The newly formatted options
 **/
	public function _appendOption($options = array(), $prop, $value, $prepend = false) {
		if (!isset($options[$prop])) {
			$options[$prop] = '';
		}
		if ($prepend) {
			$options[$prop] = $value . $options[$prop];
		} else {
			$options[$prop] .= $value;
		}
		return $options;
	}

	public function inputAutoCompleteSelect($searchField = 'title', $idField = 'id', $url = null, $options = array()) {
		$options = array_merge(array(
			'display' => true,
			'value' => '',
		) + compact('idField', 'searchField'),$options);
		
		extract($options);
		
		return $this->inputAutoComplete($searchField, $url, $options);
		/*
		if (!isset($prefix)) {
			$prefix = '';
			if (isset($model)) {
				$prefix .= $model . '.';
			}
			if (isset($count)) {
				$prefix .= $count . '.';
			}
		}
		
		$return = '';
		$class = 'input-autocomplete';
		if (!empty($action)) {
			$class .= ' action-' . $action;
		}
		
		$return .= $this->Html->div($class, null, compact('url'));
		$return .= $this->Form->hidden($prefix . $idField);
		$return .= $this->Form->input($prefix . $searchField, array(
			'type' => 'text',
			'between' => $this->Html->div('display fakeInput text', empty($value) ? '' : $value),
		) + compact('label', 'value'));
		$return .= "</div>\n";
		return $return;
		*/
	}

	//Creates an input not used for submitting, but for highlighting and copying the text inside
	public function inputCopy($value, $options = array()) {
		$return = '';
		
		$showForm = Param::keyCheck($options, 'form', true, true);
		$name = Param::keyCheck($options, 'name', true, 'copy_input');
		
		$options = array_merge(array(
			'type' => 'text',
			'value' => $value,
			'label' => false,
			'onclick' => 'this.select()',
			'readonly' => 'readonly',
		), $options);
		$options = $this->addClass($options, 'form-control input-copy');
		
		if ($showForm) {
			$return .= $this->Form->create();
		}
		$return .= $this->Form->input($name, $options);
		if ($showForm) {
			$return .= $this->Form->end();
		}
		
		return $return;
	}
	
	public function button($text, $attrs = array()) {
		$tagAttrs = array();
		$attrs = array_merge(array(
			'class' => 'button',
			'imgPosition' => 'before',
			'div' => false,
		), $attrs);
		
		if ($align = Param::keyCheck($attrs, 'align', true)) {
			$attrs = $this->addClass($attrs, 'align-' . $align);
		}

		$type = Param::keyCheck($attrs, 'type', true);
		if (empty($type) && !empty($text) && !is_array($text)) {
			$words = explode(' ', strip_tags($text));
			if (!empty($words)) {
				$type = strtolower($words[0]);
			}
		}
		if (!empty($type)) {
			$attrs = $this->addClass($attrs, $type);
		}
		if (!empty($attrs['tagAttrs'])) {
			$tagAttrs = array_merge($tagAttrs, $attrs['tagAttrs']);
		}
		if ($align = Param::keyCheck($attrs, 'align', true)) {
			$tagAttrs += compact('align');
		}
		//Adds image using img option
		$img = Param::keyCheck($attrs, 'img', true);
		if (!isset($img)) {
			$class = Param::keyCheck($attrs, 'class');
		}
		if (!empty($img)) {
			if ($attrs['imgPosition'] == 'after') {
				$text .= ' ' . $this->Html->image($img);
			} else {
				$text = $this->Html->image($img) . ' ' . $text;
			}
			$attrs['escape'] = false;
		}
		unset($attrs['imgPosition']);
		$text = $this->buttonIcon($type) . $text;
		
		$attrs = $this->addClass($attrs, 'btn btn-default');
		if ($url = Param::keyCheck($attrs, 'url', true)) {
			$button = $this->Html->link($text, $url, $attrs);
		} else {
			$button = $this->Form->button($text, $attrs);
		}
		return $button;
	}

	public function buttons($buttons = array(), $attrs = array()) {
		$out = '';
		$buttonCount = 0;
		$attrs = array_merge(array(
			'align' => 'left',
		), $attrs);
		$secondary = Param::keyCheck($attrs, 'secondary', true);
		foreach ($buttons as $buttonText => $buttonAttrs) {
			if (is_numeric($buttonText)) {
				if (!is_array($buttonAttrs)) {
					if (preg_match('/[^a-zA-Z 0-9]+/', $buttonAttrs)) {
						$out .= $buttonAttrs;
						continue;
					} else {
						list($buttonText, $buttonAttrs) = array($buttonAttrs, array());
					}
				} else {
					list($buttonText, $buttonAttrs) = $buttonAttrs + array(null, array());
				}
			} else if (!is_array($buttonAttrs)) {
				$buttonAttrs = array('type' => $buttonAttrs);
			}
			$buttonAttrs = array('tag' => false) + $buttonAttrs;
			if ($secondary && ++$buttonCount > 1) {
				$buttonAttrs = $this->addClass($buttonAttrs, 'secondary');
			}
			$out .= $this->button($buttonText, $buttonAttrs);
		}
		return $this->Html->div('form-actions', $out);
	}
	
	public function submit($text = null, $attrs = array()) {
		$return = '';
		if (is_array($text)) {
			$return .= $this->buttons($text, $attrs);
		} else {
			if ($text === false) {
				$text = '';
			} else if (!isset($text)) {
				$text = 'Submit';
			}
			$attrs = $this->addClass($attrs, 'submit');
			$return .= $this->button($text, $attrs);
		}
		return $return;
	}

	public function submitPrimary($text = null, $attrs = array()) {
		$attrs = $this->addClass($attrs, 'btn-primary btn-lg');
		return $this->Html->div('form-actions', $this->submit($text, $attrs));
	}
	
	public function buttonWrapper($return, $attrs = array(), $tagAttrs = array()) {
		if (($div = Param::keyCheck($attrs, 'div', true)) !== null) {
			if (!$div) {
				return $return;
			}
			$attrs['tag'] = 'div';
			if ($div !== true) {
				$tagAttrs = $this->addClass($tagAttrs, $div);
			}
		}			
		if (!Param::falseCheck($attrs, 'tag')) {
			$tag = Param::keyCheck($attrs, 'tag', true, 'div');
			if (!Param::falseCheck($attrs, 'clear', true)) {
				$tagAttrs = $this->addClass($tagAttrs, 'clearfix');
			}
			$return = $this->Html->tag($tag, $return, $this->buttonsAttrs($tagAttrs));
		}
		if (!empty($attrs['end'])) {
			$return .= $this->Form->end();
		}
		return $return;
	}
	
	public function buttonsAttrs($attrs = array()) {
		$attrs = $this->addClass($attrs, 'layout-buttons');
		if ($align = Param::keyCheck($attrs, 'align', true)) {
			if ($align == 'left') {
				$attrs = $this->addClass($attrs, 'align-left');
			} else if ($align == 'right') {
				$attrs = $this->addClass($attrs, 'align-right');
			}
		}
		return $attrs;
	}
	
	public function buttonIcon($type) {
		if (!empty($this->buttonIcons[$type])) {
			return $this->Iconic->icon($this->buttonIcons[$type]);
		} else {
			return '';
		}
	}
	
	/*
	
	$layout-buttons = array(
		0 => 'Submit',
		'Add Photo' => 'Upload',
		'Add' => array(
			'type' => 'add',
			'class' => 'secondary',
		)
		//The Old Way:
		4 => array(
			'Submit', 
			array(
				'type' => 'submit',
				'class' => 'secondary',
			)
		)
	);
	
	*/
	public function inputList($listContent = '', $options = array()) {
		$options = array_merge(array(
			'model' => InflectorPlus::modelize($this->request->params['controller']),
			'count' => 1,
			'type' => 'element',
			'tag' => 'div',
			'titleTag' => 'h4',
			'class' => '',
			'pass' => array(),
			'addBlank' => 1,
			'countStart' => 0,
		), $options);
		$options = $this->addClass($options, 'input-list');
		extract($options);
		$out = '';

		$listOptions = compact('class');
		if (!empty($removeCommand)) {
			$listOptions['data-input-list-remove-command'] = $removeCommand;
		}

		if (is_callable($listContent)) {
			$type = 'function';
		} else if (is_array($listContent)) {
			$total = count($listContent);
			$type = 'array';
		}
		if (empty($total)) {
			if ($data = $this->getModelData($model)) {
				$total = count($data);
				if (!empty($addBlank)) {
					$total += 1; //Adds an extra blank one
				}
			} else {
				$total = $count;
			}
		}
		
		if ($total < 0) {
			return $out;
		}
		for ($count = $countStart; $count < $countStart + $total; $count++) {
			$row = '';
			if ($type == 'function') {
				$row .= $listContent($count);
			} else if ($type == 'array') {
				$row .= $listContent[$count];
			} else if ($type == 'element') {
				$row .= $this->_View->element($listContent, compact('count') + $pass);
			} else if ($type == 'eval') {
				eval('$row .= ' . $listContent . ';');
			}
			$out .= $this->Html->div('input-list-item', $row);
		}
		$out  = $this->Html->div('input-list-inner', $out);
		if (!empty($titleTag) && !empty($title)) {
			$out = $this->Html->tag($titleTag, $title, array('class' => 'input-list-title')) . $out;
		}
		$out .= $this->Html->div('input-list-control', '');
		$out = $this->Html->tag($tag, $out, $listOptions);
		
		if (!empty($legend)) {
			$out = $this->Html->tag('legend', $legend) . $out;
			$out = $this->Html->tag('fieldset', $out);
		}
		
		return $out;
	}
	
	
	//Keeps track of re-using helper input elements, adding a counter to prevent two inputs with the same name
	private function inputNameCount($name) {
		if (empty($this->_inputCount[$name])) {
			$this->_inputCount[$name] = 0;
		}
		return $name . ($this->_inputCount[$name]++);
	}
	
	function addressInput($options = array()) {
		$model = Param::keyCheck($options, 'model', true);
		$prefix = '';
		if (!empty($model)) {
			$prefix = "$model.";
		}
		$out = $this->inputRows(array(
			array($prefix . 'addline1' => array('label' => 'Street Address')),
			array($prefix . 'addline2' => array('label' => 'Apt. #')),
			array($prefix . 'city', $prefix . 'state', $prefix . 'zip'),
			array($prefix . 'country' => array('default' => 'US'))
		), $options);
		return $out;
	}
	
	function inputRows($rows, $options = array()) {
		$out = '';
		foreach ($rows as $row) {
			if ($found = $this->_getColSize($row, null)) {
				break;
			}
		}
		if (empty($found)) {
			$found = 'col-md';
		}
		
		foreach ($rows as $row) {
			$out .= $this->inputRow($row, $options);
		}
		if (!empty($options['before'])) {
			$out = $options['before'] . $out;
		}
		if (!empty($options['after'])) {
			$out .= $options['after'];
		}
		return $out;
	}
	
	private function _getColSize($row, $default = 'col-md') {
		$found = $default;
		foreach ($row as $fieldName => $inputOptions) {
			if (is_numeric($fieldName)) {
				$fieldName = $inputOptions;
				$inputOptions = array();
			}
			foreach ($this->Layout->colSizes as $sizeKey) {
				if (isset($inputOptions[$sizeKey])) {
					$found = $sizeKey;
					break 2;
				}
			}
		}
		return $found;	
	}
	
	function inputRow($row, $options = array()) {
		$options = array_merge(array(
			'placeholder' => false,
			'label' => true,
		), $options);
		extract($options);
		
		$colTotal = 12;
		$inputs = array('fieldset' => false);
		
		$rowTotal = count($row);
		$rowCount = 0;

		$colCount = array();
		
		$found = $this->_getColSize($row);
		$colCount[$found] = 0;
		
		foreach ($row as $fieldName => $inputOptions) {
			if (is_numeric($fieldName)) {
				$fieldName = $inputOptions;
				$inputOptions = array();
			}
			$colClass = 'form-group';

			foreach (array('col', 'span') as $key) {
				if (isset($inputOptions[$key])) {
					$inputOptions['col-md'] = $inputOptions[$key];
					unset($inputOptions[$key]);
				}
			}
			
			foreach ($this->Layout->colSizes as $sizeKey) {
				if (isset($inputOptions[$sizeKey])) {
					$col = $inputOptions[$sizeKey];
					unset($inputOptions[$sizeKey]);
				} else if (isset($colCount[$sizeKey]) && $colCount[$sizeKey] < $colTotal) {
					$col = floor(($colTotal - $colCount[$sizeKey]) / ($rowTotal - $rowCount));
				} else {
					$col = false;
				}
				if ($col !== false) {
					if (!isset($colCount[$sizeKey])) {
						$colCount[$sizeKey] = 0;
					}
					$colCount[$sizeKey] += $col;
					$colClass .= sprintf(' %s-%d', $sizeKey, $col);
				}
			}
			$inputOptions = $this->addClass($inputOptions, $colClass, 'div');
			
			if ($placeholder) {
				if (!empty($inputOptions['label'])) {
					$inputOptions['placeholder'] = $inputOptions['label'];
				} else {
					$inputOptions['placeholder'] = $this->getLabelText($fieldName, $inputOptions);
				}
				$inputOptions['label'] = false;
			}
			if ($label === false) {
				$inputOptions['label'] = false;
			}
			$inputs[$fieldName] = $inputOptions;
			$rowCount++;
		}
		
		return $this->Html->div('row', $this->inputs($inputs));
		//return $this->Html->div('controls controls-row', $this->Form->inputs($inputs));
	}
	
	function inputChoices($inputs, $options = array()) {
		$options = array_merge(array(
			'name' => $this->inputNameCount('input_choice'),
		), $options);
		if (!isset($options['default'])) {
			$options['default'] = isset($options['values'][0]) ? $options['values'][0] : 0;
		}
		$options = $this->addClass($options, 'input-choices');
		extract($options);

		$this->Form->setEntity($name);
		$passVal = $this->Html->value($this->Form->_entityPath);
		if ($passVal !== null && $passVal !== false) {
			$default = $this->Html->value($this->Form->_entityPath);
		}
		
		$return = "\n";
		$count = 0;
		foreach ($inputs as $label => $input) {
			//$input = '<input name="input_choice" value="'. $count . '"';
			$radioValue = isset($options['values'][$count]) ? $options['values'][$count] : $count;
			$isDefault = $radioValue == $default || (empty($default) && $count == 0);
			$radio = $this->Form->radio($name, array($radioValue => $label), array(
				'label' => false,
				'legend' => false,
				'value' => $default,
				'data-clone-numbered-default' => $isDefault ? $default : null,
			));
			$row = $this->Html->div('input-choice-control', '<label>' . $radio . '</label>') . "\n";
			if (is_array($input)) {
				if (empty($input['fieldset']) && empty($input['legend'])) {
					$input['fieldset'] = false;
				}
				$input = $this->inputs($input);
			} else if (is_callable($input)) {
				$input = $input();
			}
			
			$row .= $this->Html->div('input-choice-content', $input, array(
				'style' => empty($isDefault) ? 'display:none;' : null,
			)) . "\n";
			$return .= $this->Html->div('input-choice', $row);
			$count++;
		}
		$return = $this->Html->div($class, $return) . "\n";
		
		if (!empty($legend)) {
			$return = $this->Layout->fieldset($legend, $return);
		}
		return $return;
	}
	
	
	function end($text = null, $attrs = array()) {
		return $this->submitPrimary($text, $attrs) . $this->Form->end();
	}

	/**
	 * Takes HABTM value and displays it as a series of select menus
	 * @param $model : The name of the HABTM model
	 * @param $options : The options to choose from
	 * @param $count : The amount of blank values to offer
	 *
	 **/
	function selectHabtm($model, $options = array()) {
		$options = array_merge(array(
			'blank' => 5,
			'options' => null,
			'label' => Inflector::humanize($model),
		), $options);
		extract($options);
		$count = $blank;
		if ($this->Html->value($model)) {
			$count += count($this->Html->value($model));
		}
		$type = !empty($options) ? 'select' : 'text';
		$out = '';
		for ($i = 0; $i < $count; $i++) {
			$attrs = compact('type', 'options');
			$fieldName = $model . '.' . $i;
			$attrs['label'] = !empty($label) ? $label . ' ' . ($i + 1) : false;
			$out .= $this->Form->input($fieldName, $attrs);
		}
		return $this->Html->div('select-habtm', $out);	
	}
	
	private function _inputDateAllDay($inputFieldName, $default = false) {
		$prefix = '';
		$name = explode('.', $inputFieldName);
		if (count($name) > 1) {
			array_pop($name);
			$prefix = implode('.', $name) . '.';
		}	

		$input = $this->input($prefix . 'all_day', compact('default') + array(
			'class' => 'input-date-all-day',
			'type' => 'checkbox',
			'wrapInput' => false,
			'div' => false,
			'label' => false,
		));
		return sprintf('<div class="%s"><label class="checkbox">%s%s</label></div>', 
			'control-date-all-day', 
			$input, 
			'All Day'
		);
	}
	
	public function inputDatetimePair($startFieldName, $endFieldName, $options = array()) {
		if (empty($options['label']) && !Param::falseCheck($options, 'label')) {
			$options['label'] = $this->getLabelText($startFieldName);
		}
		if (!empty($options['allDay']) || !empty($options['isAllDay'])) {
			$afterInput = $this->_inputDateAllDay($startFieldName, !empty($options['isAllDay']));
			$options['allDay'] = false;
			$options['isAllDay'] = false;
		} else {
			$afterInput = '';
		}
		$options['div'] = false;
		$out  = $this->inputDatetime($startFieldName, $this->getPairOptions('first', array('label' => 'From', 'inner' => true) + $options));
		//$out .= $this->Html->div('datepair-between', ' - ');
		$out .= $this->inputDatetime($endFieldName, $this->getPairOptions('second', array('flip' => true, 'label' => 'To', 'inner' => true) + $options));
		
		return $this->fakeInput($this->Html->div('input-datepair-control', $out), array(
			'div' => 'form-group datepair datepair-time',
			'label' => $options['label'],
			'editable' => true,
			'class' => false,

		) + compact('afterInput'));
	}

/**
 * A form input that includes a calendar dropdown when clicked on
 * If class is "datetime", then it also includes a separate time input
 * @param string $fieldName The form field name
 * @param array $options Standard options for the form item
 *
 **/
	public function inputDatePair($startFieldName, $endFieldName, $options = array()) {
		if (!isset($options['label'])) {
			$options['label'] = $this->getLabelText($startFieldName);
		}

		$this->pauseHSpan();
		$out  = $this->inputDate($startFieldName, $this->getPairOptions('first', array('label' => 'From', 'inner' => true) + $options));
		//$out .= $this->Html->div('datepair-between', ' - ');
		$out .= $this->inputDate($endFieldName, $this->getPairOptions('second', array('label' => 'To', 'inner' => true) + $options));
		$this->resumeHSpan();

		
		$return = $this->fakeInput($this->Html->div('input-datepair-control', $out), array(
			'div' => 'datepair form-group',
			'label' => $options['label'],
			'class' => false,
		));		
		return $return;
	}


/**
 * If passing options regarding a pair of inputs, it can also accept an option key for "first" or "second"
 * to indicate just one of the inputs in the pair. It also then removes both first and second options
 *
 * @param String $pairKey "first" or "second"
 * @param Array $options Additional options
 *
 * @return New options
 **/
	private function getPairOptions($pairKey, $options = array()) {
		if (isset($options[$pairKey])) {
			$options = array_merge($options, (array) $options[$pairKey]);
		}
		unset($options['first']);
		unset($options['second']);
		return $options;
	}
	
	private function getDateFieldValue($fieldName, $type = 'date', $value = null) {
		if (empty($value)) {
			$value = $this->getFieldValue($fieldName, $type);
		}
		if (!empty($value)) {
			if (is_array($value)) {
				$value = implode(' ', $value);
			}					
			if ($type == 'date') {
				if (!is_numeric($value)) {
					$value = strtotime($value);
				}
				$value = date('m/d/Y', $value);
			}
		}
		return $value;
	}
	
	private function getFieldValue($fieldName, $suffix = null) {
		if (!empty($suffix)) {
			$fieldName = $this->stripFieldSuffix($fieldName, $suffix);
		}
		if (strpos($fieldName, '.') === false) {
			$fieldName = $this->getCurrentModel() . '.' . $fieldName;
		}
		return $this->Html->value($fieldName);
	}
	
	private function stripFieldSuffix($fieldName, $suffix) {
		if (preg_match('/\.'.$suffix.'$/', $fieldName)) {
			$fieldName = substr($fieldName, 0, -1 * (strlen($suffix) + 1));
		}
		return $fieldName;
	}
	
	public function inputDate($fieldName, $options = array()) {
		$options = array_merge(array(
				'placeholder' => 'mm/dd/yyyy',
				'default' => null,
				//'beforeInput' => '<div class="input-group"><span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>','afterInput' => '</div>',
				'control' => array('today', 'clear'),
			), $this->addClass($options, 'form-control date datepicker'));
		if (!isset($options['div']) || $options['div'] !== false) {
			$options = $this->addClass($options, 'form-group input-date', 'div');
		}
		if ($dataValue = $this->getDateFieldValue($fieldName)) {
			$options['default'] = $dataValue;
		}
		if (Param::keyCheck($options, 'inner', true)) {
			$options['wrapInput'] = false;
		}

		$options['type'] = 'text';
		if (!empty($options['value'])) {
			$options['value'] = $this->getDateFieldValue($fieldName, 'date', $options['value']);
		}
		if ($control = Param::keyCheck($options, 'control', true)) {
			$control = array_flip($control);
			$appendButton = '';
			if (isset($control['today'])) {
				$appendButton .= $this->Html->link($this->Iconic->icon('arrow_down_alt1'),	'#', array(
					'class' => 'input-date-today btn btn-default',
					'title' => 'Today',
					'escape' => false,
					'tabIndex' => -1,					
				));
			}
			if (isset($control['clear'])) {
				$appendButton .= $this->Html->link($this->Iconic->icon('x_alt'), '#', array(
					'class' => 'input-date-clear btn btn-default',
					'title' => 'Clear',
					'escape' => false,
					'tabIndex' => -1,
				));
			}
			$options['beforeInput'] = '<div class="input-group input-date-control">';
			$options['afterInput'] = '<span class="input-group-btn">' . $appendButton . '</span></div>';
			//$options = $this->addClass($options, 'input-group');
		}
		if (!isset($options['label']) || (empty($options['label']) && $options['label'] !== false)) {
			$options['label'] = $this->getLabelText($fieldName);
		}
		$options = $this->_formatFields($options, 'm/d/Y');
		return $this->input("$fieldName.date", $options);
	}

	public function inputTime($fieldName, $options = array()) {
		$options = array_merge(array(
			'placeholder' => '0:00pm',
			'default' => null,
			//'beforeInput' => '<div class="input-group"><span class="input-group-addon"><i class="glyphicon glyphicon-time"></i></span>','afterInput' => '</div>',
		), $this->addClass($options, 'form-control time timepicker'));
		if (!isset($options['div']) || $options['div'] !== false) {
			$options = $this->addClass($options, 'form-group input-time', 'div');
		}
		if ($dataValue = $this->getFieldValue($fieldName, 'time')) {
			$options['default'] = $dataValue;
		}
		if (Param::keyCheck($options, 'inner', true)) {
			$options['wrapInput'] = false;
		}
		
		$options['type'] = 'text';
		$options = $this->_formatFields($options, 'H:i:s');
		return $this->input("$fieldName.time", $options);			
	}
	
	public function inputDatetime($fieldName, $options = array()) {
		$out = '';
		$flip = Param::keyCheck($options, 'flip', true);
		
		$options = $this->addClass($options, 'form-group input-datetime', 'div');
		$options['wrapInput'] = false;

		$div = $options['div'];
		unset($options['div']);
		
		$secondOptions = $options;
		//$secondOptions['div'] = false;
		$secondOptions['label'] = false;
		
		$type1 = 'date';
		$type2 = 'time';
		if ($flip) {
			list($type1, $type2) = array($type2, $type1);
		}
		$function1 = 'input' . ucfirst($type1);
		$function2 = 'input' . ucfirst($type2);
		
		$secondOptions = $this->addClass($secondOptions, "input-datetime-$type2");
		
		if (!empty($options['after'])) {
			$secondOptions = $this->addClass($options, $options['after'], 'after');
			unset($options['after']);
		}
		
		//$options['after'] = $this->{$function2}($fieldName, $secondOptions);
		if (!empty($options['allDay']) || !empty($options['isAllDay'])) {
			$options['after'] .= $this->_inputDateAllDay($fieldName, !empty($options['isAllDay']));
			unset($options['allDay']);
			unset($options['isAllDay']);
		}
		
		//Extract Label
		$label = !empty($options['label']) ? $options['label'] : false;
		if (false !== $label) {
			if (!is_array($label)) {
				$label = array('text' => $label);
			}
			$label = $this->addClass($label, 'control-label');
			$text = $label['text'];
			unset($label['text']);
			$label = $this->Form->label($fieldName, $text, $label);
		}
		$options['label'] = false;

		$out .= $this->{$function1}($fieldName, $options);
		$out .= $this->{$function2}($fieldName, $secondOptions);
		//$out = $this->Html->div('input-datetime-row row', $out);
		
		$inputOptions = array(
			'label' => $text,
			'editable' => true,
			'class' => false,
		) + compact('div');
		if (!empty($options['inner'])) {
			$inputOptions['wrapInput'] = false;
		}
		$return = $this->fakeInput($this->Html->div('input-datetime-control', $out), $inputOptions);
		return $return;

//		return $this->Html->div('input-datetime', $label . $this->Html->div('input-datetime-control', $out));	
	}
	
	function dateRange($name1, $name2, $options = array()) {
		$defaultClass = 'datetime';
		
		$label1 = Param::keyCheck($options, 'label1', true, 'From');
		$label2 = Param::keyCheck($options, 'label2', true, 'To');
		
		$class = Param::keyCheck($options, 'class', false, $defaultClass);
		$after = Param::keyCheck($options, 'after', true, null);
		$before = Param::keyCheck($options, 'before', true, null);
		
		$options = array_merge(array(
			'class' => $defaultClass,
		), (array) $options);
		
		$options1 = array_merge(array(
			'containerClass' => 'dateRange',
			'label' => $label1,
			'before' => $before,
		), $options);
		
		$options2 = array_merge(array(
			'containerClass' => 'dateRange2',
			'div' => false,
			'after' => $after,
		), $options);
		if ($class == 'datetime') {
			$options2['flip'] = true;
			$options2['timeLabel'] = $label2;
			$options2['label'] = false;
		} else {
			$options2['label'] = $label2;
		}
		$time2 = $this->input($name2, $options2);
		
		$options1['after'] = $time2;
		
		return $this->input($name1, $options1);
	}

/**
 * Sets or resets the horizontal form span
 *
 * @param int $span The span to set. If false, it will unset all horizontal span
 * @return void
 **/
	public function setHSpan($span = 9) {
		$this->_hSpan = $span;
		if (!isset($this->_inputDefaults)) {
			$this->_inputDefaults = $this->Form->inputDefaults();
		}
		if (empty($span)) {
			$this->Form->inputDefaults($this->_inputDefaults);
		} else {
			$this->Form->inputDefaults(array(
				'label' => array('class' => 'control-label col col-sm-' . (12 - $span)),
				'wrapInput' => 'col col-sm-' . $span,
			), true);
		}
	}

/**
 * Returns the horizontal form span
 *
 * @return int The current horizontal column span
 **/
	public function getHSpan() {
		return $this->_hSpan;
	}

/**
 * Stores the current horizontal form span but resets the form values to full
 *
 **/
	public function pauseHSpan() {
		$hSpan = $this->getHSpan();
		$this->setHSpan(false);
		$this->_hSpan = $hSpan;
	}

/**
 * Resumes the current form width back to the stored value
 *
 **/
	public function resumeHSpan() {
		$this->setHSpan($this->_hSpan);
	}

	function submitOLD_VERSION($text = null, $attrs = array()) {
		$return = '';
		if (is_array($text)) {
			foreach ($text as $buttonText => $buttonAttrs) {
				if (is_numeric($buttonText)) {
					$buttonText = $buttonAttrs;
					$buttonAttrs = array();
				}
				if (!empty($attrs)) {
					$buttonAttrs = array_merge($attrs, $buttonAttrs);
				}
				$buttonAttrs += array('tag' => false);
				$return .= $this->submit($buttonText, $buttonAttrs);
			}
		} else {
			if ($text === false) {
				$text = '';
			} else if (!isset($text)) {
				$text = 'Submit';
			}
			$attrs = array_merge(array(
				'class' => 'submit',
				'escape' => false,
				'tag' => null,
			), $attrs);
			if ($url = Param::keyCheck($attrs, 'url', true)) {
				$attrs = $this->addClass($attrs, 'button');
				$return .= $this->Html->link($text, $url, $attrs);
			} else {
				$return .= $this->button($text, $attrs);
			}
		}
		if (!Param::falseCheck($attrs, 'tag')) {
			$class = 'layout-buttons';
			if (!empty($attrs['div'])) {
				$tag = 'div';
				if ($attrs['div'] !== true) {
					$class =  $attrs['div'];
				}
			} else {					
				$tag = empty($attrs['tag']) || $attrs['tag'] === true ? 'div' : $attrs['tag'];
			}
			$return = $this->Html->tag($tag, $return, compact('class'));
		}
		return $return;
	}
	
	function searchInput($name, $options = array(), $form = false) {
		$button = $this->Form->button(
			$this->Iconic->icon('magnifying_glass'), 
			array(
				'class' => 'btn',
				'type' => 'submit',
				'div' => false,
			)
		);

		$options = array_merge(array(
			'placeholder' => false,
			'label' => false,
			'value' => '',
			'type' => 'text',
		), $options, array(
			'div' => 'search-box',
			'input-append' => $button,
		));
		
		$return = '';
		if ($form) {
			if (!is_array($form)) {
				if ($form === true) {
					$form = array();
				} else {
					$form = array($form);
				}
			}
			$form += array(null, array());
			$return .= $this->Form->create($form[0], $form[1]);
		}
		$return .= $this->input($name, $options);
		if ($form) {
			$return .= $this->Form->end();
		}
		return $return;
	}
	
	function reset($text = null, $attrs = array()) {
		if (empty($text)) {
			$text = 'Reset';
		}
		$attrs = array_merge(array(
			'class' => 'reset'
		), $attrs);
		return $this->button($text, $attrs);
	}
	
	function buttonLink($text, $url, $attrs = null, $confirm = null) {
		return $this->Html->div('layout-buttons', 
			$this->Html->link($text, $url, $attrs, $confirm)
		);
	}
	
	function fakeInput($value, $options = array()) {
		$inputDefaults = $this->Form->inputDefaults();
		$options = array_merge(array(
			'label' => false,
			'editable' => false,
			'class' => '',
			'before' => '',
			'after' => '',
			'between' => '',
			'div' => 'form-group',
			'class' => 'form-control',
			'formControl' => true,
			'wrapInput' => false,
			'beforeInput' => '',
			'afterInput' => ''
		), $inputDefaults, $options);
		if (empty($options['editable'])) {
			$options = $this->addClass($options, 'uneditable-input');
		}
		extract($options);
		$out = '';
		
		// Fake input
		$input = $this->Html->div($class . ' input-fake', $value);
		$out = $beforeInput . $input . $afterInput;
		if (!empty($wrapInput)) {
			$out = $this->Html->div($wrapInput, $out);
		}
		if (!empty($label)) {
			$labelOptions = array();
			if (is_array($label)) {
				$labelOptions = $label;
				$label = $label['text'];
			}
			if (!empty($inputDefaults['label'])) {
				$labelOptions = array_merge($inputDefaults['label'], $labelOptions);
			}
			//$label = $this->Html->tag('label', $label, array('class' => 'control-label'));
			$label = $this->Form->label('Fake', $label, $labelOptions);
		}
		$out = $before . $label . $between . $out . $after;
		if (!empty($div)) {
			$out = $this->Html->div($div, $out);
		}

		return $out;
	}
	
	function inputCash($fieldName, $options = array()) {
		$options = array_merge(array(
			'type' => 'text',
			'beforeInput' => '<div class="input-group"><span class="input-group-addon">$</span>',
			'afterInput' => '</div>',
			'step' => 'any',
			'placeholder' => '0.00',
		), $options);
		return $this->Form->input($fieldName, $options);
	}
	
	function _paramCheck($param, $val = null, $attrs = null) {
		if ($param == 'submit') {
			return $this->submit($val, $attrs);
		} else if ($param == 'reset') {
			return $this->reset($val, $attrs);
		} else {
			return $param;
		}
	}
	
	/**
	 * Generates a random string of letters and numbers
	 * Taken from: http://www.lost-in-code.com/programming/php-code/php-random-string-with-numbers-and-letters/
	 *
	 **/
	function _randomString($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$string = '';
		for ($p = 0; $p < $length; $p++) {
			$string .= $characters[mt_rand(0, strlen($characters) - 1)];
		}
		return $string;
	}
	
	private function cleanupUrl($url) {
		if (!is_array($url) && substr($url,-1) != '/') {
			$url .= '/';
		}
		$url = Router::url($url);
		return $url;
	}
	
	public function resultToList($result, $model, $primaryKey = 'id', $displayField = 'title') {
		$list = array();
		if (!empty($result[$model])) {
			$result = $result[$model];
		}
		//Twice for HABTM
		if (!empty($result[$model])) {
			$result = $result[$model];
		}
		if (is_array($result)) {
			foreach ($result as $key => $val) {
				if (isset($val[$model])) {
					$val = $val[$model];
				}
				if (is_array($val)) {
					if (!empty($val[$model])) {
						$val = $val[$model];
					}
					$key = $val[$primaryKey];
					$val = $val[$displayField];
				}
				$list[$key] = $val;
			}
		}
		return $list;
	}
	
	private function getLabelText($fieldName, $options = array()) {
		$text = !empty($options['label']) ? $options['label'] : null;
		if ($text === null) {
			if (strpos($fieldName, '.') !== false) {
				$fieldElements = explode('.', $fieldName);
				$text = array_pop($fieldElements);
			} else {
				$text = $fieldName;
			}
			if (substr($text, -3) == '_id') {
				$text = substr($text, 0, -3);
			}
			$text = __(Inflector::humanize(Inflector::underscore($text)));
		}	
		return $text;
	}

	private function getModelData($model = null) {
		if (empty($model) && !empty($this->request->data)) {
			return $this->request->data;
		}
		$models = explode('.', $model);
		$data =& $this->request->data;
		foreach ($models as $subModel) {
			if (isset($data[$subModel])) {
				$data =& $data[$subModel];
				continue;
			} else if (isset($data[0])) {
				foreach ($data as $subData) {
					if (isset($subData[$subModel])) {
						$data =& $subData[$subModel];
						continue 2;
					}
				}
			}
			return null;
		}
		return $data;
	}
	// Checks an options array for specific fields and matches it with a date formatting
	private function _formatFields($options, $dateFormat, $fields = array('value', 'default', 'data-clone-numbered-default')) {
		foreach ($fields as $field) {
			if (isset($options[$field])) {
				$time = is_array($options[$field]) ? implode('', $options[$field]) : $options[$field];
				if (!is_numeric($time)) {
					$time = strtotime($time);
				}
				$options[$field] = date($dateFormat, $time);
			}
		}
		return $options;
	}
}
<?php
/**
 * Layout Helper outputs some basic Html objects that help form a better organized view
 *
 **/
App::uses('LayoutAppHelper', 'Layout.View/Helper');

class FormLayoutHelper extends LayoutAppHelper {
	var $helpers = array('Asset','Html', 'Form', 'Layout', 'Iconic');
	var $buttonIcons = array(
		'add' => 'plus',
		'update' => 'check',
		'cancel' => 'minus_alt',
		'next' => 'arrow_right',
		'prev' => 'arrow_left',
		'upload' => 'arrow_up',
	);
	
	var $toggleCount = 0;
	
	var $_inputCount = array();
	
	function __construct($View, $settings = array()) {
		parent::__construct($View, $settings);

		//Adds new suffixes to account for dateInput and timeInput
		$this->Form->_fieldSuffixes = array_merge($this->Form->_fieldSuffixes, array('date', 'time'));
	}
	
	function beforeRender($viewFile) {
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
	
	public function inputAutoCompleteMulti($model, $url = null, $attrs = array()) {
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

		$valsOutput = '';
		foreach ($allVals as $unchecked => $vals) {
			foreach ($vals as $id => $title) {
				if (!empty($usedVals[$id])) {
					continue;
				}
				$usedVals[$id] = $id;
				$valsOutput .= $this->Html->tag('label',
					$this->Form->checkbox("$model.$i$field", array(
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
			$valsOutput .= $this->Form->input("$model.$i$field", array(
				'type' => 'select',
				'div' => false,
				'label' => false,
				'style' => 'display: none',
				'class' => 'default-vals',
				'options' => array('' => '---') + $this->resultToList($options, $model),
			));
			$i++;
		}
		
		$out = $this->Form->input("$model.$i$field", compact('url', 'label') + array(
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
		);
		$options = array_merge(array(
			'label' => null,
			'div' => 'input text input-autocomplete',
			'value' => null,
		), $custom, $options);
		extract($options);
		$hasValue = !empty($value);

		//debug(array($displayOptions, $this->Html->value($prefix . $idField)));
		
		if (!$hasValue && $this->Html->value($prefix . $idField)) {
		
			if (!empty($displayOptions[$this->Html->value($prefix . $idField)])) {
				$value = $displayOptions[$this->Html->value($prefix . $idField)];
			}		
		}
		
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
		
		if (!empty($display) && empty($displayInput)) {
			$displayInput = $this->Html->div('display fakeInput text', $hasValue ? $value : '', array('style'=> 'display:none;'));
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
			$redirect_url = is_array($redirectUrl) ? Router::url($redirectUrl) . '/' : $redirectUrl;
		}
		$return .= $this->input($prefix . $searchField, array_merge($options, array(
			'type' => 'text',
			'before' => $idInput,
			'between' => $displayInput,
		) + compact('url', 'value', 'redirect_url')));
		return $return;
	}
	
	public function input($name, $options = array()) {
		$beforeInput = '';
		$afterInput = '';

		$options = array_merge(array(
			'type' => 'text',
			'div' => 'input',
		), $options);
		$options = $this->addClass($options, $options['type'], 'div');
		
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
		
		if ($submit = Param::keyCheck($options, 'submit', true)) {
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
		}
		
		return $beforeInput . $this->Form->input($name, $options) . $afterInput;
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
		
		if ($showForm) {
			$return .= $this->Form->create(null, array('class' => 'fullFormWidth'));
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
		
		$attrs = $this->addClass($attrs, 'btn');
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
			'titleTag' => 'h3',
			'class' => '',
			'pass' => array(),
		), $options);
		extract($options);
		$out = '';
		if (is_array($listContent)) {
			$total = count($listContent);
			$type = 'array';
		} else {
			//Adds an extra blank one
			$total = !empty($this->request->data[$model]) ? count($this->request->data[$model]) + 1 : $count;
		}
		if ($total < 0) {
			return $out;
		}
		for ($count = 0; $count < $total; $count++) {
			$row = '';
			if ($type == 'array') {
				$row .= $listContent[$count];
			} else if ($type == 'element') {
				$row .= $this->_View->element($listContent, compact('count') + $pass);
			} else if ($type == 'eval') {
				eval('$row .= ' . $listContent . ';');
			}
			$out .= $this->Html->div('input-list-item', $row);
		}
		if (!empty($legend)) {
			$tag = 'fieldset';
			$title = $legend;
		}
		if ($tag == 'fieldset') {
			$titleTag = 'legend';
		}
		if (!empty($titleTag) && !empty($title)) {
			$out = $this->Html->tag($titleTag, $title) . $out;
		}
		return $this->Html->tag($tag, $out, array('class' => 'input-list ' . $class));
	}
	
	public function toggle($content, $offContent = null, $label, $options = array()) {
		$count = $this->toggleCount++;
		$options = array_merge(array(
			'checked' => null,
			'name' => 'form_layout_toggle' . $count,
			'value' => 1,
		), $options);
		extract($options);
		
		$out = '';
		$toggleId = $options['name'];
		$toggleInput = $this->Form->input($name, array(
			'type' => 'checkbox',
			'label' => $label,
			'div' => false,
			'id' => $toggleId,
		) + compact('checked'));
		$out .= $this->Html->div('toggle-input', 
			$this->Html->tag('label', $toggleInput, array('for' => $toggleId)));
		if (!empty($content)) {
			$out .= $this->Html->div('toggle-content', $content, array(
				'style' => $checked ? null : 'display:none;',
			));
		}		
		if (!empty($offContent)) {
			$out .= $this->Html->div('toggle-off-content', $offContent, array(
				'style' => !$checked ? null : 'display:none;',
			));
		}
		return $this->Html->div('form-layout-toggle', $out);
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
	
	function inputRow($row, $options = array()) {
		$options = array_merge(array(
			'span' => 12,
			'placeholder' => false,
		), $options);
		extract($options);
		$inputs = array('fieldset' => false);
		$rowCount = count($row);
		foreach ($row as $fieldName => $inputOptions) {
			if (is_numeric($fieldName)) {
				$fieldName = $inputOptions;
				$inputOptions = array();
			}
			$spanClass = 'span' . floor($span / $rowCount);
			$inputOptions = array_merge($inputOptions, array(
				'div' => "control-group $spanClass",
				'class' => $spanClass,
			));
			if ($placeholder) {
				if (!empty($inputOptions['label'])) {
					$inputOptions['placeholder'] = $inputOptions['label'];
				} else {
					$inputOptions['placeholder'] = $this->getLabelText($fieldName, $inputOptions);
				}
				$inputOptions['label'] = false;
			}
			$inputs[$fieldName] = $inputOptions;
		}
		return $this->Html->div('controls controls-row', $this->Form->inputs($inputs));
	}
	
	function inputChoices($inputs, $options = array()) {
		$options = array_merge(array(
			'name' => $this->inputNameCount('input_choice'),
		), $options);
		if (!isset($options['default'])) {
			$options['default'] = isset($options['values'][0]) ? $options['values'][0] : 0;
		}
		extract($options);
				
		$return = "\n";
		$count = 0;
		foreach ($inputs as $label => $input) {
			//$input = '<input name="input_choice" value="'. $count . '"';
			$radioValue = isset($options['values'][$count]) ? $options['values'][$count] : $count;
			$isDefault = $radioValue == $default || (empty($default) && $count == 0);
			$return .= $this->Html->div('input-choice-input radio side-inputs',
				$this->Form->radio($name, array($radioValue => $label), array(
					'label' => !is_numeric($label) ? $label : false,
					'legend' => false,
					'value' => $default,
				))
			) . "\n";
			if (is_array($input)) {
				if (empty($input['fieldset']) && empty($input['legend'])) {
					$input['fieldset'] = false;
				}
				$input = $this->Form->inputs($input);
			}

			$return .= $this->Html->div('input-choice', $input, array(
				'style' => empty($isDefault) ? 'display:none;' : null,
			)) . "\n";
			$count++;
		}
		$return = $this->Html->div('input-choices', $return) . "\n";
		
		if (!empty($legend)) {
			$return = $this->Layout->fieldset($legend, $return);
		}
		return $return;
	}
	
	
	function end($text = null, $attrs = array()) {
		return $this->submit($text, $attrs) . $this->Form->end();
	}

 	/**
	 * A form input that includes a calendar dropdown when clicked on
	 * If class is "datetime", then it also includes a separate time input
	 * @param string $fieldName The form field name
	 * @param array $options Standard options for the form item
	 *
	 **/
	function datePairInput($startFieldName, $endFieldName, $options = array()) {
		$out = '';
		$out .= $this->dateInput($startFieldName, $options);
		$out .= $this->dateInput($endFieldName, $options);
		return $this->Html->div('datepair', $out);
	}
	
	private function getDateFieldValue($fieldName, $type = 'date') {
		$value = $this->getFieldValue($fieldName, $type);
		if (!empty($value)) {
			if (is_array($value)) {
				$value = implode(' ', $value);
			}					
			if ($type == 'date') {
				$value = date('m/d/Y', strtotime($value));
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
	
	function dateInput($fieldName, $options = array()) {
		$options = array_merge(array(
				'type' => 'text',
				'placeholder' => 'mm/dd/yyyy',
				'div' => 'control-group date-input',
				'default' => $this->getDateFieldValue($fieldName),
			), $this->addClass($options, 'date datepicker'));
			
		if ($control = Param::keyCheck($options, 'control', true)) {
			$control = array_flip($control);
			$after = '';
			if (isset($control['today'])) {
				$after .= $this->Html->link('Today', '#', array('class' => 'today'));
			}
			if (isset($control['clear'])) {
				$after .= $this->Html->link('Clear', '#', array('class' => 'clear'));
			}
			$options = $this->addClass($options, $this->Html->div('control', $after), 'after');
		}
		return $this->Form->input($fieldName. ".date", $options);
	}
	
	function timeInput($fieldName, $options = array()) {
		$options = array_merge(array(
			'type' => 'text',
			'placeholder' => '0:00pm',
			'div' => 'control-group time-input',
			'default' => $this->getFieldValue($fieldName, 'time'),
		), $this->addClass($options, 'time timepicker'));
		return $this->Form->input($fieldName . '.time', $options);			
	}
	
	function datetimeInput($fieldName, $options = array()) {
		$out = '';
		$dateOptions = $options;
		$timeOptions = array_merge($options, array('label' => false, 'div' => false));
		$dateOptions['after'] = $this->timeInput($fieldName, $timeOptions);
		$out .= $this->dateInput($fieldName, $dateOptions);
		return $this->Html->div('date-time-input', $out);	
	}
	
	function dateInputOLD($fieldName, $options = array()) {
		$class = 'date-input';
		if (strpos($fieldName, '.') === false && !empty($this->request->params['models'])) {
			$model = current($this->request->params['models']);
			$fieldName = $model['className'] . '.' . $fieldName; 
		}
		if (!empty($options['containerClass'])) {
			$class .= ' ' .$options['containerClass'];
		}
		$options = $this->addClass($options, 'datepicker');
		
		//Option values that should be deleted instead of passed to the actual input
		$unset = array('blank', 'control', 'empty');
		
		//Checks for default value
		$value = $this->value(null, $fieldName);
		if(is_array($value)) {
			$value = $value['value'];
		} 
		$value = trim($value);
		
		if($value == '') {
			if (!empty($options['value'])) {
				$value = $options['value'];
			} else if (!empty($options['default'])) {
				$value = $options['default'];
			} else if (empty($value) && empty($options['blank']) && empty($options['empty'])) {
				$value = date('Y-m-d H:i:s');
			} else {
				$value = null;
			}
		}
		
		$hasClass = false;
		$dateTime = true;
		if (!empty($options['datetime'])) {
			$dateTime = true;
			$hasClass = true;
		} else if (!empty($options['date'])) {
			$hasClass = true;
			$dateTime = false;
		}
		if (Param::falseCheck($options, 'time')) {
			$dateTime = false;
		}
		if (!$hasClass) {
			$options = $this->addClass($options, ($dateTime ? 'datetime' : 'date'));
		}

		$dateFormat = 'n/j/Y';
		$timeFormat = 'g:ia';

		if (!empty($options['dateFormat'])) {
			$dateFormat = $options['dateFormat'];
		}
		if (!empty($options['timeFormat'])) {
			$timeFormat = $options['timeFormat'];
		}
		$dateDisp = '';
		$timeDisp = '';
		
		$stamp = !empty($value) ? strtotime($value) : false;
		if ($stamp) {
			$dateDisp = date($dateFormat,$stamp);
			$timeDisp = date($timeFormat,$stamp);
		}

		$divClass = 'input text '.$class;
		$divOption = array('class' => $divClass);
		if (!empty($options['div'])) {
			if ($options['div'] !== false) {
				if (is_array($options['div'])) {
					$options['div'] = array_merge($divOption, $options['div']);
				} else {
					$options['div'] = array('class' => $options['div']);
				}
			}
		} else {
			$options['div'] = $divOption;
		}
		
		$options = array_merge(
			array(
				'class' => $class,
				'type' => 'text',
				'between' => $this->Html->div('calendarPickHolder'),
				'value' => $dateDisp,
				'label' => 'Date',
				'div' => $divOption
			), 
			$options
		);
		$options['value'] = $dateDisp;
				
		$after = $this->Html->tag('div','&nbsp;',array('class' => 'calendarPick hidden'));
		$after .= '</div>';
		
		if($dateTime) {
			$fieldName = $fieldName;
			$fieldName2 = $fieldName . '.timePick';
			$fieldName .= '.datePick';
			$timeLabel = 'Time';
			if (!empty($options['timeLabel']) && strlen($options['timeLabel']) > 0) {
				$timeLabel = $options['timeLabel'];
				unset($options['timeLabel']);
			} else {
				$timeLabel = false;
			}
			$timeInput = $this->Form->input(
				$fieldName2,
				array(
					'type' => 'text',
					'div' => false,
					'class' => 'time timepicker',
					'value' => $timeDisp,
					'label' => $timeLabel,
					'between' => $this->Html->div('timePickHolder')
				)
			);
			$timeInput .= $this->Html->div('timePick hidden','&nbsp;');
			$timeInput .= "</div>\n";
			
			//$timeInput .= '<br/>';
		
			if (!empty($options['flip']) && $options['flip']) {
				//Reverses the date / time to time / date
				unset($options['flip']);
				
				$before = $timeInput . $this->Html->tag('span',null,'secondDate');
				$after .= '</span>';
				
				if (!empty($options['before'])) {
					$options['before'] .= $timeInput;
				} else {
					$options['before'] = $timeInput;
				}
			} else {
				$after .= $this->Html->tag('span',$timeInput, 'secondDate');
			}
		}

		if (!empty($options['after'])) {
			$options['after'] = $after . $options['after'];
		} else {
			$options['after'] = $after;
		}
		
		if (!empty($options['control'])) {
			if (!is_array($options['control'])) {
				$options['control'] = array($options['control']);
			}
			foreach ($options['control'] as $control) {
				$disp = '';
				$title = false;
				if ($control == 'today') {
					$title = 'Today';
					$disp = $this->Html->image('icn/16x16/calendar_today.png');
					$js = '$(this).setDateBuild(\''.date($dateFormat).'\',\''.date($timeFormat).'\');';
				} else if ($control == 'clear') {
					$title = 'Clear';
					$disp = $this->Html->image('icn/16x16/calendar_delete.png');
					$js = '$(this).setDateBuild(\'\',\'\');';
				}
				if ($disp != '') {
					$options['after'] .= ' ' . $this->Html->link($disp, '#', array(
						'title' => $title,
						'escape' => false,
						'class' => 'linkBtn',
						'onclick' => $js . 'return false;',
					));
				}
			}
			unset($options['control']);
		}
		foreach ($unset as $key) {
			unset($options[$key]);
		}

		return $this->Form->input($fieldName, $options);
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
			'between' => $this->Html->div('search-box-border') . $this->Html->div('search-box-container'),
			'after' => "</div>\n" . $button . "</div>\n",
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
		$return .= $this->Form->input($name, $options);
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
		$options = array_merge(array(
			'div' => 'control-group',
			'label' => false,
		), $options);
		$options = $this->addClass($options, 'uneditable-input');
		
		extract($options);
		$out = '';
		if (!empty($label)) {
			$out .= $this->Html->tag('label', $label, array('class' => 'control-label'));
		}
		$out .= $this->Html->div('controls', $this->Html->div($class, $value));
		if (!empty($div)) {
			$out = $this->Html->div($div, $out);
		}
		return $out;
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
		if (is_array($url)) {
			$url = Router::url($url);
		}
		if (substr($url,-1) != '/') {
			$url .= '/';
		}		
		return $url;
	}
	
	private function resultToList($result, $model, $primaryKey = 'id', $displayField = 'title') {
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
	private function getLabelText($fieldName, $options) {
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

}
<?php
/**
 * Layout Helper outputs some basic Html objects that help form a better organized view
 *
 **/
App::uses('LayoutAppHelper', 'Layout.View/Helper');

class FormLayoutHelper extends LayoutAppHelper {
	public $name = 'FormLayout';
	public $helpers = array(
		'Layout.Asset',
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
			$redirectUrl = is_array($redirectUrl) ? Router::url($redirectUrl) . '/' : $redirectUrl;
		}
		$return .= $this->input($searchField, array_merge($options, array(
			'type' => 'text',
			'before' => $idInput,
			'between' => $displayInput,
			'data-url' => $url,
			'data-redirect-url' => $redirectUrl,
		) + compact('value')));
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
		$options = array_merge(array(
			'div' => false,
			'escape' => false,
			'type' => 'submit',
		), $options);
		$options = $this->addClass($options, 'btn btn-default');
		return $this->Form->button($name, $options);
	}
	
	public function input($name, $options = array()) {
		$beforeInput = '';
		$afterInput = '';
		$options = array_merge(array('type' => null), $options);

		/*
		//Removed for addition of Bootstrap
		$options = array_merge(array(
			'type' => 'text',
			'div' => 'input',
		), $options);
		$options = $this->addClass($options, $options['type'], 'div');
		*/

		// Allows for custom types
		switch ($options['type']) {
			case 'id':
				$options['type'] = 'number';
				$options = $this->addClass($options, 'input-id');
				$options['prepend'] = '#';
			break;
			case 'cash': 
				$options['type'] = 'text';
				$options = $this->addClass($options, 'input-cash');
				$options['prepend'] = '$';
				$options['placeholder'] = '0.00';
				$options['step'] = 'any';
			break;
			case 'date':
				$input = $this->inputDate($name, $options);
			break;
			case 'datetime':
				$input = $this->inputDatetime($name, $options);
			break;
			case 'time':
				$input = $this->inputTime($name, $options);
			break;
			case 'float':
				$options['type'] = 'number';
				$options = $this->addClass($options, 'input-number');
				$options['placeholder'] = '0.0';
				$options['step'] = 'any';
			break;
			case 'email' :
				$options['prepend'] = $this->Iconic->icon('at');
			break;
			case 'tel':
			case 'phone':
				$options['prepend'] = $this->Iconic->icon('iphone');
				$options['type'] = 'tel';
			break;
			case 'url':
				$options['prepend'] = '<i class="glyphicon glyphicon-globe"></i>';
				$options['type'] = 'text';
			break;
		}
		
		if (isset($options['inputAppend'])) {
			$options['appendButton'] = $this->_buttonInner($options['inputAppend']);
			unset($options['inputAppend']);
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
		
		
		if (empty($input)) {
			$input = $this->Form->input($name, $options);
		}
		
		return $beforeInput . $input . $afterInput;
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
		$options = $this->addClass($options, 'input-copy');
		
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
		$out = $this->Html->tag($tag, $out, compact('class'));
		
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
			$row = $this->Html->div('input-choice-control', $radio) . "\n";
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
		return $this->input($prefix . 'all_day', compact('default') + array(
			'class' => 'input-date-all-day',
			'div' => 'control-date-all-day',
			'type' => 'checkbox',
			'label' => 'All Day',
		));
	}
	
	function inputDatetimePair($startFieldName, $endFieldName, $options = array()) {
		if (empty($options['label']) && !Param::falseCheck($options, 'label')) {
			$options['label'] = $this->getLabelText($startFieldName);
		}
		if (!empty($options['allDay']) || !empty($options['isAllDay'])) {
			$after = $this->_inputDateAllDay($startFieldName, !empty($options['isAllDay']));
			$options['allDay'] = false;
			$options['isAllDay'] = false;
		} else {
			$after = '';
		}
		$out  = $this->inputDatetime($startFieldName, $this->getPairOptions('first', array('label' => 'From') + $options));
		//$out .= $this->Html->div('datepair-between', ' - ');
		$out .= $this->inputDatetime($endFieldName, $this->getPairOptions('second', array('flip' => true, 'label' => 'To') + $options));
		return $this->fakeInput($out, array(
			'div' => 'datepair datepair-time',
			'label' => $options['label'],
			'editable' => true,
		) + compact('after'));
	}

 	/**
	 * A form input that includes a calendar dropdown when clicked on
	 * If class is "datetime", then it also includes a separate time input
	 * @param string $fieldName The form field name
	 * @param array $options Standard options for the form item
	 *
	 **/
	function inputDatePair($startFieldName, $endFieldName, $options = array()) {
		if (!isset($options['label'])) {
			$options['label'] = $this->getLabelText($startFieldName);
		}
		$out  = $this->inputDate($startFieldName, $this->getPairOptions('first', array('label' => 'From') + $options));
		//$out .= $this->Html->div('datepair-between', ' - ');
		$out .= $this->inputDate($endFieldName, $this->getPairOptions('second', array('label' => 'To') + $options));
		return $this->fakeInput($out, array(
			'div' => 'datepair',
			'label' => $options['label'],
			'editable' => true,
		));
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
	
	function inputDate($fieldName, $options = array()) {
		$options = array_merge(array(
				'placeholder' => 'mm/dd/yyyy',
				'div' => 'input-group input-date',
				'default' => null,
				//'prepend' => '<i class="glyphicon glyphicon-calendar"></i>',
				'control' => array('today', 'clear'),
			), $this->addClass($options, 'date datepicker'));
		if ($dataValue = $this->getDateFieldValue($fieldName)) {
			$options['default'] = $dataValue;
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
			//$options['input-append'] = $this->Html->div('btn-group', $after);
			$appendButton = $this->Html->div('input-date-control', $appendButton);
			$options = $this->addClass($options, $appendButton, 'appendButton');
			$options = $this->addClass($options, 'input-group');
		}
		if (!isset($options['label']) || (empty($options['label']) && $options['label'] !== false)) {
			$options['label'] = $this->getLabelText($fieldName);
		}
		$options = $this->_formatFields($options, 'm/d/Y');
		return $this->input("$fieldName.date", $options);
	}

	function inputTime($fieldName, $options = array()) {
		$options = array_merge(array(
			'placeholder' => '0:00pm',
			'div' => 'form-group input-time',
			'default' => null,
			//'prepend' => '<i class="glyphicon glyphicon-time"></i>',
		), $this->addClass($options, 'time timepicker'));
		if ($dataValue = $this->getFieldValue($fieldName, 'time')) {
			$options['default'] = $dataValue;
		}
		$options['type'] = 'text';
		$options = $this->_formatFields($options, 'H:i:s');
		return $this->input("$fieldName.time", $options);			
	}
	
	function inputDatetime($fieldName, $options = array()) {
		$out = '';
		$flip = Param::keyCheck($options, 'flip', true);
		$secondOptions = $options;
		$function = 'inputDate';
		$secondFunction = 'inputTime';
		if ($flip) {
			list($function, $secondFunction) = array($secondFunction, $function);
		}
		if (!empty($options['after'])) {
			$secondOptions['label'] = false;
			$secondOptions = $this->addClass($options, $options['after'], 'after');
			unset($options['after']);
		}
		
		//$options['after'] = $this->{$secondFunction}($fieldName, $secondOptions);
		if (!empty($options['allDay']) || !empty($options['isAllDay'])) {
			$options['after'] .= $this->_inputDateAllDay($fieldName, !empty($options['isAllDay']));
			unset($options['allDay']);
			unset($options['isAllDay']);
		}
		$col1 = $function == 'inputDate' ? 8 : 4;
		$col2 = 12 - $col1;
		
		$out .= $this->Html->div('col-sm-' . $col1, $this->{$function}($fieldName, $options));
		$out .= $this->Html->div('col-sm-' . $col2, $this->{$secondFunction}($fieldName, $secondOptions));
		return $this->Html->div('input-datetime row', $out);	
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
		$options = array_merge(array(
			'label' => false,
			'editable' => false,
			'class' => '',
			'before' => '',
			'after' => '',
			'between' => '',
		), $options);
		$options = $this->addClass($options, 'form-group', 'div');
		if (empty($options['editable'])) {
			$options = $this->addClass($options, 'uneditable-input');
		}
		extract($options);
		$out = $before;
		if (!empty($label)) {
			$out .= $this->Html->tag('label', $label, $this->Form->addColWidthClass(array('class' => 'control-label'), true));
		}
		$value = $between . $value . $after;
		$class .= ' ' . $this->Form->colWidthClass();
		$out .= $this->Html->div($class, $value);
		if (!empty($div)) {
			$out = $this->Html->div($div, $out);
		}
		return $out;
	}
	
	function inputCash($fieldName, $options = array()) {
		$options = array_merge(array(
			'type' => 'text',
			'prepend' => '$',
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
				$options[$field] = date($dateFormat, strtotime($time));
			}
		}
		return $options;
	}
}
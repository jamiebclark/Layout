<?php
/**
 * Helper for use with displaying contacts
 *
 **/App::uses('LayoutAppHelper', 'Layout.View/Helper');
class AddressBookFormHelper extends LayoutAppHelper {
	var $name = 'AddressBookForm';
	var $helpers = array('Form');
	
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
			if (Param::keyCheck($options, 'required', true)) {
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
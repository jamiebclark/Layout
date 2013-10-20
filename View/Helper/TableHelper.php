<?php
App::uses('InflectorPlus', 'Layout.Lib');
App::uses('LayoutAppHelper', 'Layout.View/Helper');
class TableHelper extends LayoutAppHelper {
	var $name = 'Table';
	var $helpers = array(
		'Html', 
		'Form',
		'Paginator',
		'Layout.Asset',
		'Layout.Layout',	);
	
	var $row = array();
	var $rows = array();
	var $headers = array();
	var $trOptions = array();
	
	var $columnCount = 0;
	
	var $skip = array();
	var $tableRow = array();
	var $getHeader = true;
	var $hasHeader = false;
	var $hasForm = false;
	var $checkboxCount = 0;
	var $currentCheckboxId;
	
	var $currentTableId = 1;
	
	var $trCount = 0;
	var $tdCount = 0;
	
	//Form Properties
	var $defaultModel;
	private $formAddRow = array();
	
	function beforeRender($viewFile) {
		//$this->Asset->css('Layout.layout');
		$this->defaultModel = InflectorPlus::modelize($this->request->params['controller']);
		return parent::beforeRender($viewFile);
	}
	
	//Adds an array of column ids to skip
	function skip($skipIds = null) {
		if (empty($skipIds)) {
			$skipIds = array();
		} else if (!is_array($skipIds)) {
			$skipIds = array($skipIds);
		}
		$this->skip = $skipIds;
	}
	
	/**
	 * Adds a cell to the current table row
	 * 
	 * @param string $cell Cell content
	 * @param string $header Header text
	 * @param string $headerSort Field the column can be sorted by
	 * @param string $skipId An identifier in which the column can be skipped
	 * @param array $cellOptions Additional cell options
	 **/
	function cell() {
		$argKeys = array('cell', 'header', 'headerSort', 'skipId', 'cellOptions');
		$totalArgs = count($argKeys);
		$totalArgKey = $lastArgKey = $totalArgs - 1;
		$args = func_get_args();
		$numArgs = count($args);
		for ($i = $totalArgKey; $i > 0; $i--) {
			if (!empty($args[$i])) {
				$lastArgKey = $i;
				break;
			}
		}
		if ($lastArgKey < $totalArgKey) {
			//The last passed argument can always be cell options
			if (is_array($args[$lastArgKey])) {
				$args[$totalArgKey] = $args[$lastArgKey];
				for ($i = $lastArgKey; $i < $totalArgKey; $i++) {
					$args[$i] = null;
				}
				ksort($args);
			}
		}
		extract(array_combine($argKeys, $args + array_fill(0, $totalArgs, null)));
		
		//Checks if the skipId is in the skip array
		if (!empty($skipId) && $this->__checkSkip($skipId)) {
			return false;
		}
		$formAddCell = '&nbsp;';
		if ($this->getHeader) {
			$this->columnCount++;
			//Stores first instance of non-blank header
			if (!empty($header) && !$this->hasHeader) {
				$this->hasHeader = true;
			}
			if ($headerSort) {
				if ($headerSort === true) {
					$headerSort = null;
				}
				$header = $this->_thSort($header, $headerSort);
			}
			$thOptions = isset($cellOptions['th']) ? $cellOptions['th'] : $cellOptions;
			$this->headers[] = array($header => $thOptions);
			
		}
		/*
		if ($editCell = Param::keyCheck($cellOptions, 'edit', true)) {
			$formAddCell = $editCell;
			$cell = $this->_editCell($cell, $editCell);
		}
		*/
		
		if (is_array($cellOptions)) {
			$cell = array($cell, $cellOptions);
		}
		$this->row[] = $cell;
		if ($this->trCount == 0) {
			$this->formAddRow[] = $formAddCell;
		}
	}
	
	function cells($cells = null, $rowEnd = false) {
		if (is_array($cells)) {
			foreach ($cells as $cell) {
				$cell += array(null, null, null, null, null);
				$this->cell($cell[0], $cell[1], $cell[2], $cell[3], $cell[4]); 
			}
		}
		if ($rowEnd) {
			$this->rowEnd(is_array($rowEnd) ? $rowEnd : array());
		}
	}
	
	function tableCheckbox($options = array()) {
		if (!empty($options) && !is_array($options)) {
			$options = array('value' => $options);
		}
		$name = 'table_checkbox.' . $this->checkboxCount;
		$name = 'data[table_checkbox][' . $this->checkboxCount . ']';
		$id = 'table_checkbox' . $this->checkboxCount;
		$options = array_merge(array(
			'name' => $name,
			'type' => 'checkbox',
			'label' => false,
			'div' => false,
			'hiddenField' => false,
			'id' => $id,
		), $options);
		$this->currentCheckboxId = $options['id'];

		$this->hasForm = true;

		$this->checkboxCount++;
		return $this->Form->input($name, $options);
	}
	
	function checkbox($options = array()) {
		if (!empty($options) && !is_array($options)) {
			$options = array('value' => $options);
		}
		$cell = $this->tableCheckbox($options);
		$header = $this->Form->input('check-all-checkbox', array(
			'name' => 'check-all-checkbox',
			'type' => 'checkbox',
			'class' => 'check-all',
			'div' => false,
			'label' => false,
		));
		$attrs = array(
			'width' => 20,
			'class' => 'table-checkbox',
		);
		return $this->cell($cell, $header, null, 'checkbox', $attrs);
	}
	
	function withChecked($content = null) {
		$out = '';
		if (is_array($content)) {
			$withChecked = array('' => ' -- Select action -- ');
			foreach ($content as $action => $label) {
				if (is_int($action)) {
					$action = $label;
					$label = InflectorPlus::humanize($label);
				}
				$withChecked[$action] = $label;
			}
			$out .= $this->Form->input('checked_action', array(
				'type' => 'select',
				'options' => $withChecked,
				'label' => 'With Checked:',
				'div' => false,
				'name' => 'checked_action',
			));
		} else {
			$return .= $content;
		}
		$out .= $this->Form->submit('Go', array('name' => 'with_checked','div' => false));
		return $this->Html->div('table-with-checked form-inline', $out);
	}

	public function tableSortMenu($sortMenu = array(), $options = array()) {
		$options = $this->addClass($options, 'table-sort btn');
		$menu = array();
		$text = 'Sort Result';
		$named = !empty($this->request->params['named']) ? $this->request->params['named'] : array();
		
		foreach ($sortMenu as $k => $sortOptions) {
			$sortOptions += array(null, null, true);
			list($title, $sort, $direction) = $sortOptions;
			if (!$direction || $direction == 'desc' || $direction == 'DESC') {
				$direction = 'desc';
			} else {
				$direction = 'asc';
			}
			if (
				(!empty($named['sort']) && $named['sort'] == $sort) && 
				(!empty($named['direction']) && $named['direction'] == $direction)
			) {
				$text = "Sorting: $title";
				$active = true;
				if ($direction == 'asc') {
					$direction = 'desc';
				} else {
					$direction = 'asc';
				}
			} else {
				$active = false;
			}
			$menu[] = array($title, compact('sort', 'direction'), compact('active'));
		}
		return $this->Layout->dropdown($text, $menu, $options);
	}

	public function rowEnd($trOptions = array()) {
		if (!empty($trOptions) && !is_array($trOptions)) {
			$trOptions = array('class' => $trOptions);
		}
		$this->getHeader = false;
		$row = $this->row;
		$this->trOptions[$this->trCount] = $trOptions;
		
		$trKey = $this->trCount;
		
		$this->tdCount = 0;
		$this->trCount++;
		$this->rows[] = $row;
		$this->row = array();
		
		$tdKey = count($this->rows) - 1;
		return $row;
	}
	
	
	/**
	 * Legacy function to output table
	 * 
	 * @param array $options Table options
	 * @return string HTML table
	 **/
	public function table($options = array()) {
		return $this->output($options);
	}
	
	/**
	 * Outputs current table information
	 * 
	 * @param array $options Table options
	 * @return string HTML table
	 **/
	public function output($options = array()) {
		$options = array_merge(array(
			'form' => $this->hasForm,
		), $options);
		
		$this->currentTableId++;
		
		if (!is_array($options)) {
			$options = array($options => true);
		}
		
		$isEmpty = empty($this->rows);
		
		$output = '';
		$after = '';
		
		if (!$this->hasHeader) {
			$this->headers = null;
		}
		if (!$isEmpty && !empty($this->checkboxCount)) {
			if (!empty($options['withChecked'])) {
				if (!isset($options['form'])) {
					$options['form'] = true;
				}
				$after .= $this->withChecked($options['withChecked']);
				unset($options['withChecked']);
			}
			
			//Wraps it in a form tag
			if (!isset($options['form'])) {
				$options['form'] = $this->hasForm;
			}
		}
		
		$formOptions = !empty($options['form']) ? $options['form'] : null;
		unset($options['form']);

		$output .= $this->_table($this->headers, $this->rows, $options + compact('after'));

		if (!empty($formOptions)) {
			$output = $this->formWrap($output, $formOptions);
		}
		
		$this->reset();
		return $output;
	}
	
	function formWrap($output, $options = null) {
		return $this->formOpen($options) . $output . $this->formClose($options);
	}
	
	function formOpen($options = null) {
		if ($this->hasForm && !isset($options)) {
			$options = true;
		}
		$out = '';
		if (!empty($options)) {
			if (!is_array($options)) {
				if ($options !== true) {
					$options = array('model' => $options['form']);
				} else {
					$options = array();
				}
			}
			$options = array_merge(array(
				'id' => 'tableOutput' . $this->currentTableId,
				'url' => '/' . $this->request->url,
				//'action' => false,
			), $options);
			
			if (!empty($options['model'])) {
				$modelName = $options['model'];
				unset($options['model']);
			} else {
				$modelName = InflectorPlus::modelize($this->request->params['controller']);
			}
			$out .= $this->Form->create($modelName, $options);
			$out .= $this->Form->hidden('useModel', array(
				'value' => $modelName,
				'name' => 'useModel',
			));
		}
		return $out;
	}
	
	function formClose($options = array()) {
		$out = '';
		if ($options === true || !isset($options['form']) || $options['form'] !== false) {
			$out .= $this->Form->end();
		}
		return $out;
	}
	
	function reset($set = null) {
		$this->_set(array(
			'skip' => array(),
			'row' => array(),
			'rows' => array(),
			'headers' => array(),
			'hasHeader' => false,
			'hasForm' => false,
			'formAddRow' => array(),
			'getHeader' => true,
//			'checkboxCount' => 0,
			'currentCheckboxId' => null,
			'columnCount' => 0,
			'tdCount' => 0,
			'trCount' => 0,
			'trOptions' => array(),
		));

		if (!empty($set)) {
			$this->_set($set);
		}
	}
	
	function isSkipped($th) {
		return $this->__checkSkip($th);
	}
	
	function __checkSkip($skipId = null) {
		if (empty($this->skip)) {
			return false;
		} else if (is_array($this->skip)) {
			return in_array($skipId, $this->skip);
		} else {
			return $skipId == $this->skip;
		}
	}
	
	// Creates the navigation options for the table, including the pagination and sorting options
	// If wrap is set to true, it return an array of the top and bottom navigation menus
	function tableNav($options = array(), $wrap = false) {
		$return = $wrap ? array('','') : '';
		$out = '';
		
		if (!empty($options['sort'])) {
			$out .= $this->tableSortMenu($options['sort'], array('class' => 'pull-right'));
		}
		if (!empty($options['paginate'])) {
			$out .= $this->Layout->paginateNav();
		}
		if (!empty($out)) {
			if ($wrap) {
				//Returns both top and bottom
				$return = array(
					$this->Html->div('table-nav table-nav-top', $out),
					$this->Html->div('table-nav table-nav-bottom', $out),
				);
			} else {
				$return = $this->Html->div('table-nav', $out);
			}
		}
		return $return;	
	}
	
	function _table($headers = null, $rows = null, $options = array()) {
		$return = $tableNav = '';
		list($tableNavTop, $tableNavBottom) = $this->tableNav($options, true);
		unset($options['sort']);
		unset($options['paginate']);

		$return .= $tableNavTop;
		
		if (empty($rows) && ($empty = Param::keyCheck($options, 'empty', true))) {
			return $empty;
		}
		$options = array_merge(array(
				'cellspacing' => 0,
				'border' => 0,
				//Default Html->tableCells stuff
				'tableCells' => array(),
			), (array) $options
		);
		$options = $this->addClass($options, 'table layout-table');
		$options['tableCells'] = array_merge(array(
			'oddTrOptions' => array('class' => 'altrow'),
			'evenTrOptions' => null,
			'useCount' => false,
			'continueOddEven' => true
		), $options['tableCells']);

		$tableCellOptions = Param::keyCheck($options, 'tableCells', true, array());
		$div = Param::keyCheck($options, 'div', true);
		
		if (!empty($options['before'])) {
			$return .= $options['before'];
			unset($options['before']);
		}
		if (!empty($options['after'])) {
			$after = $options['after'];
			unset($options['after']);
		} else {
			$after = '';
		}
		
		$rowsOut = array();
		if (!empty($headers)) {
			$rowsOut[] = $this->Html->tableHeaders($headers);
		}
		if (!empty($rows)) {
			extract($tableCellOptions);
			if (!empty($options['full_width'])) {
				foreach ($rows as $r => $row) {
					foreach ($row as $c => $cell) {
						if (!empty($cell[0])) {
							$rows[$r][$c][0] = preg_replace('/([\s]+)/', '&nbsp;', $cell[0]);
						}
					}
				}
			}
			foreach ($rows as $k => $row) {
				$trOptions = !empty($this->trOptions[$k]) ? $this->trOptions[$k] : array();
				if ($k % 2) {
					$trOptions = array_merge((array)$oddTrOptions, $trOptions);
				} else {
					$trOptions = array_merge((array)$evenTrOptions, $trOptions);
				}
				$cellsOut = array();
				foreach ($row as $cell) {
					$cellOptions = array();
					if (is_array($cell)) {
						list($cell, $cellOptions) = $cell + array(null, null);
					}
					$tag = Param::keyCheck($cellOptions, 'th', true) ? 'th' : 'td';
					$cellsOut[] = $this->Html->tag($tag, $cell, $cellOptions);
				}
				$rowsOut[] = $this->Html->tag('tr', implode('', $cellsOut), $trOptions);
			}
			$return .= $this->Html->tag('table', implode("\n", $rowsOut), $options) . "\n";
		}
		$return .= $after . $tableNavBottom;
	
		if ($div) {
			$return = $this->Html->div($div, $return);
		}
		return $return;
	}

	function _thSort($label = null, $sort = null, $options = array()) {
		$options = array_merge(array(
			'model' => null
		), $options);
		
		if (empty($label)) {
			$label = '&nbsp;';
		}
		$paginate = true || !empty($this->Paginator);
		if (!empty($paginate)) {
			$params = $this->Paginator->params($options['model']);
			$paginate = !empty($params);
		}

		if (!$paginate) {
			$label = $this->_thSortLink($sort, $label); //ucfirst($label);
		} else {
			$label = $this->Paginator->sort($sort, $label, array('escape' => false));
		}
		return $label;
	}
	
	function _thSortLink($sort, $label = null) {
		$direction = 'asc';
		$class = null;

		if (!empty($this->request->params['named']['sort']) && $this->request->params['named']['sort'] == $sort) {
			if (!empty($this->request->params['named']['direction']) && $this->request->params['named']['direction'] == 'asc') {
				$direction = 'desc';
			}
			$class = $direction;
		}
		return $this->Html->link($label, compact('sort', 'direction'), compact('class'));
	}
}
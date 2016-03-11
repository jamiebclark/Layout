<?php
App::uses('InflectorPlus', 'Layout.Lib');
App::uses('LayoutAppHelper', 'Layout.View/Helper');
App::uses('Param', 'Layout.Lib');

class TableHelper extends LayoutAppHelper {
	public $name = 'Table';
	public $helpers = [
		'Html', 
		'Form',
		'Paginator',
		'Layout.Layout',
	];
	
	protected $row = [];
	protected $rows = [];
	protected $headers = [];
	protected $trOptions = [];
	
	protected $columnCount = 0;
	
	protected $skip = [];
	protected $tableRow = [];
	protected $getHeader = true;
	protected $hasHeader = false;
	protected $hasForm = false;
	protected $checkboxCount = 0;
	protected $currentCheckboxId;
	
	protected $sortUrl = [];
	
	protected $currentTableId = 1;
	
	protected $trCount = 0;
	protected $tdCount = 0;
	
	//Form Properties
	protected $defaultModel;
	protected $formAddRow = [];
	
	public function beforeRender($viewFile) {
		$this->defaultModel = InflectorPlus::modelize($this->request->params['controller']);
		return parent::beforeRender($viewFile);
	}
	
	//Adds an array of column ids to skip
	public function skip($skipIds = null) {
		if (empty($skipIds)) {
			$skipIds = [];
		} else if (!is_array($skipIds)) {
			$skipIds = [$skipIds];
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
	public function cell() {
		$argKeys = ['cell', 'header', 'headerSort', 'skipId', 'cellOptions'];
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
			// The last passed argument can always be cell options
			if (is_array($args[$lastArgKey])) {
				$args[$totalArgKey] = $args[$lastArgKey];
				for ($i = $lastArgKey; $i < $totalArgKey; $i++) {
					$args[$i] = null;
				}
				ksort($args);
			}
		}
		extract(array_combine($argKeys, $args + array_fill(0, $totalArgs, null)));
		
		// Checks if the skipId is in the skip array
		if (!empty($skipId) && $this->_checkSkip($skipId)) {
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
				$header = $this->thSort($header, $headerSort);
			}
			$thOptions = isset($cellOptions['th']) ? $cellOptions['th'] : $cellOptions;
			$this->headers[] = [$header => $thOptions];
			
		}
		/*
		if ($editCell = Param::keyCheck($cellOptions, 'edit', true)) {
			$formAddCell = $editCell;
			$cell = $this->_editCell($cell, $editCell);
		}
		*/
		
		if (is_array($cellOptions)) {
			$cell = [$cell, $cellOptions];
		}
		$this->row[] = $cell;
		if ($this->trCount == 0) {
			$this->formAddRow[] = $formAddCell;
		}
	}
	
/**
 * Adds multiple cells to the table row and then closes the row
 *
 * @param array $cells An array of cell information
 * @param array $options Row options
 * @return void;
 **/
	public function row($cells = null, $options = []) {
		if (empty($options)) {
			$options = true;
		}
		return $this->cells($cells, $options);
	}

/**
 * Adds multiple cells to the table
 *
 * @param array $cells An array of cell information
 * @param bool|array $rowEnd If not false, it will end the row after inserting the cells
 * @return void;
 **/
	public function cells($cells = null, $rowEnd = false) {
		if (is_array($cells)) {
			foreach ($cells as $cell) {
				$cell += [null, null, null, null, null];
				$this->cell($cell[0], $cell[1], $cell[2], $cell[3], $cell[4]); 
			}
		}
		if ($rowEnd) {
			$this->rowEnd(is_array($rowEnd) ? $rowEnd : []);
		}
	}
	
	public function tableCheckbox($value) {
		$name = 'data[table_checkbox][' . $this->checkboxCount . ']';
		$id = 'table_checkbox' . $this->checkboxCount;
		$this->currentCheckboxId = $id;
		$this->hasForm = true;
		$this->checkboxCount++;
		return sprintf('<span class="checkbox"><input type="checkbox" name="%s" id="%s" value="%s"/></span>', 
			$name, $id, $value
		);
	}
	
	public function checkbox($value = null) {
		$cell = $this->tableCheckbox($value);

		$header = sprintf('<input type="checkbox" name="%1$s" class="%2$s" id="%1$s" value="1"/>', 
			'check-all-checkbox', 'check-all'
		);
		$attrs = [
			'width' => 20,
			'class' => 'table-checkbox',
		];
		return $this->cell($cell, $header, null, 'checkbox', $attrs);
	}
	
	public function withChecked($content = null) {
		$out = '';
		if (is_array($content)) {
			$withChecked = ['' => ' -- Select action -- '];
			foreach ($content as $action => $label) {
				if (is_int($action)) {
					$action = $label;
					$label = InflectorPlus::humanize($label);
				}
				$withChecked[$action] = $label;
			}
			$out .= $this->Form->input('checked_action', [
				'type' => 'select',
				'options' => $withChecked,
				'label' => 'With Checked:',
				'div' => 'form-group',
				'wrapInput' => false,
				'class' => 'form-control',
				'name' => 'checked_action',
			]);
		} else {
			$return .= $content;
		}
		$out .= $this->Form->button('Go', [
			'type' => 'submit', 'class' => 'btn btn-default', 'name' => 'with_checked','div' => false
		]);
		return $this->Html->div('table-with-checked form-inline', $out);
	}

	public function tableSortMenu($sortMenu = [], $options = []) {
		$options = $this->addClass($options, 'table-sort btn');
		$menu = [];
		$text = 'Sort Result';
		$named = !empty($this->request->params['named']) ? $this->request->params['named'] : [];
		
		$url = ['sort' => false, 'direction' => false, 'page' => false] + Url::urlArray();
		
		foreach ($sortMenu as $k => $sortOptions) {
			$sortOptions += [null, null, true];
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
			$menu[] = array($title, compact('sort', 'direction') + $url, compact('active'));
		}
		return $this->Layout->dropdown($text, $menu, $options);
	}

	public function rowEnd($trOptions = []) {
		if (!empty($trOptions) && !is_array($trOptions)) {
			$trOptions = ['class' => $trOptions];
		}
		$this->getHeader = false;
		$row = $this->row;
		$this->trOptions[$this->trCount] = $trOptions;
		
		$trKey = $this->trCount;
		
		$this->tdCount = 0;
		$this->trCount++;
		$this->rows[] = $row;
		$this->row = [];
		
		$tdKey = count($this->rows) - 1;
		return $row;
	}
	
	
/**
 * Legacy function to output table
 * 
 * @param array $options Table options
 * @return string HTML table
 **/
	public function table($options = []) {
		return $this->output($options);
	}
	
/**
 * Outputs current table information
 * 
 * @param array $options Table options
 * @return string HTML table
 **/
	public function output($options = []) {
		$options = array_merge([
			'form' => $this->hasForm,
		], $options);
		
		$this->currentTableId++;
		
		if (!is_array($options)) {
			$options = [$options => true];
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
	
	public function formWrap($output, $options = null) {
		return $this->formOpen($options) . $output . $this->formClose($options);
	}
	
	public function formOpen($options = null) {
		if ($this->hasForm && !isset($options)) {
			$options = true;
		}

		$out = '';
		if (!empty($options)) {
			if (!is_array($options)) {
				if ($options !== true) {
					$options = ['model' => $options];
				} else {
					$options = [];
				}
			}
			$options = array_merge([
				'id' => 'tableOutput' . $this->currentTableId,
				'url' => '/' . $this->request->url,
				//'action' => false,
			], $options);
			$options = $this->addClass($options, 'form-fullwidth');
			
			if (!empty($options['model'])) {
				$modelName = $options['model'];
				unset($options['model']);
			} else {
				$modelName = InflectorPlus::modelize($this->request->params['controller']);
			}
			$out .= $this->Form->create($modelName, $options);
			$out .= $this->Form->hidden('useModel', [
				'value' => $modelName,
				'name' => 'useModel',
			]);
		}
		return $out;
	}
	
	public function formClose($options = []) {
		$out = '';
		if ($options === true || !isset($options['form']) || $options['form'] !== false) {
			$out .= $this->Form->end();
		}
		return $out;
	}
	
	public function reset($set = null) {
		$this->_set([
			'skip' => [],
			'row' => [],
			'rows' => [],
			'headers' => [],
			'hasHeader' => false,
			'hasForm' => false,
			'formAddRow' => [],
			'getHeader' => true,
//			'checkboxCount' => 0,
			'currentCheckboxId' => null,
			'columnCount' => 0,
			'tdCount' => 0,
			'trCount' => 0,
			'trOptions' => [],
			'sortUrl' => [],
		]);

		if (!empty($set)) {
			$this->_set($set);
		}
	}
	
/**
 * Checks if a column is being skipped
 *
 * @param string $th The heading of the column
 * @return bool;
 **/
	public function isSkipped($th) {
		return $this->_checkSkip($th);
	}
	
	protected function _checkSkip($skipId = null) {
		if (empty($this->skip)) {
			return false;
		} else if (is_array($this->skip)) {
			return in_array($skipId, $this->skip);
		} else {
			return $skipId == $this->skip;
		}
	}

/**
 * Creates the navigation options for the table, including the pagination and sorting options
 * If wrap is set to true, it return an array of the top and bottom navigation menus
 *
 * @param array $options Navigation option
 * @param bool $wrap If true, wraps the output in DIV tags
 * @return string;
 **/
	public function tableNav($options = [], $wrap = false) {
		$return = $wrap ? ['',''] : '';
		$out = '';
		
		if (!empty($options['sort'])) {
			$out .= $this->tableSortMenu($options['sort'], ['class' => 'pull-right']);
		}
		
		$model = !empty($options['model']) ? $options['model'] : $this->Paginator->defaultModel();

		if (
			(!isset($options['paginate']) || $options['paginate'] !== false) &&
			!empty($this->request->params['paging'][$model])
		) {
			$out .= $this->Layout->paginateNav(compact('model'));
			//$out .= $this->Paginator->pagination(['ul' => 'pagination', 'div' => 'text-center']);
		}

		if (!empty($out)) {
			if ($wrap) {
				//Returns both top and bottom
				$return = array(
					$this->Html->div('row table-nav table-nav-top', $out),
					$this->Html->div('row table-nav table-nav-bottom', $out),
				);
			} else {
				$return = $this->Html->div('row table-nav', $out);
			}
		}
		return $return;	
	}
	
	protected function _table($headers = null, $rows = null, $options = []) {
		$return = $tableNav = '';
		list($tableNavTop, $tableNavBottom) = $this->tableNav($options, true);
		unset($options['sort']);
		unset($options['paginate']);

		$return .= $tableNavTop;
		
		if (empty($rows) && ($empty = Param::keyCheck($options, 'empty', true))) {
			return $empty;
		}
		$options = array_merge([
				'cellspacing' => 0,
				'border' => 0,
				//Default Html->tableCells stuff
				'tableCells' => [],
			], (array) $options
		);
		$options = $this->addClass($options, 'table layout-table');
		$options['tableCells'] = array_merge([
			'oddTrOptions' => null,
			'evenTrOptions' => null,
			'useCount' => false,
			'continueOddEven' => true,
			'altrowClass' => 'altrow',
		], $options['tableCells']);

		$tableCellOptions = Param::keyCheck($options, 'tableCells', true, []);
		$div = Param::keyCheck($options, 'div', true);
		
		if (!empty($options['before'])) {
			$return .= $options['before'];
		}
		if (!empty($options['after'])) {
			$after = $options['after'];
		} else {
			$after = '';
		}
		unset($options['after']);
		unset($options['before']);
		
		$t = [
			'head' => [],
			'body' => [],
			'foot' => [],
		];
		if (!empty($headers)) {
			$t['head'][] = $this->Html->tableHeaders($headers);
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
				$trOptions = !empty($this->trOptions[$k]) ? $this->trOptions[$k] : [];
				if ($k % 2) {
					$trOptions = array_merge((array)$oddTrOptions, $trOptions);
					if (!empty($altrowClass)) {
						$trOptions = $this->addClass($trOptions, $altrowClass);
					}
				} else {
					$trOptions = array_merge((array)$evenTrOptions, $trOptions);
				}
				$cellsOut = [];
				foreach ($row as $cell) {
					$cellOptions = [];
					if (is_array($cell)) {
						list($cell, $cellOptions) = $cell + [null, null];
					}
					$isHead = Param::keyCheck($cellOptions, 'th', true);
					$tag =  $isHead ? 'th' : 'td';
					$cellsOut[$isHead ? 'head' : 'body'][] = $this->Html->tag($tag, empty($cell) ? ' ' : $cell, $cellOptions);
				}
				foreach ($cellsOut as $key => $row) {
					$t[$key][] = $this->Html->tag('tr', implode('', $row), $trOptions);
				}
			}
			$out = '';
			foreach ($t as $key => $rows) {
				if (!empty($rows)) {
					$out .= $this->Html->tag("t$key", implode("\n", $rows));
				}
			}
			$return .= $this->Html->tag('table', $out, $options) . "\n";
		}
		$return .= $after . $tableNavBottom;
	
		if ($div) {
			$return = $this->Html->div($div, $return);
		}
		return $return;
	}

/**
 * Creates a sorting link for the heading cell
 *
 * @param string $sort The field on which to sort
 * @param string $label An optional label to display for the sorting field
 * @return string;
 **/
 	protected function thSort($label = null, $sort = null, $options = []) {
		$options = array_merge([
			'model' => null
		], $options);
		
		if (empty($label)) {
			$label = '&nbsp;';
		}
		$paginate = true || !empty($this->Paginator);
		if (!empty($paginate)) {
			$params = $this->Paginator->params($options['model']);
			$paginate = !empty($params);
		}

		if (!$paginate) {
			// Creates a sorting link
			$direction = 'asc';
			$class = null;
			if (!empty($this->request->params['named'])) {
				$named = $this->request->params['named'];
				if (!empty($named['sort']) && $named['sort'] == $sort) {
					if (!empty($named['direction']) && $named['direction'] == 'asc') {
						$direction = 'desc';
					}
					$class = $direction;
				}
				$label = $this->Html->link(
					$label, 
					compact('sort', 'direction') + $this->sortUrl, 
					compact('class')
				);
			}
		} else {
			$label = $this->Paginator->sort($sort, $label, [
				'url' => $this->sortUrl,
				'escape' => false
			]);
		}
		return $label;
	}	
}

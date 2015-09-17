<?php
/**
 * Handles text being displayed in message boards / blogs
 *
 **/
App::uses('Param', 'Layout.Lib');
App::uses('TextCleanup', 'Layout.Lib');
App::uses('Markup', 'Layout.Lib');
App::uses('DisplayText', 'Layout.Lib');

App::uses('LayoutAppHelper', 'Layout.View/Helper');

class DisplayTextHelper extends LayoutAppHelper {
	public $helpers = array(
		'Layout.Grid',
		'Html', 
		'Layout.Layout', 
		'Layout.Iconic'
	);

	protected $_engine;

	public function __construct(View $View, $options = null) {
		$options['wikiModel'] = $this->wikiModel;
		$this->_engine = new DisplayText($options);

		parent::__construct($View, $options);
	}
	
/**
 * Call methods from String utility class
 *
 * @param string $method Method to call.
 * @param array $params Parameters to pass to method.
 * @return mixed Whatever is returned by called method, or false on failure
 */
	public function __call($method, $params) {
		return call_user_func_array(array($this->_engine, $method), $params);
	}


/**
 * Runs all functions on text
 *  $options accepts the following:
 *   - format : false for no formatting
 *   - urls : false for no auto-linked urls
 *   - smileys : false for no emoticons
 *   - html : false for no html tags
 *   - multiNl : false to remove multiple new line characters
 **
 **/
	public function text($text, $options = array()) {
		if (empty($text) && !empty($options['empty'])) {
			$this->addClass($options, 'empty');
			$text = $options['empty'];
		}
		
		if (!empty($options['div'])) {
			$options['tag'] = 'div';
			$options['class'] = $options['div'];
		}
		if (!empty($options['tag']) || !empty($options['class'])) {
			$tag = !empty($options['tag']) ? $options['tag'] : 'div';
			$attrs = array();
			if (!empty($options['class'])) {
				$attrs['class'] = $options['class'];
			}
			$before = $this->Html->tag($tag, null, $attrs);
			$after = "</$tag>\n";
		} else {
			list($before, $after) = array('', '');
		}

		$text = $this->_engine->text($text, $options);

		if (Param::falseCheck($options, 'columns') !== false) {
			$text = $this->parseColumns($text, compact('before', 'after'));
		} else {
			$text = $before . $text . $after;
		}
		return $text;
	}
	
	
/**
 * Generates a table of formatting commands and their result
 *
 **/
	public function cheatSheet($collapse = false) {
		$out = '';
		if (!empty($this->constants)) {
			$out .= $this->Html->tag('h3', 'Constants');
			$out .= 'These constants will be updated from year to year. Using them will keep text automatically updated.';
			$rows = array();
			foreach ($this->constants as $constant => $value) {
				$rows[] = array($this->Html->tag('code', $constant),$value);
			}
			$table = $this->Html->tableHeaders(array('You type:', 'It displays:'));
			$table .= $this->Html->tableCells($rows, array('class' => 'altrow'));
			$out .= $this->Html->tag('table', $table, array('class' => 'displaytext-cheatsheet-constants'));
			$out .= '<hr/>';
		}
		
		
		$out .= $this->Html->tag('h3', 'Style Shortcuts');
		$format = array(
			'=Heading 1=',
			'==Heading 2==',
			'===Heading 3===',
			'====Heading 4====',
			"''Italic (two single-quotes)''",
			"'''Bold (three single-quotes)'''",
			'[http://google.com Link text comes right after address]',
			'""Quoted text surrounded by two double-quotes""',
			'"""Quoted text (with quotes) surrounded by three double-quotes"""',
			"\n- Unordered List Item 1\r\n- Unordered List Item 2\r\n- Unordered List Item 3\r\n",
			"\n1. Ordered List Item 1\r\n2. Ordered List Item 2\r\n3. Ordered List Item 3\r\n",
		);
		$rows = array();
		foreach ($format as $line) {
			$rows[] = array($this->Html->tag('pre', $line), $this->smartFormat($line));
		}
		$table = $this->Html->tableHeaders(array('You type:', 'It displays:'));
		$table .= $this->Html->tableCells($rows, array('class' => 'altrow'));
		$out .= $this->Html->tag('table', $table, array('class' => 'displaytext-cheatsheet-shortcuts'));
		
		$out = $this->Html->div('displaytext-cheatsheet', $out);
		if ($collapse) {
			$out = $this->Layout->toggle($out, null, 'DisplayText Cheat Sheet');
		}
		$out = $this->Html->div('panel panel-default',
			$this->Html->div('panel-heading', 'Text Formatting Cheat Sheet') .
			$this->Html->div('panel-body', $out)
		);
		return $out;
	}
	
	function parseColumns($str, $options = array()) {
		$options = array_merge(array(
			'before' => '',
			'after' => '',
			'tag' => 'div',
			'class' => 'text-column',
			'padding' => 1, //%
		), $options);
		extract($options);
		
		$return = $str;
		
		$columns = explode('<COLUMN', $return);
		if (count($columns) > 1) {
			$return = '';

			$columnVals = array();
			$columnCount = 0;
			
			foreach ($columns as $column) {
				//Find Attrs
				preg_match('/^[\s]*([^<>]*)>(.*)/sm', $column, $matches);
				if (!empty($matches)) {
					//debug(compact('column', 'matches'));
					$attrs = $matches[1];
					$column = $matches[2];
				} else {
					$attrs = null;
				}
				$width = is_numeric($attrs) ? $attrs : 1;
				$column = Markup::trimBreaks($column);
				if (empty($column)) {
					continue;
				}
				$columnCount += $width;
				$columnVals[] = array(
					'text' => $column,
					'width' => $width,
				);
			}
			
			$totalColumns = count($columnVals) - 1;
			$return .= $this->Grid->open();
			foreach ($columnVals as $key => $col) {
				$class = "{$col['width']}/$columnCount";
				$return .= $this->Grid->col($class, $before . $col['text'] . $after);
			}
			$return .= $this->Grid->close();
		} else {
			$return = $before . $return . $after;
		}
		return $return;
	}
	
	public function tableOfContents(&$text, $options = array()) {
		$options = array_merge(array(
			'cutoff' => 3,
		), $options);
		
		$text = $this->Html->div('parseWrapper', $text);
		
		$p = xml_parser_create();
		xml_set_character_data_handler($p, array(&$this, 'xmlDataHandler'));
		xml_parse_into_struct($p, $text, $elements, $index);
		
		$slugs = array();
		
		$return = '';
		$hIndex = 0;
		$toc = '';	//Table of Contents
		$bullet = array();
		$url = Url::urlArray() + array('base' => false);
		unset($url['#']);
		$currentUrl = Router::url($url);
		$count = 0;
		foreach ($elements as $element) {
			$element = array_merge(array(
				'value' => null,
				'attributes' => array()
			), $element);
			
			if (!empty($options['value'])) {
				$element['value'] = $this->_parseTextValue($element['value'], $options['value']);
			}
			
			if ($element['level'] == 1) {
				$return .= $element['value'];
			} else if ($element['type'] == 'close') {
				$return .= '</' . $element['tag'] . ">\n";
			} else {
				if ($element['type'] == 'complete' && $element['value'] == null) {
					$return .= '<' . $element['tag'] . "/>\n";
				} else {
					$value = $element['value'];
					
					if (preg_match('/^H([\d])$/', $element['tag'], $matches)) {
						$count++;
						$h = $matches[1];
						if ($h > $hIndex) {
							$toc .= '<ul>';
							$bullet[$h] = 1;
						} else if ($h < $hIndex) {
							$toc .= "</ul>";
						}
						$hIndex = $h;
						
						
						$slug = Inflector::slug($value);
						$oSlug = $slug;
						$slugCount = 1;
						while (in_array($slug, $slugs)) {
							$slug = $oSlug . '_' . $slugCount++;
						}
						
						$toc .= $this->Html->tag('li',
							$bullet[$h] . '. ' . $this->Html->link($value, $currentUrl . '#' . $slug)
						);
						$bullet[$h]++;
						
						$element['attributes']['id'] = $slug;
						$value .= ' ' . $this->Html->link('Back to top', $currentUrl . '#top', array('class' => 'badge badge-default'));
					}				
					$return .= $this->Html->tag($element['tag'], $value, $element['attributes']);
				}
			}
		}
		$text = $return;
		if ($count >= $options['cutoff']) {
			return $this->Html->div('panel toc',
				$this->Html->div('toc-title panel-heading', 'Content') 
				. $toc
			);
		} else {
			return '';
		}
	}
	
	protected function parseText($str, $options = array()) {
		$str = $this->Html->div('parseWrapper', $str);
		
		$p = xml_parser_create();
		xml_set_character_data_handler($p, array(&$this, 'xmlDataHandler'));
		xml_parse_into_struct($p, $str, $elements, $index);
				
		$return = '';
		foreach ($elements as $element) {
			$element = array_merge(array(
				'value' => null,
				'attributes' => array()
			), $element);
			
			
			if (!empty($options['value'])) {
				$element['value'] = $this->_parseTextValue($element['value'], $options['value']);
			}
			
			if ($element['level'] == 1) {
				$return .= $element['value'];
			} else if ($element['type'] == 'close') {
				$return .= '</' . $element['tag'] . ">\n";
			} else {
				if ($element['type'] == 'complete' && $element['value'] == null) {
					$return .= '<' . $element['tag'] . "/>\n";
				} else {
					$return .= $this->Html->tag($element['tag'], $element['value'], $element['attributes']);
				}
			}
			
		}
		return $return;
	}

	private function xmlDataHandler($parser, $data) {
		$data = str_replace(' ', '&nbsp;', $data);
		return $data;
	}
	
	private function _parseTextValue($value, $options = array()) {
		if (!empty($options['spaceFormat'])) {
			$value = str_replace(' ', '&nbsp;', $value);
		}
		return $value;
	}
}
<?php
class GridHelper extends AppHelper {
	var $name = 'Grid';
	var $helpers = array('Html', 'Layout.Asset');
	
	var $colCount = 0;

	//CSS
	var $colClassPrefix = 'col';
	var $lastClass = 'last';
	
	//Bool
	var $isOpen = false;
	var $isColOpen = false;
	
	function open($class = null, $content = null, $options = array()) {
		$this->__reset();
		$this->isOpen = true;
		$out = '';
		$out .= $this->_comment('Grid Open');
		$out .= $this->Html->div('row-fluid');
		if (!empty($class)) {
			$out .= $this->col($class, $content, $options);
		}
		return $out;
	}
	
	function close() {
		$this->isOpen = false;
		$out = '';
		if ($this->isColOpen) {
			$out .= $this->colClose();
		}
		$out .= "</div>\n";
		$out .= $this->_comment('Grid Closed');
		return $out;
	}
	
	function col($class, $content = null, $options = array()) {
		if (!is_array($options)) {
			$options = array('close' => $options);
		}
		$out = $this->colOpen($class, $options);
		if ($content !== null) {
			$out .= $this->colClose($content);
		}
		if (!empty($options['close'])) {
			$out .= $this->close();
		}
		return $out;
	}
	
	function cols($cols = array(), $close = false) {
		$colCount = count($cols);
		$out = '';
		if (!empty($colCount)) {
			foreach ($cols as $content) {
				if (is_array($content)) {
					list($content, $colOptions) = $content;
				} else {
					$colOptions = array();
				}
				if (is_numeric($colOptions)) {
					$colOptions = array('cols' => $colOptions);
				}
				$colOptions['totalCols'] = $colCount;
				$out .= $this->col(null, $content, $colOptions);
			}
		}
		if ($close) {
			$out .= $this->close();
		}
		return $out;
	}
	
	function colOpen($class, $options = array()) {
		$out = '';
		if (!$this->isOpen) {
			$out .= $this->open();
		}
		$out .= $this->Html->div($this->__parseClass($class, $options));
		$this->isColOpen = true;
		return $out;
	}
	
	function colClose($content = '') {
		$close = false;
		if ($content === true) {
			$content = '';
			$close = true;
		}
		$out = $content . "\n</div>\n";
		$this->isColOpen = false;

		if ($close) {
			$out .= $this->close();
		}
		return $out;
	}
	
	function colContinue($class, $content = null, $options = array()) {
		$out = '';
		if ($this->isColOpen) {
			$out .= $this->colClose();
		}
		$out .= $this->col($class, $content, $options);
		return $out;
	}
	
	function __parseClass($class, $options = array()) {
		if (!empty($options['totalCols'])) {
			$class = (!empty($options['cols']) ? $options['cols'] : 1) . '/' . $options['totalCols'];
		}
		$class = preg_replace('#(([\d]+)/([\d]+))#e', '$this->__getFractionClass($2,$3)', $class);
		if (!empty($options['class'])) {
			$class .= ' ' . $options['class'];
		}
		return $class;
	}
	
	function __getFractionClass($numerator, $denominator) {
		return 'span' . floor($numerator / $denominator * 12);
		
		$fraction = $this->__reduce(array($numerator, $denominator));
		$class = "{$this->colClassPrefix}{$fraction[0]}-{$fraction[1]}";
		if (($this->colCount += ($numerator / $denominator)) >= 1) {
			$class .= ' ' . $this->lastClass;
		}
		return $class;
	}
	
	function __reduce($fraction = array()) {
		list($n, $d) = $fraction;
		for ($f = $n; $f > 0; $f--) {
			$testN = $n / $f;
			$testD = $d / $f;
			if ($testN == round($testN) && $testD == round($testD)) {
				return array($testN, $testD);
			}
		}
		return $fraction;
	}
	
	function __reset() {
		$this->colCount = 0;
	}

	function _comment($text) {
		return "<!-- $text --->";
	}
}
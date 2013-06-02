<?php
class TextGraphHelper extends AppHelper {
	var $name = 'TextGraph';
	var $helpers = array('Layout.Asset','Html');
	
	var $colors = array();
	var $colorsInit = array(
		array(223,27,27),
		array(223,223,27),
		array(27,223,27),
	);
	
	function beforeRender($viewFile) {
		$this->colors = $this->_colorsInit($this->colorsInit);
		
		$this->Asset->css('Layout.text_graphs');
		return parent::beforeRender($viewFile);
	}
	
	function number($val, $options = array()) {
		$options = array_merge(array(
			'min' => 0,
			'max' => $val,
			'reverse' => false,
			'empty' => false,
			'range' => null,
		), $options);
		extract($options);
		
		$color = $this->colorRange($val, $min, $max, $reverse);
		
		if ($empty && !is_numeric($val)) {
			$number = '---';
			$color = '#CCC';
		} else {
			$number = number_format($val, $range);
		}
		
		if (!empty($before)) {
			$number = $before . $number;
		}
		if (!empty($after)) {
			$number .= $after;
		}
		
		return $this->Html->tag(
			'font', 
			$number,
			array('style' => 'color: ' . $color)
		);
	}

	function pieIcon($val, $total = null, $options = array()) {
		if (is_array($total)) {
			$options = $total;
			$total = null;
		}
		if (isset($total)) {
			if ($total === 0) {
				$pct = 0;
			} else {
				$pct = $val / $total * 100;
			}
		} else {
			$pct = $val;
		}
		$imageDivision = 8;
		$pctConverted = $pct * $imageDivision / 100;
		$pctConverted = $pctConverted > 1 ? floor($pctConverted) : ceil($pctConverted);		
		$pie = round($pctConverted * 100 / $imageDivision);
		$isComplete = $pie == 100;
		
		$image = 'icn/16x16/progress_pie/' . $pie . '.png';
		$alt = $pct . '% Complete';
		$class = 'pie-icon ' . ($isComplete ? 'complete' : 'incomplete');
		
		$return = $this->Html->image($image, compact('alt'));
		if (!empty($options['url'])) {
			if (is_numeric($options['url'])) {
				$options['url'] = $this->getCompleteUrl($options['url'], $isComplete);
			}
			$alt = $isComplete ? 'Mark Incomplete' : 'Mark Completed';
			$return = $this->Html->link($return, $options['url'], array('escape' => false, 'title' => $alt));
		}
		return $this->Html->tag('span', $return, compact('class'));
	}
	
	function pct($val, $min = 0, $max = 0, $reverse = false) {
		$color = $this->colorRange($val, $min, $max, $reverse);
		return $this->Html->tag(
			'font', 
			number_format($this->getPct($val, $min, $max) * 100, 2) . '%',
			array('style' => 'color: ' . $color)
		);
	}
	
	function pctChange($currentVal, $startVal) {
		if (empty($startVal)) {
			$pct = 0;
			$class = 'empty';
		} else {
			$diff = $currentVal - $startVal;
			$pct = $diff / $startVal * 100;
		}
		$pct = number_format($pct, 2) . '%';
		if ($pct > 0) {
			$pct = '+' . $pct;
			$class = 'positive';
		} else if ($pct < 0) {
			$class = 'negative';
		}
		return $this->Html->tag('font',  $pct, compact('class'));
	}
	
	function getPct($val, $min = 0, $max = 0) {
		if($val < $min) {
			$pct = 0;
		} else if($val > $max) {
			$pct = 1;
		} else if (($max - $min) != 0) {
			$pct = ($val - $min) / ($max - $min);
		} else {
			$pct = 0;
		}
		return $pct;
	}
	
	function colorTag($tag, $text, $options = array()) {
		$options = array_merge(array(
			'min' => 0,
			'max' => 100,
			'reverse' => false,
			'val' => $text,
		), $options);
		extract($options);
		return $this->Html->tag($tag, $text, array(
			'style' => 'color:' . $this->colorRange($val, $min, $max, $reverse),
		));
	}
	
	function colorRange($val, $min = 0, $max = 100, $reverse=false) {
		if($min > $max) {
			list($min,$max) = array($max,$min);
			$reverse = !$revers;
		}
		
		$colors = $this->colors;
		
		if($reverse) {
			$colors = array_values(array_reverse($colors));
		}
		
		$pct = $this->getPct($val, $min, $max);
		$key = round((count($colors)-1)*$pct);
		return 'rgb('.implode(',',$colors[$key]).')';
	}
	
	function barGraph($good, $bad, $count=35) {
		$total = $good + $bad;
		
		$good = floor($total > 0 ? $count * ($good / $total) : 0);
		$bad = ceil($total > 0 ? $count * ($bad / $total) : 0);
		
		$return = '';
		$return .= $this->Html->div('bar-graph');
		$return .= '<font class="good">' . str_repeat('O', $good) . '</font>';
		$return .= '<font class="bad">' . str_repeat('X', $bad) . '</font>';
		$return .= "</div>\n\n";
		
		return $return;
	}
	
	function divBarGraph($amt, $total = 1, $options = array()) {
		$options = array_merge(array(
			'url' => null,
			'width' => null,
			'disp' => null,
		), $options);
		
		if (empty($total)) {
			$total = 1;
		}
		$pct = $amt / $total;
		$pctDisp = round($pct * 100) . '%';
		$isComplete = $pct >= 1;
		$blankComplete = $pct <= 0;
		
		if (!empty($options['url'])) {
			$url = $this->getCompleteUrl($options['url'], $isComplete);
		} else {
			$url = null;
		}
		
		$attrs = array();
		
		if (!empty($options['width'])) {
			if (is_numeric($options['width'])) {
				$options['width'] .= 'px';
			}
			$attrs['style'] = 'width: '. $options['width'];
		} 

		$class = 'div-bar-graph ' . ($isComplete ? 'complete' : 'incomplete');
		if ($blankComplete) {
			$class .= ' blank-complete';
		}
		if (!empty($options['showPct'])) {
			$options['disp'] = 'percent';
			$class .= ' show-pct';
		}

		$disp = '';
		if (!empty($options['disp'])) {
			if ($options['disp'] == 'percent') {
				$disp = $pctDisp;
			} else if ($options['disp'] == 'numbers') {
				$disp = "$amt / $total";
			}
		}
		if (!empty($disp) && !empty($options['units'])) {
			$disp .= ' ' . $options['units'];
		}
		$disp = $this->Html->tag('font', $disp);

		$return = $this->Html->div('bg');
		$return .= $this->Html->div('amt', '&nbsp;', array('style' => 'width:' . $pctDisp));
		$return .= $disp;
		$return .= "</div>\n";
		
		if (!empty($url)) {
			$return = $this->Html->link(
				$return, 
				$url, array(
					'escape' => false,
					'title' => $pctDisp . ' Complete. ' . ($isComplete ? 'Mark Incomplete' : 'Mark Complete'),
				)
			);
		}
		
		return $this->Html->div($class, $return, $attrs);
	}

	function _colorsInit($colorsInit, $length = 50) {
		$colors = array();
		if(count($colorsInit) > 1) {
			foreach($colorsInit as $k=>$c) {
				if(isset($colorsInit[$k+1])) {
					$count = count($colorsInit)-1;
					$next = $colorsInit[$k+1];
					$this->__colorSpectrum($colors, $c, $next, $length / $count);
				}
			}
		} else {
			$colors = $colorsInit[0];
		}
		return $colors;
	}

	function __colorSpectrum(&$colors, $start, $end, $length) {
	//Finds the range of changing values between two colors
		$change = array();
		foreach($start as $k=>$v) {
			if($start[$k] != $end[$k])
				$change[$k] = 1;
		}
		for($i=0; $i<=$length; $i++) {
			$color = array();
			for($j = 0; $j <= count($start)-1; $j++) {
				if(isset($change[$j])) {
					$color[$j] = round($start[$j] + ($end[$j] - $start[$j]) * ($i / $length));
				} else
					$color[$j] = $start[$j];
			}
			$colors[] = $color;
		}
		return $colors;
	}

	function getCompleteUrl($url = array(), $isComplete = false) {
		if (is_numeric($url)) {
			$url = array($url);
		}
		if (is_array($url)) {
			if (empty($url['action'])) {
				$url['action'] = 'complete';
			}
			$url[1] = round(!$isComplete);
		}
		return $url;
	}

}
?>
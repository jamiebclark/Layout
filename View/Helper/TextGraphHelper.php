<?php
class TextGraphHelper extends AppHelper {
	public $name = 'TextGraph';
	public $helpers = array('Html');
	
/**
 * A full list of color RGB values
 *
 * @var array;
 **/	
	protected $colorRange = array();
	
/**
 * A list of color RGB basic points that will be used to create colorRange
 *
 * @var array;
 **/
	protected $colorRangePoints = array(
		array(223,27,27),
		array(223,223,27),
		array(27,223,27),
	);
	
	public function beforeRender($viewFile) {
		$this->setColorRangeValues();
		$this->Html->css('Layout.text_graphs', null, array('inline' => false));
		return parent::beforeRender($viewFile);
	}
	
	public function number($val, $options = array()) {
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

	public function pieIcon($val, $total = null, $options = array()) {
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
	
	public function pct($val, $min = 0, $max = 0, $reverse = false, $prec = 2) {
		$color = $this->colorRange($val, $min, $max, $reverse);
		return $this->Html->tag(
			'font', 
			number_format($this->getPct($val, $min, $max) * 100, $prec) . '%',
			array('style' => 'color: ' . $color)
		);
	}
	
	public function pctChange($currentVal, $startVal, $round = 2) {
		if (empty($startVal)) {
			$pct = 0;
			$class = 'empty';
		} else {
			$diff = $currentVal - $startVal;
			$pct = $diff / $startVal;
		}
		return $this->pctFormat($pct, ['round' => $round, 'showSign' => true]);
	}
	
/**
 * Displayes a percentage formatted with HTML elements
 * 
 * @param float $pct The percent value
 * @param array $settings Additional formatting settings
 *		- round: The amount of decimals to round the displayed value
 * @return string The formatted HTML 
 **/
	public function pctFormat($pctValue, $settings = []) {
		if (is_numeric($settings)) {
			$settings = ['round' => $settings];
		}
		$settings = array_merge([
			'round' => 2,
			'showSign' => false,
		], $settings);
		extract($settings);
		$class = 'badge badge-pct pct';
		$sign = "";
		$unit = "";

		if (!isset($pctValue)) {
			$displayedValue = '---';
			$class = ' pct-empty';
		} else {
			$value = (float)$pctValue * 100;
			$signValue = abs($value);
			$unit = '%';

			if ($value > 0) {
				$sign = '+';
				$class .= ' pct-positive';
			} else if ($value < 0) {
				$sign = '-';
				$value = abs($value);
				$class .= ' pct-negative';
			}
			$displayedValue = number_format($showSign ? $signValue : $value, $round);
			if ($displayedValue == 0) {
				if ($value > 0) {
					$displayedValue = '<1';
				} else if ($value < 0) {
					$displayedValue = '>-1';
				}
			}
		}
		$value = '';
		if ($showSign) {
			$value = '<span class="pct-sign">' . $sign . '</span>';
		}
		$value .= '<span class="pct-value">' . $displayedValue . '</span>' .
			'<span class="pct-unit">' . $unit . '</span>';

		if (!empty($url)) {
			$value = $this->Html->link($value, $url, ['escape' => false]);
		}
		return $this->Html->tag('span',  $value, compact('class'));
	}
	
	public function getPct($val, $min = 0, $max = 0) {
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
	
	public function colorTag($tag, $text, $options = array()) {
		$options = array_merge(array(
			'min' => 0,
			'max' => 100,
			'reverse' => false,
			'val' => $text,
			'cssProperty' => 'color',
		), $options);
		extract($options);
		return $this->Html->tag($tag, $text, array(
			'style' => $cssProperty . ':' . $this->colorRange($val, $min, $max, $reverse),
		));
	}
	
	public function colorRange($val, $min = 0, $max = 100, $reverse=false) {
		if($min > $max) {
			list($min,$max) = array($max,$min);
			$reverse = !$revers;
		}
		
		$colorRange = $this->getColorRangeValues();
		
		if($reverse) {
			$colorRange = array_values(array_reverse($colorRange));
		}
		
		$pct = $this->getPct($val, $min, $max);
		$key = round((count($colorRange)-1)*$pct);
		return 'rgb('.implode(',',$colorRange[$key]).')';
	}
	
	public function barGraph($good, $bad, $count=35) {
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
	
	public function divBarGraph($amt, $total = 1, $options = array()) {
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
			} else {
				$disp = $options['disp'];
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

	private function createColorRange($colorRangePoints, $length = 50) {
		$colorRange = array();
		if(count($colorRangePoints) > 1) {
			foreach($colorRangePoints as $k => $c) {
				if(isset($colorRangePoints[$k+1])) {
					$count = count($colorRangePoints)-1;
					$next = $colorRangePoints[$k+1];
					$colorRange = $this->addColorSpectrum($colorRange, $c, $next, $length / $count);
				}
			}
		} else {
			$colorRange = $colorRangePoints[0];
		}
		return $colorRange;
	}

/**
 * Finds the range of changing values between two colors
 * 
 * @param array $colorRange The existing range
 * @param array $startColor The RGB array of the start color
 * @param array $endColor The RGB array of the end color
 * @param string $colorCount How many points should be between the colors
 * @return array;
 **/
	private function addColorSpectrum(&$colorRange, $startColor, $endColor, $colorCount) {
		$change = array();
		foreach ($startColor as $k => $v) {
			if($startColor[$k] != $endColor[$k]) {
				$change[$k] = 1;
			}
		}

		for ($i = 0; $i <= $colorCount; $i++) {
			$color = array();
			for ($j = 0; $j <= count($startColor)-1; $j++) {
				if (isset($change[$j])) {
					$color[$j] = round($startColor[$j] + ($endColor[$j] - $startColor[$j]) * ($i / $colorCount));
				} else
					$color[$j] = $startColor[$j];
			}
			$colorRange[] = $color;
		}
		return $colorRange;
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

/**
 * Returns the list of colors associated with a color range
 *
 * @return array;
 **/
	private function getColorRangeValues() {
		if (empty($this->colorRangeValues)) {
			$this->setColorRangeValues();
		}
		return $this->colorRangeValues;
	}

/**
 * Creates a color range
 *
 * @return void;
 **/
	private function setColorRangeValues() {
		if (!empty($this->colorRangePoints)) {
			$this->colorRangeValues = $this->createColorRange($this->colorRangePoints);
		}
	}
}
<?php
class BootstrapHelper extends LayoutAppHelper {
	public $name = 'Bootstrap';
	public $helpers = array('Html');
	
	public function thumbnail($src, $options = array()) {
		$options = array_merge(array(
			'class' => '',
			'url' => false,
			'tag' => 'div',
			'caption' => null,
			'image' => array(),		//Image Options
		), $options);
		extract($options);
		$class = trim($class . ' thumbnail');
		
		$out = $this->Html->image($src, $image);
		if (!empty($caption)) {
			$out .= $caption;
		}
		if (!empty($url)) {
			$out = $this->Html->link($out, $url, array('escape' => false, 'class' => $class));
		} else {
			$out = $this->Html->tag($tag, $out, compact('class'));
		}
		return $out;		
	}

	public function listGroup($listItems, $options = array()) {
		$options = $this->addClass($options, 'list-group');
		$out = '';
		foreach ($listItems as $item => $itemOptions) {
			if (is_numeric($item)) {
				$item = $itemOptions;
				$itemOptions = array();
			}
			$out .= $this->Html->tag('li', $item, $itemOptions);
		}
		return $this->Html->tag('ul', $out, $options);
	}

	public function linkListGroup($listItems, $options = array()) {
		$options = $this->addClass($options, 'list-group');
		$out = '';
		foreach ($listItems as $listItem) {
			$listItem += array(null, array(), array(), null);
			$listItem[2] = $this->addClass($listItem[2], 'list-group-item');
			$out .= "\t\t" . $this->Html->link($listItem[0], $listItem[1], $listItem[2], $listItem[3]) . "\n";
		}
		return $this->Html->tag('div', $out, $options);
	}
}
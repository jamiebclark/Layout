<?php
class BootstrapHelper extends LayoutAppHelper {
	public $name = 'Bootstrap';
	public $helpers = array('Html');
	
	public function thumbnail($src, $options = array()) {
		$options = array_merge(array(
			'class' => '';
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
}
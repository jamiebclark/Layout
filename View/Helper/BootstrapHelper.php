<?php
class BootstrapHelper extends LayoutAppHelper {
	public $name = 'Bootstrap';
	public $helpers = array('Html', 'Form');
	
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
			$itemOptions = $this->addClass($itemOptions, 'list-group-item');
			$out .= $this->Html->tag('li', $item, $itemOptions);
		}
		return $this->Html->tag('ul', $out, $options);
	}

	public function linkListGroup($links, $options = array()) {
		$options = $this->addClass($options, 'list-group');
		$options['tag'] = 'div';
		$options['link']['class'] = 'list-group-item';
		return $this->_linkList($links, $options);
	}

	public function linkBtnGroup($links, $options = array()) {
		$options = $this->addClass($options, 'btn-group');
		$options['tag'] = 'div';
		$options['link']['class'] = 'btn btn-default';
		return $this->_linkList($links, $options);
	}

	public function linkNav($links, $options = array()) {
		$options = $this->addClass($options, 'nav');
		$options['tag'] = 'ul';
		$options['linkWrap'] = array('tag' => 'li');
		return $this->_linkList($links, $options);
	}

	public function linkList($links, $options = array()) {
		$options['tag'] = 'ul';
		$options['linkWrap'] = array('tag' => 'li');
		return $this->_linkList($links, $options);
	}

/**
 * Converts an array of links to HTML
 *
 * @param array $links An array of links, formatted to work with the Html link function
 *	- title
 * 	- url
 * 	- linkOptions
 * 	- onClick
 * @param array $options Additional options to format the list
 * @return string HTML list
 **/
	private function _linkList($links, $options = array()) {
		$options = Hash::merge(array(
			'tag' => null,
			'class' => null,
			'linkWrap' => array(
				'tag' => null,
				'class' => null,
			),
			'link' => array(
				'class' => null,
			)
		), $options);

		$tag = Param::keyCheck($options, 'tag', true);
		$globalLinkOptions = Param::keyCheck($options, 'link', true);
		$linkWrapOptions = Param::keyCheck($options, 'linkWrap', true);

		$out = '';
		foreach ($links as $link) {
			if (is_array($link)) {
				list($linkText, $linkUrl, $linkOptions, $linkClick) = $link + array(null, array(), array(), null);
				$isActive = Param::keyCheck($linkOptions, 'active', true);

				$before = Param::keyCheck($options, 'before', true);
				$after = Param::keyCheck($options, 'after', true);

				$linkOptions['escape'] = false;
				$linkOptions = array_merge((array) $globalLinkOptions, $linkOptions );
				if (empty($linkWrapOptions) && $isActive) {
					$linkOptions = $this->addClass($linkOptions, 'active');
				}
				if (Param::keyCheck($linkOptions, 'postLink', true)) {
					$link = $this->Form->postLink($linkText, $linkUrl, $linkOptions, $linkClick);
				} else {
					$link = $this->Html->link($linkText, $linkUrl, $linkOptions, $linkClick);
				}

				$link = $before . $link . $after;
			}

			if (!empty($linkWrapOptions)) {
				$lwOptions = $linkWrapOptions;
				$lwTag = Param::keyCheck($lwOptions, 'tag', true);
				if ($isActive) {
					$lwOptions = $this->addClass($lwOptions, 'active');
				}
				$link = $this->Html->tag($lwTag, $link, $lwOptions);
			}

			$out .= $link;
		}
		if (!empty($tag)) {
			$out = $this->Html->tag($tag, $out, $options);
		}
		return $out;
	}
}
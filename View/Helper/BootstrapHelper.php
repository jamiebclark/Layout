<?php
App::uses('LayoutAppHelper', 'Layout.View/Helper');
App::uses('Url', 'Layout.Lib');

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
		$options['linkWrap'] = false;

		return $this->_linkList($links, $options);
	}

	public function linkBtnGroup($links, $options = array()) {
		$options = $this->addClass($options, 'btn-group');
		$options['tag'] = 'div';
		$options['link']['class'] = 'btn btn-default';
		$options['linkWrap'] = false;
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

		// Check for active
		if (!empty($options['urlActive'])) {
			$urls = Hash::extract($links, '{n}.1');
			$keyGroups = [
				null, 
				['plugin', 'prefix', 'controller', 'action'],
				['plugin', 'prefix', 'controller', 'action' => 'index'],
				['plugin', 'prefix', 'controller'],
			];
			foreach ($keyGroups as $keys) {
				if (($k = $this->findUrlMatch($urls, $keys)) !== false) {
					$links[$k][2]['active'] = true;
					break;
				}
			}
			unset($options['urlActive']);
		}

		// Output
		$out = '';
		foreach ($links as $k => $link) {
			$isActive = false;
			if (is_array($link)) {
				list($linkText, $linkUrl, $linkOptions, $linkClick) = $link + array(null, array(), array(), null);

				$isActive = Param::keyCheck($linkOptions, 'active', true);

				$before = Param::keyCheck($linkOptions, 'before', true);
				$after = Param::keyCheck($linkOptions, 'after', true);

				if (!empty($linkOptions['dropdown'])) {
					$targetId = 'dropdown' . rand(0,99999);
					$linkOptions['data-toggle'] = 'dropdown';
					$linkOptions['aria-expanded'] = 'false';
					$linkOptions['id'] = $targetId;
					$linkOptions['role'] = 'button';
					$after .= $this->linkList($linkOptions['dropdown'], [
							'class' => 'dropdown-menu',
							'role' => 'menu',
							'aria-labelledby' => $targetId,
						]);
					unset($linkOptions['dropdown']);
				}

				if (!empty($linkOptions['collapse'])) {
					$targetId = 'collapse' . rand(0,99999);
					$linkOptions = $this->addClass($linkOptions, 'dropdown-toggle');
					$linkOptions['data-toggle'] = 'collapse';
					$linkOptions['data-target'] = '#' . $targetId;
					$linkOptions['aria-expanded'] = 'true';
					$after .= $this->linkList($linkOptions['collapse'], [
						'class' => 'collapse nav',
						'role' => 'menu',
						'id' => $targetId,
						'aria-labelledby' => $targetId,
					]);
					unset($linkOptions['collapse']);
				}

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

/**
 * Cycles through an array of url arrays looking to see if it matches the current url array
 *
 * @param array $urls An array of url arrays to check
 * @param array $keys An optional filter to only search by a specific type of keys and / or value
 *		Array(
 *			'controller',			# Limit the search to only compare 'controller' and 'action' keys
 *			'action' => 'index',	# Assume every 'action' value in $urls is set to 'index'
 *		);
 * @return int|bool Either the matching key in urls or false if not found
 **/
	private function findUrlMatch($urls, $keys = []) {
		$replaceUrl = [];
		if (is_array($keys)) {
			// If you pass a value as well as a key, 
			// it will force that value on all your arrays
			foreach ($keys as $k => $v) {
				if (!is_numeric($k)) {
					$replaceUrl[$k] = $v;
					unset($keys[$k]);
					$keys[] = $k;
				}
			}
		}

		$currentUrl = Url::urlArray();
		$defaultKeys = ['controller', 'action'];
		if ($prefix = Url::getPrefix()) {
			$defaultKeys[] = $prefix;
		}
		$default = array_intersect_key($currentUrl, array_flip($defaultKeys));
		$currentUrl = $this->prepareUrlForCompare($currentUrl, $keys);

		foreach ($urls as $k => $url) {
			$url = (array) $replaceUrl + (array) $url + (array) $default;
			if ($this->prepareUrlForCompare($url, $keys) == $currentUrl) {
				return $k;
			}
		}
		return false;
	}

/**
 * Prepares the url array to be compared
 * Sorts it by key order and filters it down to only specific keys
 * 
 * @param array $url The url array to be compared
 * @param array $keys An optional array of keys to intersect
 * @return array;
 **/
	private function prepareUrlForCompare($url, $keys = []) {
		ksort($url);
		if (!empty($keys)) {
			$url = array_intersect_key($url, array_flip($keys));
		}
		return $url;
	}
}
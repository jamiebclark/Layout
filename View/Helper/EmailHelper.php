<?php
App::uses('LayoutAppHelper', 'Layout.View/Helper');
class EmailHelper extends LayoutAppHelper {
	var $name = 'Email';
	
	var $helpers = array(
		'Html',
		'Layout.DisplayText',
	);
	
	function __construct($View, $settings = array()) {
		if (!empty($settings['helpers'])) {
			$this->helpers = array_merge($this->helpers, (array) $settings['helpers']);
		}
		parent::__construct($View, $settings);
	}
	
	function display($text, $format = 'html', $options = array()) {
		$text = $this->DisplayText->text($text, $options);
		$style = Param::keyCheck($options, 'style', true, null);
		
		if (Param::keyCheck($options, 'eval', true)) {
			$text = $this->evalVars($text);
		}
		
		if ($format == 'html') {
			$text = $this->html($text, $style);
		} else if ($format == 'textHtml') {
			$text = $this->textHtml($text);
		} else {
			$text = $this->text($text);
		}
		
		return $text;
	}
	
	function image($url, $options = array()) {
		//Makes sure the image has the aboslute path
		if (substr($url, 0, 1) != '/') {
			$url = Router::url('/img/' . $url, true);
		}
		if (!empty($options['url'])) {
			$options['url'] = Router::url($options['url'], true);
		}
		if (empty($options['style'])) {
			$options['style'] = '';
		}
		$options['style'] .= 'border:0;';
	
		return $this->Html->image($url, $options);
	}
	
	function link($title, $url, $options = array(), $confirm = null) {
		$url = Router::url($url, true);
		return $this->Html->link($title, $url, $options, $confirm);
	}
	
	function evalVars($text) {
		extract($this->viewVars);
		$text = str_replace('"', '\\"', $text);
		eval('$text = "' . $text . '";');
		return $text;
	}
	
	/**
	 * Takes HTML and inserts CSS directly into the tag STYLE
	 *
	 **/
	function html($text, $style = array()) {
		$text = $this->replaceCssWithStyle($text, $style);
		$text = $this->setAbsoluteUrls($text);
		return $text;
	}
	
	function replaceCssWithStyle($text, $style = array()) {
		$tags = array('h1', 'h2', 'h3', 'h4', 'p', 'a', 'blockquote');
		$replace = array();
		foreach ($tags as $tag) {
			if (!empty($style[$tag])) {
				$replace['#(<' . $tag . ')([^>]*)(>)#'] = '$1 style="' . $style[$tag] . '"$2$3';
			}
		}
		return preg_replace(array_keys($replace), $replace, $text);
	}
	
	function setAbsoluteUrls($text) {
		return preg_replace_callback(
			array(
				'@(<a[^>]+href=")([^\"]*)("[^>]*>)@',
				'@(<img[^>]+src=")([^\"]*)("[^>]*>)@',
			),
			function ($matches) {
				return $matches[1] . $this->_url($matches[2]) . $matches[3];
			}, 
			$text
		);
	}

	function setAbsoluteUrls_OLD($text) {
		return preg_replace(
			array(
				'@(<a[^>]+href=")([^\"]*)("[^>]*>)@e',
				'@(<img[^>]+src=")([^\"]*)("[^>]*>)@e',
			),
			'"$1" . Router::url("$2", true) . "$3";', 
			$text
		);
	}
	
	//Formats text for being displayed in a Plain-text email.
	function text($text) {
		$eol = "\r\n";
		
		$text = $this->setAbsoluteUrls($text);
		
		//$text = preg_replace('/[\[\]\{\}]/', '\\\\$0', $text);
		preg_match_all('/<a[\s+]href="([^\"]*)"/', $text, $matches);
		//Unique URLs
		$uniqueUrls = array_flip(array_flip($matches[1]));
		if (!empty($uniqueUrls)) {
			$urlIds = array_combine($uniqueUrls, range(1, count($uniqueUrls)));
		} else {
			$urlIds = array();
		}

		$urlCount = 0;
		$replace = array(
			'/([\{\}\$])/' => '\\$1',
			'@<dd>(.*)<\/dd>@' => '$1' . $eol,
			'@<td>(.*)<\/td>@' => '$1' . $eol,
			'@<tr>(.*)<\/tr>@' => '$1' . $eol,
			
			'/(\<img([^>]+)>)/' => '[IMAGE]',
			'/<a[\s+]href="([^\"]*)"[^>]*>http:(.*)<\/a>/' => '[ $1 ]',
			'/<li>/' => '- ',													//Removes list items
			'@<[\/\!]*?[^<>]*?>@si' => '',									//Removes comments
		);
		$text = preg_replace(array_keys($replace), $replace, $text);

		// Replaces URLs
		$text = preg_replace_callback('/<a[\s+]href="([^\"]*)"[^>]*>(.*)<\/a>/', function($matches) {
			return sprintf('[%1] %2', $urlIds[$matches[1]], $matches[2]); //'"[" . $urlIds["$1"] . "] $2 "',	//
		}, $text);

		$replaceUpper = array(
			'@<h[\d]>(.*)<\/h[\d]>@',	//Replaces titles
			'@<dt>(.*)<\/dt>@',		//Replaces titles
			'@<th>(.*)<\/th>@',		//Replaces titles
		);
		$text = preg_replace_callback($replaceUpper, function($matches) {
			return $eol . $eol . strtoupper($matches[1]) . $eol;
		}, $text);
		
		//Removes additional tags
		$text = strip_tags($text);
		$text = html_entity_decode($text,ENT_QUOTES);
		$text = $this->_linewrap($text, 75, $eol);
		if (!empty($urlIds)) {
			$text .= $eol . $eol . 'References:' . $eol;
			foreach ($urlIds as $url => $id) {
				$text .= $id . ': ' . Router::url($url, true) . $eol;
			}
		}
		$text = preg_replace("/([$eol]{3,})/", $eol . $eol, $text);
		return stripslashes($text);
	}
	
	//Formats text for being displayed in Plain-text emails, but then re-formats to be displayed in an HTML page
	function textHtml($text) {
		return $this->Html->tag('code', nl2br($this->text($text)));
	}
		
	function _linewrap($text, $width, $break = "\n", $cut = false) {
		$array = explode("\n", $text);
		$text = "";
		foreach($array as $key => $val) {
			$text .= wordwrap($val, $width, $break, $cut);
			$text .= "\n";
		}
		return $text;
	}
	
	function loadHelpers($helpers = array()) {
		if (!is_array($helpers)) {
			preg_match('/[a-zA-Z_0-9]+/', $helpers, $helpers);
		}
		if (!empty($helpers)) {
			foreach ($helpers as $helper) {
				$this->_loadHelper($helper);
			}
		}
	}
	
	function _loadHelper($helper) {
		if (empty($this->{$helper})) {
			App::uses($helper, 'Helper');
			$this->helpers[] = $helper;
			$this->{$helper} = $this->_View->loadHelper($helper);
		}
		return $this->{$helper};
	}

	//Creates an absolute URL with a removed base
	private function _url($url) {
		return Router::url($this->removeUrlBase($url), true);
	}
	
	private function removeUrlBase($url) {
		if (is_array($url)) {
			$url['base'] = false;
		} else {
			//If webroot is more than "/", remove it from the beginning
			if ($webroot = substr($this->_View->webroot,0,-1)) {
				if (strpos($url, $webroot) === 0) {
					$url = substr($url, strlen($webroot));
				}
			}
		}
		return $url;
	}

}
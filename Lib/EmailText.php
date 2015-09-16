<?php
App::uses('DisplayText', 'Layout.Lib');

class EmailText {
	protected $DisplayText;

	public static function display($text, $format = 'html', $options = array()) {
		$DisplayText = new DisplayText();

		$text = $DisplayText->text($text, $options);
		$style = Param::keyCheck($options, 'style', true, null);
		
		if (Param::keyCheck($options, 'eval', true)) {
			$text = self::evalVars($text);
		}
		
		if ($format == 'html') {
			$text = self::html($text, $style);
		} else if ($format == 'textHtml') {
			$text = self::textHtml($text);
		} else {
			$text = self::text($text);
		}
		
		return $text;
	}

	public static function evalVars($text) {
		$text = str_replace('"', '\\"', $text);
		eval('$text = "' . $text . '";');
		return $text;
	}
	
	/**
	 * Takes HTML and inserts CSS directly into the tag STYLE
	 *
	 **/
	public static function html($text, $style = array()) {
		$text = self::replaceCssWithStyle($text, $style);
		$text = self::setAbsoluteUrls($text);
		return $text;
	}
	
	public static function replaceCssWithStyle($text, $style = array()) {
		$tags = array('h1', 'h2', 'h3', 'h4', 'p', 'a', 'blockquote');
		$replace = array();
		foreach ($tags as $tag) {
			if (!empty($style[$tag])) {
				$replace['#(<' . $tag . ')([^>]*)(>)#'] = '$1 style="' . $style[$tag] . '"$2$3';
			}
		}
		return preg_replace(array_keys($replace), $replace, $text);
	}
	
	public static function setAbsoluteUrls($text) {
		return preg_replace_callback(
			array(
				'@(<a[^>]+href=")([^\"]*)("[^>]*>)@',
				'@(<img[^>]+src=")([^\"]*)("[^>]*>)@',
			),
			function ($matches) {
				return $matches[1] . self::_url($matches[2]) . $matches[3];
			}, 
			$text
		);
	}

	public static function setAbsoluteUrls_OLD($text) {
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
	public static function text($text) {
		$eol = "\r\n";
		
		$text = self::setAbsoluteUrls($text);
		
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
		$text = self::_linewrap($text, 75, $eol);
		if (!empty($urlIds)) {
			$text .= $eol . $eol . 'References:' . $eol;
			foreach ($urlIds as $url => $id) {
				$text .= $id . ': ' . Router::url($url, true) . $eol;
			}
		}
		$text = preg_replace("/([$eol]{3,})/", $eol . $eol, $text);
		return stripslashes($text);
	}

	public  static function _linewrap($text, $width, $break = "\n", $cut = false) {
		$array = explode("\n", $text);
		$text = "";
		foreach($array as $key => $val) {
			$text .= wordwrap($val, $width, $break, $cut);
			$text .= "\n";
		}
		return $text;
	}

	
	public static function removeUrlBase($url) {
		if (is_array($url)) {
			$url['base'] = false;
		} else {
			//If webroot is more than "/", remove it from the beginning
			if ($webroot = substr(Router::getRequest()->webroot,0,-1)) {
				if (strpos($url, $webroot) === 0) {
					$url = substr($url, strlen($webroot));
				}
			}
		}
		return $url;
	}


// Creates an absolute URL with a removed base
	private static function _url($url) {
		return Router::url(self::removeUrlBase($url), true);
	}
}
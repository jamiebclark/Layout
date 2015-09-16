<?php
/**
 * Handles text being displayed in message boards / blogs
 *
 **/
App::uses('Param', 'Layout.Lib');
App::uses('TextCleanup', 'Layout.Lib');
App::uses('Markup', 'Layout.Lib');

App::uses('LayoutAppHelper', 'Layout.View/Helper');


class DisplayText {

	public $valid_tag_exp = ':A-Za-z0-9';	//Acceptable tag characters
	public $allowableTags = '<br><hr><strong><em>';
	
	public $constants = array();
	
	private $_textOptions = array(); 	//Stores options when buffering output
	private $_textMethods = array();

	public function __construct($options = null) {
		if (!empty($options['constants'])) {
			$this->addConstant($options['constants']);
		}
		if (empty($this->_textMethods)) {
			$this->_textMethods = [];
		}

		if (!empty($options['wikiModel'])) {
			Markup::setWikiModel($options['wikiModel']);
		}

		// Default Text Methods
		$Helper = $this;
		$this->registerTextMethod('format', [$this, 'stripSpecialChars']);
		$this->registerTextMethod('format', [$this, 'addConstants']);

		$this->registerTextMethod('format', [$this, 'smartFormat']);

		$this->registerTextMethod('tabs', ['Markup', 'replaceTabs']);

		$this->registerTextMethod('urls', ['Markup','setLinks'], ['shrinkUrls']);
		$this->registerTextMethod('smileys', [$this, 'parseSmileys']);
		$this->registerTextMethod('first', [$this, 'firstParagraph']);
		
		$this->registerTextMethod('striphtml', function($text) use ($Helper) {
			return strip_tags($text, $Helper->allowableTags);
		});

		$this->registerTextMethod('truncate', [$this, 'truncate'], ['truncate']);
		$this->registerTextMethod('surround', function($text, $surround) {
			return "$surround$text$surround";
		}, 'surround');

		$this->registerTextMethod('nl2br', array($this, 'smartNl2br'), function ($options) {
			return [['multiNl' => $options['multiNl'], 'nlPad' => $options['nlPad']]];
		});

		$this->registerTextMethod('php', [$this, 'evalPhp']);
	}
	
/**
 * Sets all markup formatting
 *
 **/
	public function smartFormat($text) {
		return Markup::set($text);
	}

/**
 * Registers a new text method to be called on the text() function
 *
 * @param string $flag A reference name to group the method under
 * @param Array|Callable $method The method to be called
 * @param Array|Callable $args The list of arguments, or a function to generate the list of arguments
 * @param string|Boolean $positionRule A rule to dictate where the method will be placed [first, last, before, after]
 * @param string|Boolean $positionFlag A flag reference if the position rule is before or after
 * @return void
 **/
	public function registerTextMethod($flag, $method, $args = [], $positionRule = false, $positionFlag = false) {
		$method = [$method, $args];
		//$key = serialize($method);
		$flags = array_keys($this->_textMethods);
		$flagPos = array_flip($flags);
		$pos = null;
		if (empty($this->_textMethods[$flag]) && !empty($positionRule)) {
			if ($positionRule == 'first' || ($positionRule == 'before' && empty($positionFlag))) {
				$pos = 0;
			} else if ($positionRule == 'last' || ($positionRule == 'after' && empty($positionFlag))) {
				$pos = null;
			} else if ($positionRule == 'before' || $positionRule == 'after') {
				$foundFlagPos = [];
				if (is_array($positionFlag)) {
					foreach ($positionFlag as $k => $f) {
						if (!empty($flagPos[$f])) {
							$foundFlagPos[] = $flagPos[$f];
							unset($positionFlag[$k]);
						}
					}
					if (!empty($positionFlag)) {
						throw new Exception ('Could not location position to add new text method to displayText Helper');
					}
				} else {
					if (!empty($flagPos[$positionFlag])) {
						$foundFlagPos[] = $flagPos[$positionFlag];
					}
				}
				if ($positionRule == 'before') {
					$pos = !empty($foundFlagPos) ? min($foundFlagPos) : 0;
				} else if ($positionRule == 'after') {
					$pos = !empty($foundFlagPos) ? max($foundFlagPos) + 1 : 1;
				}
			}
		} 

		if (isset($pos)) {
			$this->_textMethods = array_merge(
				array_slice($this->_textMethods,0,$pos),
				[$flag => [$method]],
				array_slice($this->_textMethods,$pos)
			);
		} else {
			$this->_textMethods[$flag][] = $method;
		}
	}

	public function addConstant($find, $replace = null) {
		if (is_array($find)) {
			$this->constants = array_merge($this->constants, $find);
		} else {
			$this->constants[$find] = $replace;
		}
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
		$options = array_merge([ 
			'html' => true,						// Allow HTML output
			'php' => false,						// Evaluate PHP
			'ascii' => !empty($_GET['ascii']),	// Output ASCII value for each character
			'multiNl' => true,					// Allow multiple new lines
			'nlPad' => true, 					// Pad each new line
			'stripSpecialChars' => true,		// Strip unneccessary characters
			'first' => false, 					// Only get the first paragrah
			'before' => '',
			'after' => '',
			'div' => null,
			'tag' => null,
			'class' => null,
			'truncate' => null,
			'tabs' => false,
		], $options);

		$options['striphtml'] = $options['html'] === false;
			
		// Legacy term for truncate		
		if ($fragment = Param::keyCheck($options, 'fragment', true)) {
			$options['truncate'] = $fragment;
		}

		//$this->smartNl2br($text, ['multiNl' => $options['multiNl'], 'nlPad' => $options['nlPad']]);

		$text = $this->_renderTextMethods($text, $options);
		return $text;
	}
	
	//Evaluates any PHP included in the text.
	//Only use this if you trust the content creator!
	public function evalPhp($text) {
		extract($this->viewVars);
		ob_start();
		//debug($text);
		eval("?>$text");
		$text = ob_get_clean();
		return $text;
	}
	
	//Starts collecting buffer to output as text
	public function textStart($options = array()) {
		$this->_textOptions = $options;
		ob_start();
	}
	
	//Ends buffer collection and outputs the stored buffer
	public function textEnd() {
		$out = ob_get_clean();
		$options = $this->_textOptions;
		$this->_textOptions = array();
		return $this->text($out, $options);
	}
	
	public function quote($quote, $author = null, $options = array()) {
		$text = $this->text($quote);
		$options - array_merge(array(
			'class' => '',
			'url' => null,
		), $options);
		extract($options);

		$class .= 'layout-quote';
		if (!empty($author)) {
			$text .= "<small>$author</small>";
		}
		if (isset($url)) {
			$text = sprintf('<a href="%s">%s</a>', $url, $text);
		}
		return sprintf('<blockquote class="%s">%s</blockquote>', $class, $text);
	}
	
/**
 * String helper function to convert new line characters to HTML line breaks
 * 
 * @param string $str The string being processed
 * @param array $options Additional parameters
 * 	- multiNl : If false, removes all multiple new lines
 *	- nlPad : adds additional endlines after existing endlines to pad paragraphs
 * @return string Newly processed string
 **/
	function smartNl2br($str, $options = array()) {
		$preserve = array();
		$preserveMatches = array(
			'@<\?php(.+?)\?>@is',
			'@<pre>(.+?)</pre>@is'
		);
		foreach ($preserveMatches as $match) {
			if (preg_match_all($match, $str, $matches)) {
				foreach ($matches[0] as $k => $match) {
					$key = '###PRESERVE' . $k . '###';
					$preserve[$key] = $match;
				}
				$str = str_replace($preserve, array_keys($preserve), $str);
			}
		}
		
		$nlPad = Param::keyCheck($options, 'nlPad', true);
		if ($nlPad || Param::falseCheck($options, 'multiNl', true)) {
			//Strips multiple newlines in a row
			$str = preg_replace('/[\r\n]{3,}/', "\n", $str);
		}
		if ($nlPad) {
			$str = preg_replace('/[\r\n]+/', str_repeat("\r\n", $nlPad + 1), $str);
		}
		$str = nl2br(html_entity_decode($str,ENT_QUOTES));
		$non_break = array('h[\d]+','br','p','li','ul','hr','div', 'dt', 'dd', 'dl', 'blockquote', 't[a-z]+');
		foreach($non_break as $nb) {
			$regexps[] = '/(<[\/]*'.$nb.'>)([\s]*<br[^>]*>)*/is';
		}
		$str = preg_replace($regexps,'$1',$str);

		// Replaces anything that needed to be preserved		
		if (!empty($preserve)) {
			$str = str_replace(array_keys($preserve), $preserve, $str);
		}

		// Trims final br
		$str = trim($str);

		$br = '<br />';
		if (substr($str, -strlen($br)) == $br) {
			$str = substr($str, 0, -strlen($br));
		}
		return $str;
	}
	
	/**
	 * Removes curly quotes and emdashes from a string
	 *
	 **/
	function stripSpecialChars($str) {
		$str = TextCleanup::ms($str);
		return $str;
	}
	
	function addConstants($str) {
		if (!empty($this->constants)) {
			$str = str_replace(array_keys($this->constants), $this->constants, $str);
		}
		return $str;
	}
	
	
	
	
/**
 * Strips out certain tags
 *
 **/
	function shortenStripTags($str) {
		$strip = array(
			'#<object(.*?)</object>#',
			'#<embed(.*?)</embed>#'
		);
		$str = preg_replace($strip, '', $str);
		return $this->closeOpenedTags($str);
	}
	
	function parseHref($str, $shrink = true) {
		//Emails
		$str = preg_replace('/[\n ]([a-zA-Z0-9&\-_.]+@[a-zA-Z0-9&\-_.]+)/',' <a href="mailto:$1">$1</a>',$str);
		//Links
		$str = preg_replace('/(^|[\n ])(((www|ftp)\.[^ ,""\s<\/]*)[^ ,""\s<]*)/',' <a href="http://$2">' . ($shrink ? '[$3]' : 'http://$2') . '</a>',$str);
		$str = preg_replace('/(^|[\n ])([\w]+?:\/\/([^ ,""\s<\/]*)[^ ,""\s<]*)/',' <a href="$2">' . ($shrink ? '[$3]' : '$2') . '</a>',$str);
		return $str;
	}
	
	function parseSmileys($str) {
		//Smiley Faces
		$smiley_dir = '/img/emoticons/';
		$smileys = array(':)' => 'smiley.gif',':('=>'sad.gif');
		foreach ($smileys as $smile => $link) {
			$smiley_new['/'.$this->regexpEsc($smile).'/'] = '<img src="'.$smiley_dir.$link.'"/>';
		}
		$smileys = $smiley_new;
		$str = preg_replace(array_keys($smileys),$smileys,$str);
		return $str;
	}

/**
 * Taken from CakePHP 1.2 Flay Class http://api12.cakephp.org/view_source/flay/#line-270
 * Return a fragment of a text, up to $length characters long, with an ellipsis after it.
 *
 * @param string $text Text to be truncated.
 * @param integer $length Max length of text.
 * @param string $ellipsis Sign to print after truncated text.
 * @return string Fragment
 * @access public
 **/
	public function truncate($text, $length, $options = array()) {
		if (is_array($length)) {
			list($length, $options) = $length + [null, null];
		}

		if (empty($length)) {
			return $text;
		}

		if (!is_array($options)) {
			$options = array('ellipsis' => $options);
		}
		$options = array_merge(array(
			'ellipsis' => '...',
			'moreText' => 'More',
			'url' => false,
		), $options);
		extract($options);
		if ($url) {
			$ellipsis .= ' ' . '<a href="'. Router::url($url) . '">' . $moreText . '</a>';
		}
		
		$soft = $length - 10;
		$hard = $length + 10;
		$rx = '/(.{' . $soft . ',' . $hard . '})[\s,\.:\/="!\(\)<>~\[\]]+.*/';

		if (preg_match($rx, $text, $r)) {
			$out = $r[1];
		} else {
			$out = substr($text, 0, $length);
		}

		$out = $out . (strlen($out) < strlen($text) ? $ellipsis : null);
		return $this->closeOpenedTags($out);
	}
	//Legacy term
	public function fragment($text, $length, $options = array()) {
		return $this->truncate($text, $length, $options);
	}

	
	public function cash($number, $round = null) {
		$number = html_entity_decode($number);
		if (empty($number)) {
			$number = 0;
		}
		return '$' . number_format($number, $round !== false && ($round || $number == round($number)) ? 0 : 2);
	}

	public function cashLabel($cash, $positive = true, $round = null) {
		$class = 'label ';
		if ($cash == 0) {
			$class .= 'label-default';
		} else {
			$class .= $positive ? 'label-success' : 'label-danger';
		}
		return '<span class="' . $class . '">' . $this->cash($cash, $round) . '</span>';
	}

	
	function positiveNumber($number, $options = array()) {
		$class = $number > 0 ? 'positive' : 'negative';
		$format = Param::keyCheck($options, 'format', true, null);
		
		if ($format == 'cash') {
			$number = $this->cash($number);
		} else {
			$number = number_format($number);
		}
		return '<font class="' . $class . '">' . $number . '</font>';
	}

	function regexpEsc($term) {
	//Takes characters that are reserved in regexp and escapes them with a \
		$esc = '.[]{}()/\\:';
		$newTerm = '';
		for($i=0; $i<strlen($term);$i++) {
			$newTerm .= strstr($esc,$term{$i}) ? '\\'.$term{$i} : $term{$i};
		}
		return $newTerm;
	}

	function stripImages($body) {
		return preg_replace('/[\[<]Photo [^>]+[\]>]/','',$body);
	}
	
	public function firstParagraph($str) {
	//Returns only the first paragraph of a string of text
		$str = str_replace(array('<br>','<br/>','<BR>','<BR/>','<br />'),"\n",$str);
		$ps = explode("\n",$str);
		$return = '';
		while(count($ps) > 0 && $return == '') {
			$return = array_shift($ps);
		}
		return $return;
	}
	
	function closeOpenedTags($text) {
		//Close unclosed html tags
		if(preg_match_all("|(<([\w]+)[^>\/]*>)|", $text, $aBuffer)) {
			if(!empty($aBuffer[1])) {
				
				preg_match_all("|</([a-zA-Z]+)>|", $text, $aBuffer2);
				if(count($aBuffer[2]) != count($aBuffer2[1])) {
					$closing_tags = array_diff($aBuffer[2], $aBuffer2[1]);
					$closing_tags = array_reverse($closing_tags);
					foreach($closing_tags as $tag) {
						$text .= '</'.$tag.'>';
					}
				}
			}
		}
		return $text;
	}
	
	function parseTags($str) {
		return preg_replace('/([^'.$this->valid_tag_exp.']*)(['.$this->valid_tag_exp.']+)([^'.$this->valid_tag_exp.']*)/','#$2, ',$str);
	}
	
	function parseTagsLinks($str) {
		$regexp = '/[\s]*(['.$this->valid_tag_exp.']+)[^,]*/';
		$replace = ' #<a href="'.$this->blog_href.'?'.$this->cat_qstring.'&amp;tags=$1">$1</a>';
		return preg_replace($regexp,$replace,$str);
	}
	
	protected function dedupArray(&$array) {
		if(is_array($array)) {
			foreach($array as $k=>$v) {
				$switch[$v] = 1;
			}
			$array = array_keys($switch);
		}
		return $array;
	}
	
	protected function arrayStripEmpty(&$array,$trim=false) {
		if (is_array($array)) {
			foreach ($array as $k=>$v) {
				if ($trim) {
					$v = trim($v);
				}
				if ($v == '') {
					unset($array[$k]);
				} else {
					$array[$k] = $v;
				}
			}
		}
		return $array;
	}
	
	private function _asciiDebug($text) {
		$return = array();
		for ($i = 0; $i < strlen($text); $i++) {
			$c = $text{$i};
			$return[] = array($c, ord($c));
		}
		debug($return);
	}
	
	private function _renderTextMethods($text, $options = []) {
		foreach ($this->_textMethods as $flag => $flagMethods) {
			if ($flag === false || (isset($options[$flag]) && $options[$flag] === false)) {
				continue;
			}
			foreach ($flagMethods as $vars) {
				list($method, $args) = $vars;			
				$passArgs = [$text];
				if (is_callable($args)) {
					if ($calledArgs = $args($options)) {
						$passArgs += $calledArgs;
					}
				} else if (!empty($args)) {
					if (!is_array($args)) {
						$args = [$args];
					}
					foreach ($args as $arg) {
						$passArgs[$arg] = isset($options[$arg]) ? $options[$arg] : null;
					}
				}

				if (is_array($method) && !is_object($method[0])) {
					$text = forward_static_call_array($method, $passArgs);
				} else {
					$text = call_user_func_array($method, $passArgs);
				}
			}
		}
		return $text;
	}
}
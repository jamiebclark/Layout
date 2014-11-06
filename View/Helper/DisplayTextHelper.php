<?php
/**
 * Handles text being displayed in message boards / blogs
 *
 **/
App::uses('Param', 'Layout.Lib');
App::uses('TextCleanup', 'Layout.Lib');
App::uses('Markup', 'Layout.Lib');

App::uses('LayoutAppHelper', 'Layout.View/Helper');


class DisplayTextHelper extends LayoutAppHelper {
	public $helpers = array(
		'CakeAssets.Asset',
		'Layout.Grid',
		'Html', 
		'Layout.Layout', 
		'Layout.Iconic'
	);
	public $valid_tag_exp = ':A-Za-z0-9';	//Acceptable tag characters
	public $allowableTags = '<br><hr><strong><em>';
	
	public $constants = array();
	
	private $_textOptions = array(); 	//Stores options when buffering output
	private $_textMethods = array();

	public function __construct(View $View, $options = null) {
		if (!empty($options['constants'])) {
			$this->addConstant($options['constants']);
		}
		if (empty($this->_textMethods)) {
			$this->_textMethods = [];
		}

		parent::__construct($View, $options);

		// Default Text Methods
		$Helper = $this;
		$this->registerTextMethod('format', [$this, 'smartFormat']);
		$this->registerTextMethod('format', [$this, 'stripSpecialChars']);
		$this->registerTextMethod('format', [$this, 'addConstants']);

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
				// debug(compact('text', 'flag'));
			}
		}
		return $text;
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
	function text($text, $options = array()) {
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
		], $options);

		$options['striphtml'] = $options['html'] === false;
			
		// Legacy term for truncate		
		if ($fragment = Param::keyCheck($options, 'fragment', true)) {
			$options['truncate'] = $fragment;
		}

		//$this->smartNl2br($text, ['multiNl' => $options['multiNl'], 'nlPad' => $options['nlPad']]);

		if (empty($text) && !empty($options['empty'])) {
			$this->addClass($options, 'empty');
			$text = $options['empty'];
		}
		
		if (!empty($options['div'])) {
			$options['tag'] = 'div';
			$options['class'] = $options['div'];
		}
		if (!empty($options['tag']) || !empty($options['class'])) {
			$tag = !empty($options['tag']) ? $options['tag'] : 'div';
			$attrs = array();
			if (!empty($options['class'])) {
				$attrs['class'] = $options['class'];
			}
			$before = $this->Html->tag($tag, null, $attrs);
			$after = "</$tag>\n";
		} else {
			list($before, $after) = array('', '');
		}
		
		
		/*
		if (Param::keyCheck($options, 'php')) {
			$text = $this->evalPhp($text);
		}
		*/

		$text = $this->_renderTextMethods($text, $options);

		if (Param::falseCheck($options, 'columns') !== false) {
			$text = $this->parseColumns($text, compact('before', 'after'));
		} else {
			$text = $before . $text . $after;
		}

		//debug(count($this->_textMethods));

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
	
	function quote($quote, $author = null, $options = array()) {
		$text = $this->text($quote);
		$options = $this->addClass($options, 'layout-quote');
		if (!empty($author)) {
			$text .= $this->Html->tag('small', $author);
		}
		if (isset($options['url'])) {
			$text = $this->Html->link($text, $options['url'], array('escape' => false));
		}
		return $this->Html->tag('blockquote', $text, array('class' => $options['class']));
	}
	
	/**
	 * Generates a table of formatting commands and their result
	 *
	 **/
	function cheatSheet($collapse = false) {
		$out = $this->Html->tag('h2', 'Text Formatting Cheat Sheet');
		if (!empty($this->constants)) {
			$out .= $this->Html->tag('h3', 'Constants');
			$out .= 'These constants will be updated from year to year. Using them will keep text automatically updated.';
			$rows = array();
			foreach ($this->constants as $constant => $value) {
				$rows[] = array($this->Html->tag('code', $constant),$value);
			}
			$table = $this->Html->tableHeaders(array('You type:', 'It displays:'));
			$table .= $this->Html->tableCells($rows, array('class' => 'altrow'));
			$out .= $this->Html->tag('table', $table, array('class' => 'displaytext-cheatsheet-constants'));
			$out .= '<hr/>';
		}
		
		
		$out .= $this->Html->tag('h3', 'Style Shortcuts');
		$format = array(
			'=Heading 1=',
			'==Heading 2==',
			'===Heading 3===',
			'====Heading 4====',
			"''Italic (two single-quotes)''",
			"'''Bold (three single-quotes)'''",
			'[http://google.com Link text comes right after address]',
			'""Quoted text surrounded by two double-quotes""',
			'"""Quoted text (with quotes) surrounded by three double-quotes"""',
			"\n- Unordered List Item 1\r\n- Unordered List Item 2\r\n- Unordered List Item 3\r\n",
			"\n1. Ordered List Item 1\r\n2. Ordered List Item 2\r\n3. Ordered List Item 3\r\n",
		);
		$rows = array();
		foreach ($format as $line) {
			$rows[] = array($this->Html->tag('pre', $line), $this->smartFormat($line));
		}
		$table = $this->Html->tableHeaders(array('You type:', 'It displays:'));
		$table .= $this->Html->tableCells($rows, array('class' => 'altrow'));
		$out .= $this->Html->tag('table', $table, array('class' => 'displaytext-cheatsheet-shortcuts'));
		
		$out = $this->Html->div('displaytext-cheatsheet', $out);
		if ($collapse) {
			$out = $this->Layout->toggle($collapse, null, 'DisplayText Cheat Sheet');
		}
		return $out;
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
		if (preg_match_all( '@<\?php(.+?)\?>@is', $str, $matches)) {
			foreach ($matches[0] as $k => $match) {
				$key = '###PRESERVE' . $k . '###';
				$preserve[$key] = $match;
			}
			$str = str_replace($preserve, array_keys($preserve), $str);
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
		$non_break = array('h[\d]+','br','p','li','ul','hr','div','t[a-z]+');
		foreach($non_break as $nb) {
			$regexps[] = '/(<[\/]*'.$nb.'>)([\s]*<br[^>]*>)*/is';
		}
		$str = preg_replace($regexps,'$1',$str);
		
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
	 * Sets all markup formatting
	 *
	 **/
	public function smartFormat($text) {
		$webroot = substr($this->_View->webroot,0,-1);
		return Markup::set($text, compact('webroot') + array(
			'wikiModel' => $this->wikiModel,
		));
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
	
	function parseColumns($str, $options = array()) {
		$options = array_merge(array(
			'before' => '',
			'after' => '',
			'tag' => 'div',
			'class' => 'text-column',
			'padding' => 1, //%
		), $options);
		extract($options);
		
		$return = $str;
		
		$columns = explode('<COLUMN', $return);
		if (count($columns) > 1) {
			$return = '';

			$columnVals = array();
			$columnCount = 0;
			
			foreach ($columns as $column) {
				//Find Attrs
				preg_match('/^[\s]*([^<>]*)>(.*)/sm', $column, $matches);
				if (!empty($matches)) {
					//debug(compact('column', 'matches'));
					$attrs = $matches[1];
					$column = $matches[2];
				} else {
					$attrs = null;
				}
				$width = is_numeric($attrs) ? $attrs : 1;
				$column = Markup::trimBreaks($column);
				if (empty($column)) {
					continue;
				}
				$columnCount += $width;
				$columnVals[] = array(
					'text' => $column,
					'width' => $width,
				);
			}
			
			$totalColumns = count($columnVals) - 1;
			$return .= $this->Grid->open();
			foreach ($columnVals as $key => $col) {
				$class = "{$col['width']}/$columnCount";
				$return .= $this->Grid->col($class, $before . $col['text'] . $after);
			}
			$return .= $this->Grid->close();
		} else {
			$return = $before . $return . $after;
		}
		return $return;
	}
	
	
	
	function shortenStripTags($str) {
	//If the blog article is being shortened, it strips out certain tags
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
		$smileys = array(':)'=>'smiley.gif',':('=>'sad.gif');
		foreach ($smileys as $smile=>$link) {
			$smiley_new['/'.$this->regexpEsc($smile).'/'] = '<img src="'.$smiley_dir.$link.'"/>';
		}
		$smileys = $smiley_new;
		$str = preg_replace(array_keys($smileys),$smileys,$str);
		return $str;
	}

	/*
	* Taken from CakePHP 1.2 Flay Class http://api12.cakephp.org/view_source/flay/#line-270
	* Return a fragment of a text, up to $length characters long, with an ellipsis after it.
	*
	* @param string $text Text to be truncated.
	* @param integer $length Max length of text.
	* @param string $ellipsis Sign to print after truncated text.
	* @return string Fragment
	* @access public
	*/
	function truncate($text, $length, $options = array()) {
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
			$ellipsis .= ' ' . $this->Html->link($moreText, $url);
		}
		
		$soft = $length - 5;
		$hard = $length + 5;
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

	
	function cash($number, $round = null) {
		$number = html_entity_decode($number);
		if (empty($number)) {
			$number = 0;
		}
		return '$' . number_format($number, $round !== false && ($round || $number == round($number)) ? 0 : 2);
	}
	
	function positiveNumber($number, $options = array()) {
		$class = $number > 0 ? 'positive' : 'negative';
		$format = Param::keyCheck($options, 'format', true, null);
		
		if ($format == 'cash') {
			$number = $this->cash($number);
		} else {
			$number = number_format($number);
		}
		return $this->Html->tag('font', $number, compact('class'));
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
	
	function insertImages($body, $blog_id, $use_thumb = false) {
	/*
		$query = 'SELECT * FROM '.$this->blog_image_table.' WHERE '.$this->blog_table_id.' = "'.$blog_id.'"';
		if($_GET['blog_debug'])
			print "\n$query\n";
		$result = mysql_query($query);
		if(!$result)
			return $body;
		$dir = $use_thumb ? $this->thumb_dir : $this->img_dir;
		
		while($row = mysql_fetch_assoc($result)) {
			$img = '<img';
			if(!$use_thumb) {
				if($row['width'] >0)
					$img .= ' width="'.$row['width'].'"';
				if($row['height'] >0)
					$img .= ' height="'.$row['height'].'"';
			}
			if($row['align'] == 'center')
				$img .= ' style="text-align:center;"';
			if($row['align'] == 'left')
				$img .= ' style="float:left;margin:.5em 1em 1em .5em;vertical-align:text-top;"';
			if($row['align'] == 'right')
				$img .= ' style="float:right;margin:.5em .5em 1em 1em;vertical-align:text-top;"';
			$img .= ' src="'.$this->page_root.$dir.$row['filename'].'"';
			$img .= ' alt="Image '.$row['uid'].'"';
			$img .= '/>';			
			//$images['/[\[<]Photo '.$row['uid'].'[\]>]/'] = $img;
			$images['<Photo '.$row['uid'].'>'] = $img;
			$images['[Photo '.$row['uid'].']'] = $img;
		}
		if(count($images)>0) {
			//$body = preg_replace(array_keys($images),$images,$body);
			$body = str_replace(array_keys($images),$images,$body);
		}
			
		if($_GET['blog_debug']) {
			print_r($images);
			print "BODY:\n\n\n$body";
		}
		return $body;
	*/
	}
	
	function insertBlogList($str, $blog_id=false) {
		/*
	//Allows within the copy, a user to insert a blog list
		preg_match_all('#<List ([^>]*)>#',$str,$matches);
		$replace = array();
		if(count($matches[0]) > 0) {
			foreach($matches[0] as $k => $str_find) {
				$blog = clone $this;
				$attr_str = $matches[1][$k];
				$attrs = array();
				preg_match_all('#([A-Za-z0-9]+):([^\s]+)#',$attr_str,$match_attrs);
				if(is_array($match_attrs)) {
					foreach($match_attrs[1] as $ak=>$av) {
						$attrs[$av] = $match_attrs[2][$ak];
					}
					$lim = round($attrs['lim']);
					$cats = explode(',',$attrs['cats']);
					if(count($cats)>0)
						$this->Blog->cats = $cats;
					$tags = explode(',',$attrs['tags']);
					if(count($tags)>0)
						$this->Blog->tags = $tags;
				}
				if($blog_id)
					$this->Blog->user_wheres[] = 'A.id <> "'.$blog_id.'"';
					
				$this->Blog->build_wheres();
				$list = $this->Blog->blog_list($lim);
				$txt = '<h3><a href="?'.$this->Blog->qstring.'">Article List</a></h3>';
				if($list) {
					$txt .= $list->create();
					$txt .= '<p style="text-align:right;"><a href="?'.$this->Blog->qstring.'">Read All</a></p>';
				}
				$replace[$str_find] = '<div class="sub_list">'.$txt.'</div>';
			}
			if(count($replace)>0) 
				$str = str_replace(array_keys($replace),$replace,$str);
		}
		return $str;
		*/
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
	
	public function tableOfContents(&$text, $options = array()) {
		$options = array_merge(array(
			'cutoff' => 3,
		), $options);
		
		$text = $this->Html->div('parseWrapper', $text);
		
		$p = xml_parser_create();
		xml_set_character_data_handler($p, array(&$this, 'xmlDataHandler'));
		xml_parse_into_struct($p, $text, $elements, $index);
		
		$slugs = array();
		
		$return = '';
		$hIndex = 0;
		$toc = '';	//Table of Contents
		$bullet = array();
		$url = Url::urlArray() + array('base' => false);
		unset($url['#']);
		$currentUrl = Router::url($url);
		$count = 0;
		foreach ($elements as $element) {
			$element = array_merge(array(
				'value' => null,
				'attributes' => array()
			), $element);
			
			
			if (!empty($options['value'])) {
				$element['value'] = $this->_parseTextValue($element['value'], $options['value']);
			}
			
			if ($element['level'] == 1) {
				$return .= $element['value'];
			} else if ($element['type'] == 'close') {
				$return .= '</' . $element['tag'] . ">\n";
			} else {
				if ($element['type'] == 'complete' && $element['value'] == null) {
					$return .= '<' . $element['tag'] . "/>\n";
				} else {
					$value = $element['value'];
					
					if (preg_match('/^H([\d])$/', $element['tag'], $matches)) {
						$count++;
						$h = $matches[1];
						if ($h > $hIndex) {
							$toc .= '<ul>';
							$bullet[$h] = 1;
						} else if ($h < $hIndex) {
							$toc .= "</ul>";
						}
						$hIndex = $h;
						
						
						$slug = Inflector::slug($value);
						$oSlug = $slug;
						$slugCount = 1;
						while (in_array($slug, $slugs)) {
							$slug = $oSlug . '_' . $slugCount++;
						}
						
						$toc .= $this->Html->tag('li',
							$bullet[$h] . '. ' . $this->Html->link($value, $currentUrl . '#' . $slug)
						);
						$bullet[$h]++;
						
						$element['attributes']['id'] = $slug;
						$value .= ' ' . $this->Html->link('Back to top', $currentUrl . '#top', array('class' => 'badge badge-default'));
					}				
					$return .= $this->Html->tag($element['tag'], $value, $element['attributes']);
				}
			}
		}
		$text = $return;
		if ($count >= $options['cutoff']) {
			return $this->Html->div('panel toc',
				$this->Html->div('toc-title panel-heading', 'Content') 
				. $toc
			);
		} else {
			return '';
		}
	}
	
	private function _asciiDebug($text) {
		$return = array();
		for ($i = 0; $i < strlen($text); $i++) {
			$c = $text{$i};
			$return[] = array($c, ord($c));
		}
		debug($return);
	}
	
	protected function parseText($str, $options = array()) {
		$str = $this->Html->div('parseWrapper', $str);
		
		$p = xml_parser_create();
		xml_set_character_data_handler($p, array(&$this, 'xmlDataHandler'));
		xml_parse_into_struct($p, $str, $elements, $index);
				
		$return = '';
		foreach ($elements as $element) {
			$element = array_merge(array(
				'value' => null,
				'attributes' => array()
			), $element);
			
			
			if (!empty($options['value'])) {
				$element['value'] = $this->_parseTextValue($element['value'], $options['value']);
			}
			
			if ($element['level'] == 1) {
				$return .= $element['value'];
			} else if ($element['type'] == 'close') {
				$return .= '</' . $element['tag'] . ">\n";
			} else {
				if ($element['type'] == 'complete' && $element['value'] == null) {
					$return .= '<' . $element['tag'] . "/>\n";
				} else {
					$return .= $this->Html->tag($element['tag'], $element['value'], $element['attributes']);
				}
			}
			
		}
		return $return;
	}

	private function xmlDataHandler($parser, $data) {
		$data = str_replace(' ', '&nbsp;', $data);
		return $data;
	}
	
	private function _parseTextValue($value, $options = array()) {
		if (!empty($options['spaceFormat'])) {
			$value = str_replace(' ', '&nbsp;', $value);
		}
		return $value;
	}
}
<?php
/**
 * Handles a basic version of the Wikipedia markup formatting
 *
 **/
App::uses('Inflector', 'Utilities');
class Markup {

/**
 * The amount of spaces to use for each tab
 *
 * @var int;
 **/
	protected static $tabSpaces = 4;

	public static $wikiModel;

	const EMAIL_REGEX = '[a-zA-Z0-9&\-_.]+@[a-zA-Z0-9&\-_.]+';


	public static function set($text, $options = array()) {
		$default = array(
			'wikiModel' => self::$wikiModel,
			'links' => true,
			'webroot' => '/',
		);
		if ($request = Router::getRequest()) {
			$default['webroot'] = substr($request->webroot, 0, -1);
		}
		$options = array_merge($default, $options);
		extract($options);
		
		$text = self::setLists($text);
		if (!empty($wikiModel)) {
			$text = self::setWikiLinks($text, $wikiModel, $links);
		}
		$text = self::setStyle($text, $webroot);
		$text = self::setCiteSources($text);

		return $text;
	}
	
	public static function setWikiModel($wikiModel) {
		$this->wikiModel = $wikiModel;
	}

/**
 * Converts email addresses and URLs into clickable links
 *
 * @param string $text The text being checked
 * @param bool $shrink If true, it will condense the results
 * @return string The converted $text
 **/
	public static function setLinks($text, $shrink = true) {
		//Emails
		$text = preg_replace('/[\n ](' . self::EMAIL_REGEX . ')/',' <a href="mailto:$1">$1</a>',$text);
		//Links
		$text = preg_replace('/(^|[\n ])(((www|ftp)\.[^ ,""\s<\/]*)[^ ,""\s<]*)/',' <a href="http://$2">' . ($shrink ? '[$3]' : 'http://$2') . '</a>',$text);
		$text = preg_replace('/(^|[\n ])([\w]+?:\/\/([^ ,""\s<\/]*)[^ ,""\s<]*)/',' <a href="$2">' . ($shrink ? '[$3]' : '$2') . '</a>',$text);
		return $text;
	}

	public static function setWikiLinks($text, $wikiModelName, $links = true) {
		extract($options);
		$View = $this;
		$url = array('controller' => Inflector::tableize($wikiModelName), 'action' => 'view');
		$text = preg_replace_callback(
			'/\[\[([^\|^\]]+)[\|]{0,1}([^\]]*)\]\]/',
			function ($matches) use ($links, $View) {
				list($match, $slug, $title) = $matches;
				if (empty($title)) {
					$title = $slug;
				}
				if ($links) {
					return $View->Html->link($title, $url + array(Inflector::slug($slug)));
				} else {
					return $View->Html->tag('span', $title);
				}
			},
			$text
		);
	}

	public static function setCiteSources($text) {
		$references = '';
		$i = 1;	// Keeps track of the current source index
		$replace = [];
		if (preg_match_all('/{{cite[\s]*([a-zA-Z0-9]+)([^}]*)}}/m', $text, $matches)):
			foreach ($matches[0] as $k => $match):
				if (!isset($replace[$match])):
					$id = 'cite_ref_' . $i;
					$reverseId = 'cite_rev_ref_' . $i;

					$replace[$match] = sprintf('<sup>[<a href="#%s" id="%s">%s</a>]</sup>', $id, $reverseId, $i);
					$type = $matches[1][$k];
					if (preg_match_all('/[\s]*[\|]{0,1}[\s]*([^=]+)[\s]*=[\s]*([^\|]+)/', $matches[2][$k], $attrsMatches)) {
						$attrs = Hash::combine($attrsMatches, '1.{n}', '2.{n}');
						$attrs = array_map('trim', $attrs);
					}
					$attrs = $attrs + ['title' => null, 'url' => null];
					if (empty($attrs['title'])) {
						$attrs['title'] = $attrs['url'];
					}

					$source =  sprintf('<sup><a href="#%s" id="%s">^</a></sup> ', $reverseId, $id);
					if (!empty($attrs['url'])) {
						$source .= sprintf('<a href="%s">%s</a>', $attrs['url'], $attrs['title']);
					}
					if (!empty($attrs['author'])) {
						$source .= ' by ' . $attrs['author'];
					}
					if (!empty($attrs['date'])) {
						$source .= ' on ' . date('M j, Y', strtotime($attrs['date']));
					}
					$references .= "\t<li>$source</li>\n";
					$i++;
				endif;
			endforeach;
			$references = '<div class="markup-references"><h3>References</h3><ol>' . $references . '</ol></div>';
		endif;
		if (!empty($replace)) {
			$text = str_replace(array_keys($replace), $replace, $text) . $references;
		}
		return $text;
	}

/**
 * Converts the wiki markup for lists
 * Converts:
 * 		* List 1
 * 		* List 2
 *		** Sub List 1
 * 		** Sub List 2
 * To:
 *		<ul>
 *			<li>List 1</li>
 *			<li>List 2
 * 				<ul>
 *					<li>Sub List 1</li>
 *					<li>Sub List 2</li>
 *				</ul>
 *			</li>
 *		</ul>
 *
 * @param string $text The selected text
 **/
	public static function setLists($text) {
		$endl = "\n";
		$lines = explode($endl, $text);
		$listTags = array();
		$lastDepth = 0;
		$text = '';
		foreach ($lines as $line) {
			if (preg_match('/^([\-\*\#]+)[\s]*([^\r\n]+)/', $line, $matches)) {
				list($full, $bullet, $line) = $matches;
				$lineDepth = strlen($bullet);
				$tag = substr($bullet, -1) == '#' ? 'ol' : 'ul';
				$oldTag = !empty($listTags[$lineDepth]) ? $listTags[$lineDepth] : null;
				$listTags[$lineDepth] = $tag;
				if ($lineDepth == $lastDepth && $tag != $oldTag) {
					$text .= sprintf('</%s><%s>', $listTags[$depth], $tag);
				} else if ($lineDepth > $lastDepth) {
					for ($depth = $lastDepth; $depth < $lineDepth; $depth++) {
						$tag = substr($bullet, $depth, 1) == '#' ? 'ol' : 'ul';
						$listTags[$depth] = $tag;
						$text .= sprintf('<%s>', $tag);
					}
				} else if ($lineDepth < $lastDepth) {
					for ($depth = $lastDepth; $depth >= $lineDepth; $depth--) {
						$text .= sprintf('</%s>', array_pop($listTags));
					}
					$text .= sprintf('<%s>', $tag);
				}
				$text .= sprintf('<li>%s</li>', $line);
			} else {
				$lineDepth = 0;
				if (!empty($listTags)) {
					for ($depth = count($listTags); $depth > 0; $depth--) {
						$text .= sprintf('</%s>', array_pop($listTags));
					}
				}
				$text .= $line . $endl;
			}
			$lastDepth = $lineDepth;
		}
		if (!empty($listTags)) {
			for ($depth = count($listTags); $depth > 0; $depth--) {
				$text .= sprintf('</%s>', array_pop($listTags));
			}
			$text .= $endl;
		}
		return $text;
	}
	

	public static function setStyle($text, $webroot = '') {
		$regx = array(
			//Heading Items
			'/^[=]{4}[\s]*(.*?)([\r\n]|[\n\r]|[\r]|[\n]|[=]{4})/m'				=>  "<h4>$1</h4>",
			'/^[=]{3}[\s]*(.*?)([\r\n]|[\n\r]|[\r]|[\n]|[=]{3})/m'				=>  "<h3>$1</h3>",
			'/^[=]{2}[\s]*(.*?)([\r\n]|[\n\r]|[\r]|[\n]|[=]{2})/m'				=>  "<h2>$1</h2>",
			'/^[=]{1}[\s]*(.*?)([\r\n]|[\n\r]|[\r]|[\n]|[=]{1})/m'				=>  "<h1>$1</h1>",
			
//			'/&#039;&#039;&#039;([^"&#039;&#039;&#039;"]+)&#039;&#039;&#039;/'	=>  "<strong>$1</strong>",
			'/([^=])[\']{3}(.*?)[\']{3}/s'										=>  "$1<strong>$2</strong>",
//			'/&#039;&#039;([^"&#039;&#039;"]+)&#039;&#039;/'				=>  "<em>$1</em>",
			'/([^=])[\']{2}(.*?)[\']{2}/s'										=>  "$1<em>$2</em>",
			'/([^=])["]{3}(.*?)["]{3}/s'										=>  "$1<blockquote><div>&quot;$2&quot;</div></blockquote>",
			'/([^=])["]{2}(.*?)["]{2}/s'										=>  "$1<blockquote><div>$2</div></blockquote>",
			
			//Updates List Items
			'/[\r\n]+-[\s]+(.*?)[\r]*[\n]*[\r]*[\n]*$/sm'		=>	'<uli>$1</uli>',
			'/[\r\n]+[\d]+\.[\s]+([^\r\n]+)[\r]*$/sm'			=>	'<oli>$1</oli>',
			'#((?<!uli\>)<uli>.*?</uli>(?!\<uli))#m'			=>	'<ul>$1</ul>',
			'#((?<!oli\>)<oli>.*?</oli>(?!\<oli))#m'			=>	'<ol>$1</ol>',
			'/<[o|u]li>/'										=>	'<li>',
			'/<\/[o|u]li>/'										=>	"</li>\r\n",
			
			'/\[(\/[^\s]+)\]/'									=>	'<a href="' . $webroot . '$1">$1</a>',
			'/\[(\/[^\s]+)[\s]([^\]]+)\]/'						=>	'<a href="' . $webroot . '$1">$2</a>',
			'/\[([http|\/|\.][^\s]+)\]/'						=>	'<a href="$1">$1</a>',
			'/\[([http|\/|\.][^\s]+)[\s]([^\]]+)\]/'			=>	'<a href="$1">$2</a>',
			'/\[(' . self::EMAIL_REGEX . ')[\s]([^\]]+)\]/'		=> 	'<a href="mailto:$1">$2</a>',
			'/{{clear}}/'										=> 	'<br clear="all" />',
			'/{{-}}/'											=> 	'<br clear="all" />',
			//'/\{([http|\/|\.][^\s]+)[\s]([^\}]+)\}/'			=>	'<a href="$1">$2</a>',
		);

		/*
		if($this->global_links) {
			//Makes sure there are no local links if set
			$regx['#href="/#'] = 'href="'.$this->page_root.'/';
		}
		*/
		
		$text = "\n$text";
		$text = preg_replace(array_keys($regx), $regx, $text, -1, $count);
		$text = substr($text, 1);
		return self::trimBreaks($text);
	}
	
/**
 * Replaces tabs with non-breaking spaces
 *
 * @param string $text
 * @param return string;
 **/
	public static function replaceTabs ($text, $tabSpaces = false) {
		if ($tabSpaces === false) {
			$tabSpaces = self::$tabSpaces;
		}
		return str_replace("\t", str_repeat('&nbsp;', $tabSpaces), $text);
	}

	public static function trimBreaks($text) {
		$text = preg_replace(array(
			'#^[\r\n]*(?:<br\s*/?>[\s\r\n]*)+#', 	//Breaks at beginning of string
			'#(?:<br\s*/?>[\s\r\n]*)+[\r\n]*$#'		//Breaks at end of string
		), '', $text);
		return $text;
	}
}
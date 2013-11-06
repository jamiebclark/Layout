<?php
App::uses('Prefix', 'Lib');
class Url {
	public static function &getInstance() {
		static $instance = array();

		if (!$instance) {
			$instance[0] = new Url();
		}
		return $instance[0];
	}

	public static function getAction($url = null) {
		$paths = Router::getPaths(empty($url) ? true : $url);
		$action = $paths->params['action'];
		if (!empty($paths->params['prefix'])) {
			$action = Prefix::removeFromAction($action, $paths->params['prefix']);
		}
		return $action;
	}
	
	//Returns a CakePHP-formatted URL Array
	public static function urlArray($url = null) {
		if (empty($url)) {
			$url = Router::url();
		}
		$paths = Router::getPaths(true);
		if (!empty($paths->base) && !empty($url) && strpos($url, $paths->base) === 0) {
			$url = substr($url, strlen($paths->base));
		}
		if (!is_array($url)) {
			$url = Router::parse($url);
			unset($url['url']);
			$vars = array('pass', 'named');
			foreach ($vars as $var) {
				if (!empty($url[$var])) {
					foreach ($url[$var] as $k => $v) {
						$url[$k] = $v;
					}
					unset($url[$var]);
				}
			}
		}
		return $url;
	}
	
	public static function host($url) {
		$self =& Url::getInstance();
		$url = parse_url($self->validate($url));
		return preg_replace('/^www./', '', $url['host']);
	}
	
	//Makes sure the URL is formatted correctly with the appropriate prefixes
	public static function validate($url, $options = array()) {
		if (substr($url,0,1) == '/') {
			//If it's a local URL, add the local host
			$url = 'http://' . $_SERVER['HTTP_HOST'] . $url;
		} else if (!preg_match('#://#', $url)) {
			//If we've accidentally left off the scheme, add it on
			$scheme = Param::keyCheck($options, 'scheme', true, 'http');
			$url = $scheme . '://' . $url;
		}
		return $url;
	}
	
	public static function base() {
		$self =& Url::getInstance();
		$urlArray = $self::urlArray();
		$url = Router::url($urlArray, true);
		$noBaseUrl = Router::url(array('base' => false) + $urlArray);
		return substr($url, 0, -1 * strlen($noBaseUrl));
	}
}
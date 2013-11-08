<?php
App::uses('AssetMinify', 'Layout.Vendor');
class MinifiedAssetsController extends LayoutAppController {
	var $name = 'MinifiedAssets';
	var $uses = array();
	
	var $allowedActions = array('*');
	
	function beforeFilter() {
		if (isset($this->Auth)) {
			$this->Auth->allow();
		}
		return parent::beforeFilter();
	}
	
	function css($file = null) {
		$this->_renderFile('css', $file);
	}
	
	function js($file = null) {
		$this->_renderFile('js', $file);
	}
	
	function _renderFile($type, $file = null) {
		$AssetMinify = new AssetMinify();
		if (!empty($file)) {
			$filepath = $AssetMinify->getCacheDir($type, false) . $file;
		} else if (isset($_GET['m'])) {
			$files = explode(',',$_GET['m']);
			$filepath = $AssetMinify->minify($type, $files);
		}
		$this->layout = 'asset';
		switch($type) {
			case 'js':
				$contentType = 'text/javascript';
			break;
			case 'css':
				$contentType = 'text/css';
			break;
		}
		$cacheMtime = filemtime($filepath);
		$etag = md5_file($filepath);
		$lastModified = gmdate("D, d M Y H:i:s", $cacheMtime);
		
		$isModified = !(@strtotime(@$_SERVER['HTTP_IF_MODIFIED_SINCE']) == $cacheMtime || 
			@trim(@$_SERVER['HTTP_IF_NONE_MATCH']) == $etag);
		$isModified = true;

		$this->set(compact('type', 'filepath', 'contentType', 'cacheMtime', 'etag', 'lastModified', 'isModified'));
		$this->render('view');
	}
}
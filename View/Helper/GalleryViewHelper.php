<?php
class GalleryViewHelper extends AppHelper {
	var $name = 'GalleryView';
	var $helpers = array('Layout.Asset');
	
	function beforeRender($viewFile) {
		$this->Asset->css('Layout.gallery_view'); 
		$this->Asset->js('Layout.gallery_view'); 
		return parent::beforeRender($viewFile);
	}
}
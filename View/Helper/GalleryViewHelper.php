<?php
class GalleryViewHelper extends AppHelper {
	var $name = 'GalleryView';
	var $helpers = array('Layout.Asset');
	
	var $modelName;
	
	function beforeRender($viewFile) {
		$this->modelName = Inflector::classify($this->request->params['controller']);
		
		$this->Asset->css('Layout.gallery_view'); 
		$this->Asset->js('Layout.gallery_view'); 
		return parent::beforeRender($viewFile);
	}
	
	function getThumbnails($result, $neighbors = null, $options = array()) {
		$options = array_merge(array(
			'alias' => $this->modelName,
		), $options);
		extract($options);
		list($next, $prev) = $this->getNeighbors(!empty($neighbors) ? $neighbors : $result);
		if (!empty($next)) {
			rsort($next);
		}
		$current = array(0 => array($alias => $result[$alias]));
		return array_merge($next, $current, $prev);
	}
	
	function getNeighborUrls($result, $neighbors = null, $options = array()) {
		return $this->getNeighborInfo('url', $result, $neighbors, $options);
	}
	
	function getNeighborIds($result, $neighbors = null, $options = array()) {
		return $this->getNeighborInfo('id', $result, $neighbors, $options);
	}
	
	function getNeighborInfo($return = 'url', $result, $neighbors = null, $options = array()) {
		$options = array_merge(array(
			'urlAdd' => array(),
			'keys' => false,
		), $options);
		extract($options);
		$neighbors = $this->getNeighbors(!empty($neighbors) ? $neighbors : $result, $keys);
		$info = array();
		foreach ($neighbors as $key => $rows)  {
			$val = null;
			if (isset($rows[0][$this->modelName]['id'])) {
				$modelId = $rows[0][$this->modelName]['id'];
				if ($return == 'url') {
					$val = array('action' => 'view', $modelId) + $urlAdd;
				} else if ($return == 'id') {
					$val = $modelId;
				}

			}
			$info[$key] = $val;
		}
		return $info;
	}

	private function getNeighbors($neighbors, $keys = false) {
		$next = $prev = array();
		// Reverses next and prev, since we want to sort in descending order
		if (!empty($neighbors['next'])) {
			$next = $neighbors['next'];
		}
		if (!empty($neighbors['prev'])) {
			$prev = $neighbors['prev'];
		}
		return $keys ? compact('next', 'prev') : array($next, $prev);	
	}
	
}
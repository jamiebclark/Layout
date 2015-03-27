<?php
class GalleryViewHelper extends AppHelper {
	public $name = 'GalleryView';
	public $helpers = array('Layout.ModelView');
	
	var $modelName;
	
	public function __construct($View, $settings = array()) {
		$this->modelName = Inflector::classify($View->request->params['controller']);
		$this->helpers[] = $this->modelName;
		parent::__construct($View, $settings);
	}
	
	public function beforeRender($viewFile) {
		$this->Html->css('Layout.gallery_view', null, array('inline' => false)); 
		$this->Html->script('Layout.gallery_view', array('inline' => false)); 
		return parent::beforeRender($viewFile);
	}
	
	public function thumbnails($result, $neighbors = null, $options = array()) {
		$thumbs = $this->getThumbnails($result, $neighbors);
		$count = count($thumbs);
		$out = '';
		if ($count > 1) {
			$options = $this->addClass($options, 'row thumbnails' . $count);
			$options['id'] = $result[$this->modelName]['id'];
			$out = $this->{$this->modelName}->thumbnails($thumbs, $options);
		}
		return $out;
	}
	
	public function getThumbnails($result, $neighbors = null, $options = array()) {
		$options = array_merge(array(
			'alias' => $this->modelName,
		), $options);
		extract($options);
		list($next, $prev) = $this->getNeighbors(!empty($neighbors) ? $neighbors : $result);
		if (!empty($prev)) {
			rsort($prev);
		}
		$current = array(0 => array($alias => $result[$alias]));
		return array_merge($prev, $current, $next);
	}
	
	public function getNeighborUrls($result, $neighbors = null, $options = array()) {
		return $this->getNeighborInfo('url', $result, $neighbors, $options);
	}
	
	public function getNeighborIds($result, $neighbors = null, $options = array()) {
		return $this->getNeighborInfo('id', $result, $neighbors, $options);
	}
	
	public function getNeighborInfo($return = 'url', $result, $neighbors = null, $options = array()) {
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
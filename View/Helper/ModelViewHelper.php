<?php
App::uses('LayoutAppHelper', 'Layout.View/Helper');
/**
 * Used to quickly format information used in Model results for output
 *
 **/
class ModelViewHelper extends LayoutAppHelper {
	var $name = 'ModelView';
	
	var $defaultHelpers = array(
		'Html', 
		'Image', 
		'Layout.AddressBook',
		'Layout.Asset', 
		'Layout.DisplayText',
		'Layout.Iconic',
		'Layout.Layout',
	);
	
	var $modelName;
	
	var $blankTitle = 'No Title';
	
	//As long as modelName is set, these should be taken care of
	var $controller;				//Controller to redirect
	var $primaryKey = 'id';
	var $displayField = 'title';	//What to display after link
	var $cssClass;
	var $modelAlias;
	var $modelHuman;

	
	var $fileField = 'filename';

	var $imageDir;
	var $thumbDir = 'profiles/';	//Base directory where thumbnails are stored
	var $defaultDir = 'mid';		//Default sub-directory of thumbnail

	//Whether the urls should be formatted to include slugs: array('controller','action', 'id' => $id, 'slug' => $slug)
	// Should be set up in Config/router first
	protected $sluggable = false;	
	
	//The functions within the Address Book Helper to check using the media function
	private $_addressBookFunctions = array('address', 'cityState', 'addline', 'addressLine');
	
	protected $_actions = array();

	// Actions translated to their Iconic icon name
	var $actionIcons = array(
		'index' => 'list',
		'active' => 'check_alt',
		'add' => 'plus',
		'inactive' => 'x_alt',
		'edit' => 'pen',
		'settings' => 'cog',
		'delete' => 'x',
		'view' => 'magnifying_glass',
		'submit' => 'check',
		'spam' => 'target',
		'move_up' => 'arrow_up',
		'move_down' => 'arrow_down',
		'move_top' => 'upload',
		'move_bottom' => 'download',
	);
	
	// Actions that automatically translate to array
	var $autoActions = array(
		'index', 'edit', 'delete', 'view', 'add', 
		'move_up', 'move_down', 'move_top', 'move_bottom', 'settings',
		'spam', 'clock',
		'active'
	);

	function __construct(View $view, $settings = array()) {
		$helpers = $this->defaultHelpers;
		if (!empty($this->helpers)) {
			$helpers = array_merge($helpers, $this->helpers);
		}
		$this->helpers = $helpers;
		parent::__construct($view, $settings);
		
		if (!empty($settings['model'])) {
			$modelName = $settings['model'];
		} else if (!empty($this->modelName)) {
			$modelName = $this->modelName;
		} else if ($this->name != 'ModelView') {
			$modelName = $this->name;
		} else {
			$modelName = $this->getViewModel();
		}
		if (!empty($modelName)) {
			$this->setModel($modelName);
		}
	}
	
	protected function getViewModel() {
		return array_shift(array_keys($this->request->params['models']));
	}
	
/**
 * Imports the model name and sets basic model-associated variables
 *
 **/
	public function setModel($modelName) {
		$this->modelName = $modelName;
		$Model =& ClassRegistry::init($this->modelName);
		$this->primaryKey = $Model->primaryKey;
		$this->displayField = $Model->displayField;
		$this->controller = Inflector::tableize($this->modelName);
		$this->cssClass = strtolower($this->modelName);
		$this->modelHuman = InflectorPlus::humanize($this->modelName);
		if (empty($this->modelAlias)) {
			$this->modelAlias = $this->modelName;
		}
	}

/**
 * Finds the primaryKey field within a url array
 *
 * @param array $url
 * @return int|null The primary key, or null if not found
 **/ 
	function getUrlId($url) {
		$id = null;
		if (!empty($url[$this->primaryKey])) {
			$id = $url[$this->primaryKey];
		} else if (!empty($url['id'])) {
			$id = $url['id'];
		} else if (!empty($url[0]) && is_numeric($url[0])) {
			$id = $url[0];
		}
		return $id;
	}
	
	// Adds an act
	function setAutoAction($action, $options = array()) {
		if (is_array($action)) {
			foreach ($action as $key => $val) {
				if (is_numeric($key)) {
					$this->setAutoAction($val);
				} else {
					$this->setAutoAction($key, $val);
				}
			}
		} else {
			$this->autoActions[$action] = $options;
			if (!empty($options['icon'])) {
				$this->actionIcons[$action] = $options['icon'];
			}
		}
	}
	
	function getAutoAction($action, $id, $options = array()) {
		$baseUrl = array('controller' => $this->controller, $id) + compact('action');
		$menuItem = $action;
		
		if (in_array($action, array('up', 'down', 'top', 'bottom'))) {
			$baseUrl = array($action => $id);
			$action = 'move_' . $action;
		}
		
		if ($action == 'delete') {
			$menuItem = array('Delete', $baseUrl, array('title' => 'Delete ' . $this->modelHuman), "Delete this {$this->modelHuman}?");
		} else if ($action == 'active') {
			$isActive = !empty($options['active']);
			$title = $isActive ? 'Deactivate' : 'Activate';
			$cmd = $isActive ? 'inactive' : 'active';
			$menuItem = array($title, array($cmd => $id), compact('title') + array('icon' => $cmd));
		} else if ($this->isAutoAction($action)) {
			$actionOptions = isset($this->autoActions[$action]) ? $this->autoActions[$action] : array();
			$title = Inflector::humanize($action);
			$itemOptions = compact('title');
			
			if (isset($actionOptions['function'])) {
				$fn = $actionOptions['function'];
				$menuItem = $fn($id, $options);
			} else {
				$menuItem = array($title, $baseUrl, $itemOptions);
			}
		}
		if (empty($menuItem[2]['icon']) && isset($this->actionIcons[$action])) {
			$menuItem[2]['icon'] = $this->actionIcons[$action];
		}
		return $menuItem;		
	}		

	private function isAutoAction($action) {
		if (is_array($action)) {
			return false;
		}
		return isset($this->autoActions[$action]) || in_array($action, $this->autoActions);
	}
	
	function actionMenu($actions = null, $attrs = array()) {
		$attrs = array_merge(array(
			'icons' => true,	// Displays an icon if found for each action
			'text' => false,		// Displays the link text for each action
		), $attrs);
		$attrs = $this->addClass($attrs, 'action-menu inline');
		if (!($url = Param::keyCheck($attrs, 'url', true)) && !empty($attrs['id'])) {
			$url = $this->url(array('id' => $attrs['id']));
		}
		
		$active = Param::keyCheck($attrs, 'active', true);
		$menu = array();
		
		$useIcons = !empty($attrs['icons']);
		
		if (!empty($attrs['autoActions'])) {
			$this->setAutoAction($attrs['autoActions']);
		}
		if (!empty($actions)) {
			foreach ($actions as $action => $config) {
				if (is_numeric($action)) {
					$action = $config;
					$config = array();
				}
				$config += compact('active');
				if (empty($config['url'])) {
					$config['url'] = $url;
				}
				if (!empty($config['urlAdd'])) {
					$config['url'] = $config['urlAdd'] + $config['url'];
				}
				$id = !empty($attrs['id']) ? $attrs['id'] : $this->getUrlId($config['url']);
				if ($this->isAutoAction($action)) {
					$menuItem = $this->getAutoAction($action, $id, $config);
				} else {
					$menuItem = $action;
					if (is_array($menuItem)) {
						$menuItem[2]['escape'] = false;
					}
				}
				//ID Replace
				if (is_array($menuItem[1])) {
					foreach ($menuItem[1] as $urlKey => $urlVal) {
						if ($urlVal == 'ID') {
							$menuItem[1][$urlKey] = $id;
						}
					}
				}
				if (is_array($menuItem)) {
					list($linkTitle, $linkUrl, $linkOptions, $linkPost) = $menuItem + array(null, null, null, null);
					if (!isset($linkUrl[0])) {
						$linkUrl[0] = $id;
					}
					$linkOptions = $this->addClass($linkOptions, 'btn');
					$linkOptions['escape'] = false;
					if (!empty($attrs['icons']) && isset($linkOptions['icon'])) {
						$oTitle = $linkTitle;
						$linkTitle = $this->Iconic->icon($linkOptions['icon']);
						if (!empty($attrs['text'])) {
							$linkTitle .= " $oTitle";
						}
						unset($linkOptions['icon']);
					}
					if (!empty($attrs['vertical'])) {
						if (!empty($attrs['text'])) {
							if ($prefix = Prefix::get($linkUrl)) {
								$linkTitle .= ' ' . Inflector::humanize($prefix);
							}
							$linkTitle .= ' ' . $linkOptions['title'];
						}
					}
					$menu[] = $this->Html->link($linkTitle, $linkUrl, $linkOptions, $linkPost);
				} else {
					$menu[] = $this->Html->tag('span', $menuItem, array('class' => 'btn'));
				}
			}
		}
		$attrs = $this->addClass($attrs, !empty($attrs['vertical']) ? 'btn-vertical' : 'btn-group');
		return $this->Html->div($attrs['class'], implode('', $menu));
	}
	
	
	function link($Result, $options = array()) {
		$options = array_merge(array(
			'class' => $this->cssClass,
			'escape' => false,
		), (array) $options);
		$url = !empty($options['url']) ? $options['url'] : $this->url($Result);
		if (isset($options['prefix'])) {
			if ($options['prefix'] === false) {
				$url += Prefix::reset();
			} else {
				$url[$options['prefix']] = true;
			}
			unset($options['prefix']);
		}
		
		$title = !empty($Result[$this->displayField]) ? $Result[$this->displayField] : '<em>No Title</em>';
		if (!empty($options['img'])) {
			$imgOptions = array();
			if ($options['img'] !== true) {
				$imgOptions['dir'] = $options['img'];
			}
			$title = $this->thumb($Result, $imgOptions). ' ' . $title;		
		}

		$link = $this->Html->link($title, $url,array('escape' => false) + $options);
		
		if (!empty($options['div'])) {
			$options['tag'] = 'div';
			$options['tagClass'] = $options['div'];
		}
		if (!empty($options['tag'])) {
			if (empty($options['tagClass'])) {
				$options['tagClass'] = null;
			}
			$link = $this->Html->tag($options['tag'], $link, array('class' => $options['tagClass']));
			unset($options['tag']);
			unset($options['div']);
			unset($options['tagClass']);
		}
		return $link;
	}

	
	
	/**
	 * Generates a media element based around the CSS media layout 
	 * 
	 * @param array $result Result array from model
	 * @param array $options
	 * @return string Media HTML element
	 **/
	function media($result, $options = array()) {
		$options = array_merge(array(
			'tag' => 'div',			//Tag wrapper
			'dir' => 'small',		//Thumbnail directory
			'thumb' => array(),		//Thumbnail options
			'url' => null,	
			'contentTag' => 'p',
			'right' => '',
			'idMenu' => false,
			'body' => '',
			'titleTag' => 'h4',
		), $options);
		$options = $this->addClass($options, 'media');
		if (!empty($options['dir'])) {
			$options = $this->addClass($options, 'media-' . $options['dir']);
		}
		extract($options);
		if (empty($url) && $url !== false) {
			$url = $this->url($result);
		}
		
		$out = '';
		//Thumb
		if (isset($thumb) && $thumb === false) {
			$out .= '';
		} else if (!is_array($thumb)) {
			$out .= $thumb;
		} else {
			if (empty($thumb['dir']) && isset($dir)) {
				$thumb['dir'] = $dir;
			}
			$thumb = $this->addClass($thumb, 'media-object');
			$thumb['media'] = true;
			$thumb['url'] = $url;
			$out .= $this->thumb($result, $thumb);
		}

		if (!empty($remove)) {
			$right .= $this->__removeLink(null, $result, !empty($options['remove']) ? $options['remove'] : null);
		}

		if (!empty($right)) {
			$out .= $this->Html->tag('div', $right, array('class' => 'pull-right'));
		}

		//Body
		if (!empty($url)) {
			$title = $this->link($result, compact('url'));
		} else {
			$title = $result[$this->displayField];
		}
		
		if (!empty($titleTag)) {
			$title = $this->Html->tag($titleTag, $title, array('class' => 'media-title'));
		} else {
			$title .= "<br/>\n";
		}
		$bd = $title;
		foreach ($this->_addressBookFunctions as $func) {
			if (!empty($$func)) {
				$bd .= $this->AddressBook->$func($result, array('tag' => $contentTag));
			}
		}
		if (!empty($after)) {
			$bd .= $after;
		}
		
		if (!empty($idMenu)) {
			if (!empty($idMenu[0]) && is_array($idMenu[0])) {
				list($idMenu, $idMenuOptions) = $idMenu;
			} else {
				$idMenuOptions = array();
			}
			$bd .= $this->Layout->idMenu($result[$this->primaryKey], $idMenu, $idMenuOptions);
		}
		$out .= $this->Html->tag('div', $bd, array('class' => 'media-body')) . "\n";
		return $this->Html->tag($tag, $out, array('class' => $class));
	}
	
	function mediaList($results, $options = array()) {
		$options = $this->addClass($options, 'media-list');
		$class = $options['class'];
		unset($options['class']);
		
		$out = '';
		foreach ($results as $result) {
			if (isset($result[$this->modelName])) {
				$result = $result[$this->modelName];
			}
			$out .= $this->media($result, array('tag' => 'li') + $options);
		}
		return $this->Html->tag('ul', $out, compact('class'));
	}
	
	function linkList($Result, $options = array()) {
		$list = array();
		foreach ($Result as $row) {
			if (isset($row[$this->name])) {
				$row = $row[$this->name];
			}
			$list[] = $this->link($row, $options);
		}
		return $this->Html->tag('ul', '<li>' . implode('</li><li>', $list) . '</li>');	
	}	

	function url($Result, $options = array()) {
		$url = array('controller' => $this->controller,	'action' => 'view');
		
		if ($this->sluggable) {
			$url += array(
				'id' => $Result[$this->primaryKey],
				'slug' => Inflector::slug($Result[$this->displayField]),
			);
		} else {
			$url[] = $Result[$this->primaryKey];
		}
		
		if (!empty($options['urlAdd'])) {
			$url = array_merge($url, $options['urlAdd']);
		}
		if (isset($options['prefix'])) {
			if (!empty($options['prefix'])) {
				$url[$options['prefix']] = true;
			} else {
				$url += Prefix::reset();
			}
		}
		return $url;
	}
	
	function thumb($Result, $options = array()) {
		if (Param::keyCheck($options, 'url') === true && !empty($Result)) {
			$options['url'] = $this->url($Result);
		}
		$options = $this->thumbOptions($Result, $options);
		if (!empty($options['media'])) {
			$hasMedia = true;
			$options = $this->addClass($options, 'media-object');
			if (!empty($options['url'])) {
				$url = $options['url'];
				unset($options['url']);
			} else {
				$options = $this->addClass($options, 'pull-left');
			}
		}
		if (isset($options['alt'])) {
			$options['alt'] = strip_tags($options['alt']);
		}
		$out = $this->Image->thumb($Result, $options);
		if (!empty($hasMedia) && !empty($url)) {
			$out = $this->Html->link($out, $url, array('escape' => false, 'class' => 'pull-left'));
		}
		return $out;
	}
	
	function image($Result, $options = array()) {
		$return = '';
		if (!empty($this->imageDir) && !empty($Result[$this->fileField])) {
			$return .= $this->Image->image($Result[$this->fileField], $this->imageDir, $options);
		}
		return $return;
	}
	
	
	/**
	 * Sets the basic options to pass on to Photo helper to create a profile thumbnail
	 *
	 **/
	protected function thumbOptions($Result, $options = array()) {
		if (is_numeric($Result)) {
			$modelId = $Result;
		} else if (!empty($Result[$this->primaryKey])) {
			$modelId = $Result[$this->primaryKey];
		} 
		$options = array_merge(array(
			'dir' => $this->defaultDir,
			'alt' => $this->urlTitle($Result),
			'base' => $this->thumbDir,
			'defaultFile' => '0.jpg',
		), (array) $options);
		return !empty($modelId) ? $this->_idReplace($options, $modelId) : $options;
	}
	
	/**
	 * Replaces any instance of the string with the actual profile's ID
	 * If value is an array, it applies to all values and any arrays found inside
	 *
	 **/
	protected function _idReplace(&$value, $id, $str = '_ID_') {
		if (!is_array($value)) {
			$value = str_replace($str, $id, $value);
		} else {
			foreach ($value as $key => $val) {
				$value[$key] = $this->_idReplace($val, $id, $str);
			}
		}
		return $value;
	}

	protected function urlTitle($Result) {
		if (!empty($Result[$this->displayField])) {
			$title = $Result[$this->displayField];
		} else if ($this->blankTitle) {
			$title = '<em>' . $this->blankTitle . '</em>';
		}
		return $title;
	}
}
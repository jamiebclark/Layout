<?php
App::uses('LayoutAppHelper', 'Layout.View/Helper');
App::uses('InflectorPlus', 'Layout.Lib');

/**
 * Used to quickly format information used in Model results for output
 *
 **/
class ModelViewHelper extends LayoutAppHelper {
	public $name = 'ModelView';
	
	protected $modelName;
	protected $blankTitle = 'No Title';
	
	//As long as modelName is set, these should be taken care of
	protected $controller;				//Controller to redirect
	protected $primaryKey = 'id';
	protected $displayField = 'title';	//displayField from Model
	protected $descriptionField = null;	//field that is the body description
	protected $cssClass;
	protected $modelAlias;
	protected $modelHuman;
	protected $modelPlugin;

	
	protected $imageField = 'filename';

	protected $imageDir;
	protected $thumbDir = 'profiles/';	//Base directory where thumbnails are stored
	protected $defaultDir = 'mid';		//Default sub-directory of thumbnail
	protected $defaultMediaDir = 'small';	//Default sub-directory of an image used in a media HTML object
	protected $defaultImageFile = '0.jpg';

	protected $thumbType = 'image';
	protected $dateStartField = 'started';
	protected $dateEndField = 'stopped';
	
	//Whether the urls should be formatted to include slugs: ['controller','action', 'id' => $id, 'slug' => $slug]
	// Should be set up in Config/router first
	protected $sluggable = false;	
	
	//The functions within the Address Book Helper to check using the media function
	private $_addressBookFunctions = ['address', 'cityState', 'addline', 'addressLine'];
	
	protected $_actions = [];

	// Actions translated to their Iconic icon name
	protected $actionIcons = [
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
	];
	
	// Actions that automatically translate to array
	protected $autoActions = [
		'index', 'edit', 'delete', 'view', 'add', 
		'move_up', 'move_down', 'move_top', 'move_bottom', 'settings',
		'spam', 'clock',
		'active' => ['activate' => true],
	];
	
	// The fields passed to each getAutoAction function
	protected $autoActionFields = ['id', 'url', 'active'];


	public function __construct(View $view, $settings = []) {
		$this->setDefaultHelper([
			'Html', 
			'Form',
			'Layout.AddressBook',
			'Layout.Calendar',
			'Layout.DisplayText',
			'Layout.Iconic',
			'Layout.Image', 
			'Layout.Layout',
			'Text',
		]);

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
		$this->setAutoActions();
	}
	
	protected function getViewModel() {
		if (!empty($this->request->params['models'])) {
			$modelKeys = array_keys($this->request->params['models']);
			return array_shift($modelKeys);
		}
		return null;
	}
	
/**
 * Imports the model name and sets basic model-associated variables
 *
 **/
	public function setModel($modelName) {
		list($plugin, $model) = pluginSplit($modelName);
		if (!empty($plugin)) {
			$this->modelPlugin = $plugin;
			$modelName = $model;
		}
		$this->modelName = $modelName;
		$loadModel = $this->modelName;
		if (!empty($this->modelPlugin)) {
			$loadModel = "{$this->modelPlugin}.$loadModel";
		}
		$Model = ClassRegistry::init($loadModel, true);
		if (empty($Model)) {
			throw new Exception("Could not load ModelViewHelper for model <em>$loadModel</em>");
		}
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
	public function getUrlId($url) {
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
	
	
/**
 * Extendable function to allow child ModelViewHelpers to add new auto actions
 *
 **/
	protected function setAutoActions() {
		return true;
	}

// Adds an action to the action menu
	function setAutoAction($action, $options = []) {
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
	
	function linkActive($id, $action, $actionOptions, $options = []) {
		$options = array_merge([
			'title' => ['Activate', 'Deactivate'],
			'param' => ['activate', 'deactivate'],
			'icon' => 'active',
			'field' => $action,
		], (array) $options);
		$field = $options['field'];
		$active = !empty($actionOptions[$field]) || !empty($actionOptions['result'][$field]);
		foreach ($options as $k => $v) {
			if (is_array($v)) {
				$options[$k] = $v[$active];
			}
		}
		extract($options);
		$class = $active ? 'active' : null;
		return array($title, [$param => $id], compact('title', 'icon', 'class'));
	}
	
	function getAutoAction($action, $id, $options = []) {
		$baseUrl = !empty($options['url']) ? $options['url'] : [];
		$baseUrl += ['controller' => $this->controller, $id];
		$baseUrl['action'] = $action;
		$menuItem = $action;
		
		if (in_array($action, ['up', 'down', 'top', 'bottom'])) {
			$baseUrl = [$action => $id];
			$action = 'move_' . $action;
		}
		
		if ($action == 'delete') {
			$menuItem = ['Delete', $baseUrl, ['title' => 'Delete ' . $this->modelHuman], "Delete this {$this->modelHuman}?"];
		} else if ($this->isAutoAction($action)) {
			$actionOptions = isset($this->autoActions[$action]) ? $this->autoActions[$action] : [];
			$title = Inflector::humanize($action);
			$itemOptions = compact('title');
			foreach ($options as $key => $val) {
				if ($this->isTagAttribute($key)) {
					$itemOptions[$key] = $val;
				}
			}
			if (isset($actionOptions['function'])) {
				$fn = $actionOptions['function'];
				$menuItem = $fn($id, $options);
			} else if (!empty($actionOptions['activate'])) {
				$menuItem = $this->linkActive($id, $action, $options, $actionOptions['activate']);
			} else {
				if (!empty($actionOptions['url'])) {
					$baseUrl = $actionOptions['url'];
				}
				if (isset($actionOptions['idField'])) {
					$baseUrl[$actionOptions['idField']] = $id;
				}
				if (isset($actionOptions['urlOptions'])) {
					$itemOptions = $actionOptions['urlOptions'] + $itemOptions;
				}
				if (!empty($actionOptions['addUrl'])) {
					$baseUrl = $actionOptions['addUrl'] + $baseUrl;
				}
				$menuItem = [$title, $baseUrl, $itemOptions];
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
	
	function adminMenu($actions, $result = [], $actionMenuOptions = [], $navBarOptions = []) {
		$navBarOptions = array_merge([
			'title' => 'Staff Only',
		], $navBarOptions);
		$navBarOptions = $this->addClass($navBarOptions, 'navbar-admin');
		extract($navBarOptions);
		$actionMenuOptions = $this->addClass($actionMenuOptions, 'pull-right');
		$actionMenuOptions['div'] = false;
		$menu = $this->actionMenu($actions, $result, $actionMenuOptions);
		return $this->Layout->navBar($menu, $title, $navBarOptions);
	}
	
	function actionMenu($actions = null, $result = [], $attrs = []) {
		if (!isset($attrs)) {
			$attrs = $result;
		}
		$attrs = array_merge([
			'icons' => true,	// Displays an icon if found for each action
			'text' => false,	// Displays the link text for each action
			'active' => null,
			'id' => null,
			'url' => [],
			'vertical' => false,
			'btnSize' => 'sm',
		], $attrs);
		$attrs = $this->addClass($attrs, 'action-menu inline');
		if ($attrs['vertical']) {
			$attrs['text'] = true;
		}
		if (empty($attrs['url']) && !empty($result['id'])) {
			$attrs['url'] = $this->modelUrl($result);
		}
		$menu = [];
		$useIcons = !empty($attrs['icons']);
		
		$btnClass = 'btn btn-default btn-' . $attrs['btnSize'];

		if (!empty($attrs['autoActions'])) {
			$this->setAutoAction($attrs['autoActions']);
		}
		if (!empty($actions)) {
			foreach ($actions as $action => $config) {
				if (is_numeric($action)) {
					$action = $config;
					$config = [];
				}

				foreach ($this->autoActionFields as $field) {
					if (isset($attrs[$field]) && !isset($config[$field])) {
						$config[$field] = $attrs[$field];
					}
				}

				if (!empty($action[2]['postLink'])) {
					$postLink = $action[2]['postLink'];
					unset($action[2]['postLink']);
				} else {
					$postLink = null;
				}

				if (!empty($attrs['urlAdd'])) {
					$config['urlAdd'] = !empty($config['urlAdd']) ? array_merge($attrs['urlAdd'], $config['urlAdd']) : $attrs['urlAdd'];
				}
				if (empty($config['url'])) {
					if (!empty($attrs['url'])) {
						$config['url'] = $attrs['url'];
					} else if (!empty($result['url'])) {
						$config['url'] = $result['url'];
					}
				}
				if (!empty($config['urlAdd'])) {
					$config['url'] = $config['urlAdd'] + $config['url'];
				}
				$config += compact('result');
				
				$id = !empty($result[$this->primaryKey]) ? $result[$this->primaryKey] : $this->getUrlId($config['url']);
				if ($this->isAutoAction($action)) {
					$menuItem = $this->getAutoAction($action, $id, $config);
					if ($menuItem === false) {
						continue;
					}
				} else {
					$menuItem = $action;
					if (is_array($menuItem)) {
						$menuItem[2]['escape'] = false;
					}
				}
				//ID Replace
				if (is_array($menuItem[1])) {
					foreach ($menuItem[1] as $urlKey => $urlVal) {
						if ($urlVal === 'ID') {
							$menuItem[1][$urlKey] = $id;
						}
					}
				}
				if (is_array($menuItem)) {
					list($linkTitle, $linkUrl, $linkOptions, $confirm) = $menuItem + [null, null, null, null];
					if (!empty($confirm)) {
						$linkOptions['confirm'] = $confirm;
					}
					if ((empty($linkUrl['controller']) || ($linkUrl['controller'] == $this->controller)) && !isset($linkUrl[0])) {
						$linkUrl[0] = $id;
					}
					$linkOptions = $this->addClass($linkOptions, $btnClass);
					$linkOptions['escape'] = false;
					if (isset($linkOptions['icon'])) {
						if (strstr($linkOptions['icon'], '<') !== false) {
							$icon = $linkOptions['icon'];
						} else if (!empty($attrs['icons'])) {
							$icon = $this->Iconic->icon($linkOptions['icon']);	
						} else {
							$icon = '';
						}
						$oTitle = $linkTitle;
						$linkTitle = $icon;
						if (!empty($attrs['text'])) {
							$linkTitle .= " $oTitle";
						}
						unset($linkOptions['icon']);
					}
					/*
					if (!empty($attrs['vertical'])) {
						if (!empty($attrs['text'])) {
							if ($prefix = Prefix::get($linkUrl)) {
								$linkTitle .= ' ' . Inflector::humanize($prefix);
							}
							$linkTitle .= ' ' . $linkOptions['title'];
						}
					}
					*/
					if ($postLink) {
						$menu[] = $this->Form->postLink($linkTitle, $linkUrl, $linkOptions);
					} else {
						$menu[] = $this->Html->link($linkTitle, $linkUrl, $linkOptions);
					}

				} else {
					$menu[] = $this->Html->tag('span', $menuItem, ['class' => $btnClass]);
				}
			}
		}
		$attrs = $this->addClass($attrs, !empty($attrs['vertical']) ? 'btn-vertical' : 'btn-group');
		return $this->Html->div($attrs['class'], implode('', $menu));
	}
	
	
	function link($result, $options = []) {
		$options = array_merge([
			'class' => $this->cssClass,
			'escape' => false,
			'titleFields' => ['truncate'],
		], (array) $options);
		$url = !empty($options['url']) ? $options['url'] : $this->modelUrl($result);
		if (isset($options['prefix'])) {
			if ($options['prefix'] === false) {
				$url += Prefix::reset();
			} else {
				$url[$options['prefix']] = true;
			}
			unset($options['prefix']);
		}
		if ($urlAdd = Param::keyCheck($options, 'urlAdd', true)) {
			$url += $urlAdd;
		}

		$titleOptions = ['tag' => false];
		if ($titleFields = Param::keyCheck($options, 'titleFields', true)) {
			foreach ($titleFields as $field) {
				if ($val = Param::keyCheck($options, $field, true)) {
					$titleOptions[$field] = $val;
				}
			}
		}

		$title = $this->title($result, $titleOptions);
		if (!empty($options['img'])) {
			$imgOptions = [];
			if ($options['img'] !== true) {
				$imgOptions['dir'] = $options['img'];
			}
			$title = $this->thumb($result, $imgOptions). ' ' . $title;		
		}

		$link = $this->Html->link($title, $url,['escape' => false] + $options);
		
		if (!empty($options['div'])) {
			$options['tag'] = 'div';
			$options['tagClass'] = $options['div'];
		}
		if (!empty($options['tag'])) {
			if (empty($options['tagClass'])) {
				$options['tagClass'] = null;
			}
			$link = $this->Html->tag($options['tag'], $link, ['class' => $options['tagClass']]);
			unset($options['tag']);
			unset($options['div']);
			unset($options['tagClass']);
		}
		return $link;
	}
	
	function title($result, $options = []) {
		$options = array_merge([
			'tag' => 'h2',
			'text' => '',
			'default' => '<em>No Title</em>',
			'after' => '',
			'before' => '',
			'truncate' => false,
			'url' => false,
			'alias' => null,
		], (array) $options);
		$options = $this->addClass($options, strtolower($this->name) . '-title');
		extract($options);
		$result = $this->_getResult($result, $alias);
		if (empty($text) && !empty($this->displayField) && !empty($result[$this->displayField])) {
			$text = $result[$this->displayField];
		}
		if (empty($text) && !empty($default)) {
			$text = $default;
		}
		if (!empty($truncate)) {
			$text = $this->Text->truncate($text, $truncate);
		}
		if (!empty($link)) {
			$text = strip_tags($text);
		}
		$text = $before . $text . $after;
		if (!empty($url)) {
			if ($url === true) {
				$url = $this->modelUrl($result);
			}
			$text = $this->Html->link($text, $url, ['escape' => false]);
		}
		if (!empty($tag)) {
			$text = $this->Html->tag($tag, $text, compact('class'));
		}
		return $text;
	}

/**
 * If a descriptionField is set, formats the next and wraps it in a tag
 *
 **/
	function description($result, $options = []) {
		$options = array_merge([
			'tag' => 'div',
			'class' => 'media-description',
		], $options);
		$out = '';
		if (!empty($this->descriptionField) && !empty($result[$this->descriptionField])) {
			$out = $this->DisplayText->text($result[$this->descriptionField], $options);
		}
		return $out;
	}
	

	public function avatar($result, $options = []) {
		$options = array_merge([
			'dir' => 'thumb',
			'link' => true,
			'avatar' => true,
		], $options);
		$options = $this->addClass($options, 'media-avatar');
		return $this->media($result, $options);
	}

/**
 * Generates a media element based around the CSS media layout 
 * 
 * @param array $result Result array from model
 * @param array $options
 * @return string Media HTML element
 **/
	public function media($result, $options = []) {
		$options = array_merge([
			'tag' => 'div',							//Tag wrapper
			'dir' => $this->defaultMediaDir,		//Thumbnail directory
			'thumb' => [],						//Thumbnail options
			'url' => null,							//Use a custom URL instead of the automated one
			'urlAdd' => null,						//Additional parameters to pass to the url
			'contentTag' => 'p',					//Tag to wrap the individual content elements
			'right' => '',							//Text to be floated right
			'body' => '',							//Additional text to show up in the body of the media element
			'titleTag' => 'h4',						//Tag of the title
			'titleClass' => null,
			'link' => false,						//Whether the entire media element should be a link
			'alias' => $this->modelName,			//Alias of the primary model in the result
			'title' => null,						//Custom title
			'actionMenu' => null,					//Actions to be added to an action menu within media element
		], $options);

		$options = $this->addClass($options, 'media media-' . strtolower($this->modelName));
		if (!empty($options['dir'])) {
			$options = $this->addClass($options, 'media-' . $options['dir']);
		}
		extract($options);
		$returnOptions = compact('class');
		$modelResult = $this->_getResult($result, $alias);
		if (empty($url) && $url !== false) {
			$url = $this->modelUrl($modelResult);
		}
		if (!empty($urlAdd) && $url !== false) {
			$url = $urlAdd + $url;
		}
		if ($link || !empty($options['hover'])) {
			if ($tag == 'li') {
				$wrapTag = 'li';
				$tag = 'span';
			}
		}
		if ($link) {
			if ($link === true) {
				$link = $url;
			}
			$tag = 'a';
			$url = false;
			$returnOptions['escape'] = false;
			$returnOptions['href'] = Router::url($link);
		}
		
		$out = '';
		// Thumb
		if (isset($thumb) && $thumb === false) {
			$out .= '';
		} else if (!is_array($thumb)) {
			$out .= $thumb;
		} else {
			if (empty($thumb['dir']) && isset($dir)) {
				$thumb['dir'] = $dir;
			}
			$thumb['media'] = true;
			$thumb += compact('url', 'link', 'alias', 'size', 'avatar') + $thumb;
			$out .= $this->thumb($result, $thumb);
		}
		if (!empty($right)) {
			$out .= $this->Html->tag('div', $right, ['class' => 'pull-right']);
		}
		//Body
		$titleOptions = compact('url', 'alias') + [
			'class' => 'media-title ' . $this->cssClass,
			'tag' => $titleTag,
			'text' => $title,
			'link' => $link,
		];

		if (!empty($avatar)) {
			$titleOptions['truncate'] = 50;
		}
		
		if (!empty($titleClass)) {
			$titleOptions = $this->addClass($titleOptions, $titleClass);
		}
		$body = $this->title($result, $titleOptions);
	
		foreach ($this->_addressBookFunctions as $func) {
			if (!empty($$func)) {
				$line = $this->AddressBook->$func($modelResult, ['tag' => 'small']);
				if (trim(strip_tags($line)) != "") {
					$body .= $line . "<br/>\n";
				}
			}
		}

		if (!empty($after)) {
			$body .= $after;
		}
		
		if (!empty($actionMenu)) {
			if (!empty($actionMenu[0]) && is_array($actionMenu[0])) {
				list($actionMenu, $actionMenuOptions) = $actionMenu;
			} else {
				$actionMenuOptions = [];
			}
			$actionMenuOptions = $this->addClass($actionMenuOptions, 'media-actionmenu');
			$actionMenu = $this->actionMenu($actionMenu, $modelResult, $actionMenuOptions);
			if ($link) {
				if (empty($wrapTag)) {
					$wrapTag = 'span';
				}
			} else {
				$out .= $actionMenu;
			}
		}
		$out .= $this->Html->tag('div', $body, ['class' => 'media-body']) . "\n";
		$out = $this->Html->tag($tag, $out, $returnOptions);

		if (!empty($options['hover']) && method_exists($this, 'hoverContent') && ($hoverContent = $this->hoverContent($result))) {
			$out = $this->Layout->hover($out, $hoverContent, ['block' => true]);
		}

		if (!empty($wrapTag)) {
			if (!empty($actionMenu) && !empty($link)) {
				$out .= $actionMenu;
			}
			$out = $this->Html->tag($wrapTag, $out, ['class' => 'media-wrap']);
		}
		return $out;
	}
	
	public function avatarList($results, $options = [], $listOptions = []) {
		$options = array_merge([
			'dir' => 'thumb',
			'link' => true,
			'avatar' => true,
		], $options);

		$listOptions = array_merge([
			'cols' => 3,
		], $listOptions);

		$options = $this->addClass($options, 'media-avatar');
		$listOptions['listWrapClass'] = 'media-avatar-list';

		if (!empty($listOptions['cols'])) {
			$listOptions = $this->addClass($listOptions, 'media-avatar-list-cols-' . $listOptions['cols']);
			unset($listOptions['cols']);
		}
		return $this->mediaList($results, $options, $listOptions);
	}

	function mediaList($results, $options = [], $listOptions = []) {
		$out = '';
		
		$listWrapClass = !empty($listOptions['listWrapClass']) ? $listOptions['listWrapClass'] : 'media-list';
		unset($listOptions['listWrapClass']);
		$listOptions = $this->addClass($listOptions, $listWrapClass);

		if (empty($results)) {
			if (!empty($listOptions['empty'])) {
				$out = $this->Html->div('lead', $listOptions['empty']);
			}
		} else {
			$paginateOptions = [];
			if (!empty($options['paginate']) && $options['paginate'] !== true) {
				$paginateOptions = $options['paginate'];
			}
			$pagNav = !empty($options['paginate']) ? $this->Layout->paginateNav($paginateOptions) : '';
			$count = 0;
			foreach ($results as $result) {
				$passOptions = $options;
				$id = !empty($result[$this->modelAlias][$this->primaryKey]) ? $result[$this->modelAlias][$this->primaryKey] : null;
				if (!empty($listOptions['active']) && $listOptions['active'] == $id) {
					$passOptions = $this->addClass($passOptions, 'active');
				}
				$out .= $this->media($result, ['tag' => 'li'] + (array) $passOptions);
				if (!empty($listOptions['limit']) && ++$count >= $listOptions['limit']) {
					break;
				}
			}
			$out = $pagNav . $this->Html->tag('ul', $out, $listOptions) . $pagNav;
		}
		return $out;
	}
	
	function linkList($result, $linkOptions = [], $listOptions = []) {
		$list = [];
		foreach ($result as $row) {
			$list[] = $this->link($this->_getResult($row), $linkOptions);
		}
		return $this->Html->tag('ul', '<li>' . implode('</li><li>', $list) . '</li>', $listOptions);	
	}	

	public function modelUrl($result, $options = []) {
		$modelResult = $this->_getResult($result);
		$controller = !empty($options['controller']) ? $options['controller'] : $this->controller;
		$action = !empty($options['action']) ? $options['action'] : 'view';
		$plugin = !empty($options['plugin']) ? $options['plugin'] : Inflector::underscore($this->modelPlugin);
		
		$url = compact('controller', 'action', 'plugin');
		$id = null;
		$title = null;

		if (is_numeric($result)) {
			$id = $result;
		} else {
			if (!empty($modelResult[$this->primaryKey])) {
				$id = $modelResult[$this->primaryKey];
			}
			if (!empty($modelResult[$this->displayField])) {
				$title = $modelResult[$this->displayField];
			}
		}
		
		if ($this->sluggable && !empty($title)) {
			$url += ['id' => $id, 'slug' => $title];
		} else {
			$url[] = $id;
		}
		
		if (!empty($options['urlAdd'])) {
			$url = array_merge($url, $options['urlAdd']);
		}
		if (!empty($this->modelPlugin)) {
			$options['plugin'] = strtolower($this->modelPlugin);
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
	
	function thumb($result, $options = []) {
		$alias = !empty($options['alias']) ? $options['alias'] : null;
		$result = $this->_getResult($result, $alias);
		$isMedia = !empty($options['media']);
		$isAvatar = !empty($options['avatar']);

		$mediaPositionClass = $isAvatar ? null : 'pull-left';

		if (Param::keyCheck($options, 'url') === true && !empty($result)) {
			$options['url'] = $this->modelUrl($result);
		}
		$options = $this->thumbOptions($result, $options);
	
		if ($isMedia) {
			$hasMedia = true;
			$options = $this->addClass($options, 'media-object');
			if (!empty($options['url'])) {
				$url = $options['url'];
				unset($options['url']);
			} else {
				$options = $this->addClass($options, $mediaPositionClass);
			}
		}
		if (isset($options['alt'])) {
			$options['alt'] = str_replace('"', "'", strip_tags($options['alt']));
		}
		
		$type = Param::keyCheck($options, 'type', true, $this->thumbType);

		if ($type == 'text') {
			$out = $this->thumbText($result, $options);
		} else if ($type == 'date') {
			$out = $this->thumbDate($result, $options);
		} else {
			$out = $this->thumbImage($result, $options);
		}
		if (empty($out)) {
			$out = '';
		}
		
		if (!empty($out)) {
			if (!empty($hasMedia) && !empty($url)) {
				$out = $this->Html->link($out, $url, ['escape' => false, 'class' => $mediaPositionClass]);
			}
		}
		return $out;
	}

	function image($Result, $options = []) {
		$return = '';
		if (!empty($options['src'])) {
			$src = $options['src'];
		} else if (!empty($this->imageDir) && !empty($Result[$this->imageField])) {
			$src = $Result[$this->imageField];
		}
		if (!empty($src)) {
			$return .= $this->Image->image($src, $options);
		}
		return $return;
	}

	// Returns only the path to the model's image
	public function imageSrc($result, $options = []) {
		$result = $this->_getResult($result);
		$options = $this->thumbOptions($result, $options);
		$src = $this->Image->src($result, $options);
		
		// Looks for preceding plugin
		$urlParts = explode('/', $src);
		list($plugin, $urlParts[0]) = pluginSplit($urlParts[0]);
		$src = implode('/', $urlParts);
		if (!empty($src) && $src[0] != '/' && strpos($src, '://') === false) {
			$srcBase = Url::base();
			if (!empty($plugin)) {
				$srcBase .= '/' . Inflector::underscore($plugin);
			}
			$src = $srcBase . '/img/' . $src;
		}
		return $src;
	}
	
	protected function thumbText($result, $options = []) {
		$text = 'True';
		if (!empty($options['text']) && $options['text'] !== true) {
			$text = $options['text'];
		}
		$options = $this->addClass($options, 'thumbnail-text');
		$out = $this->Html->tag('span', $text, $this->keyFilter($options, ['style', 'class', 'id']));
		return $out;
	}
	
	protected function thumbDate($result, $options = []) {
		if (isset($options['dir'])) {
			$options = $this->addClass($options, $options['dir']);
		}
		if (!empty($options['media'])) {
			unset($options['media']);
			$mediaPositionClass = !empty($options['avatar']) ? null : 'pull-left';
			$options = $this->addClass($options, 'media-object');
			if (!empty($options['link'])) {
				$options = $this->addClass($options, $mediaPositionClass);
			}
		}
		$start = $end = null;
		if (!empty($this->dateField) && !empty($result[$this->dateField])) {
			$start = $result[$this->dateField];
		} else if (!empty($this->dateStartField) && !empty($result[$this->dateStartField])) {
			$start = $result[$this->dateStartField];
		}
		if (!empty($this->dateEndField) && !empty($result[$this->dateEndField])) {
			$end = $result[$this->dateEndField];
		}
		return $this->Calendar->calendarDate($start, $end, $options);
	}

	protected function thumbImage($result, $options = []) {
		return $this->Image->thumb($result, $options);
	}
	

	function thumbnail($result, $options = []) {
		$modelResult = $this->_getResult($result);
		$options = array_merge([
			'dir' => 'mid',
			'tag' => 'div',		
			'image' => true,		// Display the image
			'caption' => false,		// Display a caption
			'url' => true,			// Link thumbnail to something
			'link' => false,		// Make the entire thumbnail a link
			'empty' => false,		// How to handle a not found image
			'captionTitleTag' => 'h3',
		], $options);
		$options = $this->addClass($options, 'thumbnail');
		extract($options);
		if ($url === true) {
			$url = $this->modelUrl($result);
		}
		if (!empty($urlAdd)) {
			$url = $urlAdd + $url;
		}
		if ($link) {
			$link = $url;
			$options['url'] = false;
			$url = false;
		}
		$out = '';
		if ($image) {
			$out .= $this->thumb($result, compact('dir', 'url') + ['dirClass' => false]);
		}
		if (empty($out)) {
			if ($empty === false) {
				return '';
			} else if ($empty && empty($catpion)) {
				$caption = true;
				$options = compact('caption') + $options;
			}
		}
		if (!empty($caption)) {
			$out .= $this->thumbnailCaption($result, $options);
		}
		$thumbnailOptions = compact('class');
		
		if ($link) {
			return $this->Html->link($out, $link, $thumbnailOptions + ['escape' => false]);
		} else {
			return $this->Html->tag($tag, $out, $thumbnailOptions);
		}
	}
	
	function thumbnailCaption($result, $options = []) {
		$caption = '';
		$modelResult = $this->_getResult($result);
		$tag = !empty($options['captionTitleTag']) ? $options['captionTitleTag'] : 'h3';
		$useCaption = !empty($options['caption']) ? $options['caption'] : true;
		if (!empty($modelResult['title']) && ($useCaption === true || $useCaption == 'title')) {
			$caption .= $this->title($result, compact('tag') + ['url' => $options['url'], 'class' => 'caption-title']);
		}
		if ($useCaption === true || $useCaption == 'description') {
			if ($description = $this->thumbnailCaptionDescription($result, $options)) {
				$caption .= $this->Html->div('caption-description', $description);
			}
		}
		if (!empty($options['after'])) {
			$caption .= $options['after'];
		}
		if (!empty($caption)) {
			$caption = $this->Html->div('caption', $caption);
		}
		return $caption;	
	}
	
	function thumbnailCaptionDescription($result, $options = []) {
		$result = $this->_getResult($result);
		if (!empty($result['description'])) {
			return $this->DisplayText->text($result['description']);
		}
		return '';
	}

/** 
 * Outputs a breadcrumb list based on a getPath result
 *
 **/
	public function pathBreadcrumb($resultPath, $options = []) {
		$options = array_merge([
			'lastLink' => false,		//Should the last item be a link
			'class' => 'breadcrumb',	
			'url' => null, 				//Set an alternate url from the default one
		], $options);
		extract($options);
		$count = count($resultPath) - 1;
		$out = '';
		foreach ($resultPath as $k => $result) {
			if (!$lastLink && $k == $count) {
				$out .= sprintf('<li class="active">%s</li>', $result[$this->modelName][$this->displayField]);
			} else {
				$out .= sprintf('<li>%s</li>', $this->link($result, compact('url')));
			}
		}
		return $this->Html->tag('ol', $out, compact('class'));
	}
	
	private function _getColSizeClass($options = [], $unset = true) {
		$class = '';
		// Converts from Bootstrap 2.X spanN classes
		if (isset($options['span'])) {
			$options['col-sm'] = $options['span'];
			if ($unset) {
				unset($options['span']);
			}
		}
		$suffixes = ['', '-offset'];
		// Cycles through all of the column size types
		foreach ($this->Layout->colSizes as $sizeKey) {
			foreach ($suffixes as $suffix) {
				$key = $sizeKey . $suffix;
				if (isset($options[$key])) {
					$class .= sprintf(' %s-%d', $key, $options[$key]);
					if ($unset) {
						unset($options[$key]);
					}
				}
			}
		}
		return trim($class);
	}
	
	public function thumbnails($results, $options = []) {
		$options = array_merge([
			'id' => null,
			'urlAdd' => null,
			'thumbnailAdd' => null
		], $options);
		$options = $this->addClass($options, 'photo-thumbnails');
		extract($options);
		$wrapClass = $class;
		unset($options['class']);
		
		$out = '';
		$colSizeClass = $this->_getColSizeClass($options);
		$class = $colSizeClass;
		if (!empty($thumbnailClass)) {
			$class = trim($class . ' ' . $thumbnailClass);
		}

		foreach ($results as $result):
			$modelResult = $this->_getResult($result);
			$thumbnailOptions = !empty($options['thumbnailOptions']) ? $options['thumbnailOptions'] : [];
			$thumbnailOptions += $options;
			unset($thumbnailOptions['thumbnailOptions']);
			if ($modelResult['id'] == $id) {
				$thumbnailOptions = $this->addClass($thumbnailOptions, 'active');
			}
			$out .= $this->Html->div($class, $this->thumbnail($result, $thumbnailOptions));
		endforeach;

		if (!empty($thumbnailAdd)) {
			$out .= $this->Html->div($colSizeClass . ' thumbnail thumbnail-add',
				$this->Html->link(Icon::plus(), $thumbnailAdd, ['escape' => false, 'title' => 'Add a photo to the gallery'])
			);
		}

		$out = $this->Html->div('row', $out);
		if (!isset($paginate) || $paginate !== false) {
			$paginate = $this->Layout->paginateNav();
		} else {
			$paginate = '';
		}
		if (empty($sub)) {
			$out = $paginate . $this->Html->div($wrapClass, $out) . $paginate;
		}
		return $out;
	}
	
	/**
	 * Sets the basic options to pass on to Image helper to create a profile thumbnail
	 *
	 **/
	protected function thumbOptions($Result, $options = []) {
		if (is_numeric($Result)) {
			$modelId = $Result;
		} else if (!empty($Result[$this->primaryKey])) {
			$modelId = $Result[$this->primaryKey];
		} 
		if (!empty($options['media']) && !isset($options['dir'])) {
			$options['dir'] = $this->defaultMediaDir;
		}
		$options = array_merge(array(
			'dir' => $this->defaultDir,
			'alt' => $this->urlTitle($Result),
			'base' => $this->thumbDir,
			'defaultFile' => $this->defaultImageFile,
			'dirClass' => true,
			'plugin' => $this->modelPlugin,
			'size' => null,
			'imageField' => $this->imageField,
		), (array) $options);
		//Keeps track of when the entry was last modified to ensure image cache refresh
		if (!empty($options['modified']) && !empty($Result['modified'])) {
			$options['modified'] = date('Ymdhis', strtotime($Result['modified']));
		}
		if (empty($options['size'])) {
			$options['size'] = $options['dir'];
		}
		if (!empty($options['size']) && !empty($options['dirClass'])) {
			$options = $this->addClass($options, "thumbnail-{$options['size']}");
		}
		$options = !empty($modelId) ? $this->replaceModelIdString($options, $modelId) : $options;
		return $options;
	}
	
	function neighbors($neighbors) {
		$fields = ['prev', 'next', 'up' => 'parent'];
		$prev = $next = $up = null;
		foreach ($fields as $neighborField => $resultField) {
			if (is_numeric($neighborField)) {
				$neighborField = $resultField;
			}
			if (!empty($neighbors[$resultField])) {
				$result = $this->_getResult($neighbors[$resultField]);
				$$neighborField = array($this->title($result, ['tag' => false]), $this->modelUrl($result));
			}
		}
		return $this->Layout->neighbors($prev, $next, $up);
	}


	function inputThumb($fieldName = null, $options = []) {
		$options = array_merge([
			'name' => 'add_image',
			'label' => 'Photo',
			'deleteName' => 'delete_file',
			'image' => '',
		], $options);
		extract($options);
		$add = !empty($this->request->data[$this->modelName]['id']);
		$hasImg = !empty($this->request->data[$this->modelName][$this->imageField]);
		if (empty($image)) {
			$image = $this->thumb($hasImg ? $this->request->data[$this->modelName] : 0, [
				'class' => 'input-thumb-image',
				'type' => 'image',
			]);
		}
		$out = $image;
		if (empty($fieldName)) {
			$fieldName = "{$this->modelName}.$name";
		}
		$out .= $this->Form->input($fieldName, array(
			'type' => 'file', 
			'div' => false,
			'label' => $this->Html->tag('font', $label),
			'tabindex' => -1,
		));
		$out = $this->Html->div('input-thumb', $out);
		if ($hasImg && $deleteName) {
			$out .= $this->Form->input($deleteName, [
				'type' => 'checkbox',
				'label' => 'Delete photo',
				'div' => 'input-thumb-delete',
				'class' => 'checkbox',
			]);
		}
		return $out;
	}	
/**
 * Replaces any instance of the string with the actual profile's ID
 * If value is an array, it applies to all values and any arrays found inside
 *
 * @param array|string $value The value to update
 * @param int $modelId The model id to use in the replacement
 * @param string $placeholder The replacement string to search for
 * @return array|string The updated $value;
 **/
	protected function replaceModelIdString($value, $modelId, $placeholder = '_ID_') {
		if (!is_array($value)) {
			if ($value !== false) {
				$value = str_replace($placeholder, $modelId, $value);
			}
		} else {
			foreach ($value as $k => $v) {
				$value[$k] = $this->replaceModelIdString($v, $modelId, $placeholder);
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
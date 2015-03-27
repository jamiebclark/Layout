<?php
App::uses('LayoutAppHelper', 'Layout.View/Helper');
class CollapseListHelper extends LayoutAppHelper {
	var $name = 'CollapseList';
	var $helpers = array(
		'Layout.Layout',
		'Layout.ModelView',
		'Html',
		'Layout.Table',
	);
	
	var $listItemCount = 0;

	function beforeRender($viewFile) {
		$this->Html->script(
			array('Layout.jquery/jquery.scrollTo-1.4.2-min', 'Layout.collapse_list'),
			array('inline' => false)
		);
		parent::beforeRender($viewFile);
	}

	function output($result, $options = array()) {
		$this->Table->reset();
		$options = array_merge(array(
			'sub' => 0,
			'model' => null,
			'displayField' => 'title',
			'primaryKey' => 'id',
			'titleTag' => 'h4',
			'titleUrl' => array('action' => 'view'),
			'titleElement' => null,
			'titleEval' => null,
			'element' => null,
			'infoTableResult' => null,
			'eval' => null,
			'actionMenu' => null,
			'selected' => false,		//ID of the row to be highlighted
			'autoSelected' => true,
			'selectRoot' => false,		//Expand the root element
			'activeField' => false,
			'withChecked' => false,
			'form' => null,
			'draggable' => false,
			'ModelView' => null,
		), $options);
		$options = $this->addClass($options, 'collapse-list');
		if (!empty($options['draggable'])) {
			$options = $this->addClass($options, 'draggable');
		}
		if (empty($options['model'])) {
			$options['model'] = InflectorPlus::modelize($this->request->params['controller']);
		}
		if (empty($options['ModelView'])) {
			$options['ModelView'] =& $this->ModelView;
			$options['ModelView']->setModel($options['model']);
		}
		$root = empty($options['sub']);
		if ($root) {
			$params = $this->request->params;
			if (empty($selected) && $options['autoSelected'] && !empty($params['pass'][0]) && is_numeric($params['pass'][0])) {
				$options['selected'] = $params['pass'][0];
			}
		}
		extract($options);
		
		if ($root) {
			$this->listItemCount = 0;
			//Only needed at root level
			unset($options['withChecked']);
			unset($options['form']);
			unset($options['draggable']);
		}
		$out = '';
		foreach ($result as $row) {
			$hasChildren = !empty($row['children']);
			$isActive = $activeField ? $row[$model][$activeField] == true : null;

			$title = $row[$model][$displayField];
			$id = $row[$model][$primaryKey];
			
			$liOptions = array('class' => 'collapse-list-item', 'id' => 'cl-' . $id);
			if (!$hasChildren) {
				$liOptions = $this->addClass($liOptions, 'childless');
			}
			
			//Title
			$titleClass = 'collapse-list-item-title';
			$titleLinkClass = '';

			$isSelected = !empty($selected) ? ($selected == $id) : ($root && $selectRoot);

			if ($isSelected) {
				$titleClass .= ' selected';
			}
			if ($isActive === false) {
				$titleLinkClass .= ' inactive';
			}
			
			if (!empty($titleUrl)) {
				$url = $titleUrl + array($id);
				$title = $this->Html->link($title, $url, array('class' => $titleLinkClass));
			} else {
				$url = null;
			}
			
			if (!empty($titleElement)) {
				$title = $this->element($titleElement, array('result' => $row));
			}
			
			if (!empty($checkbox)) {
				$title = $this->Table->tableCheckbox($id) . $title;
			}
			$title = $this->Html->tag($titleTag, $title, array('class' => $titleClass));
			
			if (is_array($actionMenu)) {
				$actionMenu += array(array(), array());
				$title = $ModelView->actionMenu($actionMenu[0], $row[$model], array(
					'active' => $isActive,
					'url' => $url,
					'class' => 'pull-right',
				) + $actionMenu[1]) . $title;
			}
			
			if (!empty($checkbox)) {
				$title = $this->Html->tag('label', $title, array('class' => "collapse-list-item-title-label"));
			}
			
			$li = $title;

			//Body
			$body = '';
			if (!empty($element)) {
				$body .= $this->element($element, array('result' => $row));
			} else if (!empty($infoTableResult)) {
				$body .= $this->Layout->infoTableResult($row, $infoTableResult);
			}
			$li .= $this->Html->tag('span', $body, array('class' => 'collapse-list-item-body'));
			
			//Children
			if ($hasChildren) {
				$li .= $this->output($row['children'], array('sub' => $sub + 1) + $options);
			}
			$out .= $this->Html->tag('li', $li, $liOptions);
			$this->listItemCount++;
		}
		$out = $this->Html->tag('ul', $out, array('class' => 'collapse-list-list'));
		
		if ($root) {
			if (!empty($checkbox)) {
				$this->Table->hasForm = true;
			}
			$out = $this->Html->div($class, $out);
			if (!empty($withChecked)) {
				$out .= $this->Table->withChecked($withChecked);
			}
			$out = $this->Table->formWrap($out, $form);
			$this->Html->scriptBlock('$(".collapse-list").collapseList();', array('inline' => false));
		}
		return $out;
	}
}
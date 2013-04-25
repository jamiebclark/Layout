<?php
class CollapseListHelper extends AppHelper {
	var $name = 'CollapseList';
	var $helpers = array(
		'Asset',
		'Layout',
		'Html',
		'Table',
		'Popup',
	);
	
	var $listItemCount = 0;

	function beforeRender($viewFile) {
		$this->Asset->js(array('Layout.jquery/jquery.scrollTo-1.4.2-min', 'Layout.collapse_list'));
		$this->Asset->css('Layout.collapse_list');
		parent::beforeRender($viewFile);
	}

	function output($result, $options = array()) {
		$options = array_merge(array(
			'sub' => 0,
			'model' => null,
			'displayField' => 'title',
			'primaryKey' => 'id',
			'titleTag' => 'h2',
			'titleUrl' => array('action' => 'view'),
			'titleElement' => null,
			'titleEval' => null,
			'element' => null,
			'infoTableResult' => null,
			'eval' => null,
			'actionMenu' => null,
			'selected' => false,
			'autoSelected' => true,
			'activeField' => false,
			'withChecked' => false,
			'form' => null,
			'draggable' => false,
		), $options);
		if (empty($options['model'])) {
			$options['model'] = InflectorPlus::modelize($this->request->params['controller']);
		}
		extract($options);
		$root = !$sub;
		$rootWrapClass = 'collapse-list';
		if ($draggable) {
			$rootWrapClass .= ' draggable';
		}
		
		if ($root) {
			$this->listItemCount = 0;
			//Only needed at root level
			unset($options['withChecked']);
			unset($options['form']);
			unset($options['draggable']);
		}
		
		$out = $this->Html->tag('ul', null, array('class' => 'cl'));
		foreach ($result as $row) {
			$hasChildren = !empty($row['children']);
			$isActive = $activeField ? $row[$model][$activeField] == true : null;

			$title = $row[$model][$displayField];
			$id = $row[$model][$primaryKey];
			
			$liOptions = array(
				'class' => 'cl',
				'id' => 'cl-' . $id,
			);
			if (!$hasChildren) {
				$liOptions = $this->addClass($liOptions, 'childless');
			}
			
			$out .= $this->Html->tag('li', null, $liOptions);
			
			//Title
			$titleClass = 'cl-t';
			$titleLinkClass = '';
			$isSelected = false;
			if ($selected == $id) {
				$isSelected = true;
			} else if ($autoSelected && !empty($this->request->params['pass'][0]) && $this->request->params['pass'][0] == $id) {
				$isSelected = true;
			}
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
			if (is_array($actionMenu)) {
				$actionMenu += array(array(), array());
				$title = $this->Layout->headingActionMenu(
					$title, 
					$actionMenu[0], 
					array(
						'class' => $titleClass,
						'tag' => $titleTag,
						'url' => $url,
						'active' => $isActive,
					) + $actionMenu[1]
				);
			} else {
				$title = $this->Html->tag($titleTag, $title, array('class' => $titleClass));
			}
			
			if (!empty($checkbox)) {
				$title = $this->Html->tag('label', $title, array('class' => 'cl-tl'));
			}
			
			$out .= $title;

			//Body
			$body = '';
			if (!empty($element)) {
				$body .= $this->element($element, array('result' => $row));
			} else if (!empty($infoTableResult)) {
				$body .= $this->Layout->infoTableResult($row, $infoTableResult);
			}
			$out .= $this->Html->tag('span', $body, array('class' => 'cl-body'));
			
			//Children
			if ($hasChildren) {
				$out .= $this->output($row['children'], array('sub' => $sub + 1) + $options);
			}
			$out .= "</li>\n";
			$this->listItemCount++;
		}
		$out .= "</ul>\n";
		
		if ($root) {
			if (!empty($checkbox)) {
				$this->Table->hasForm = true;
			}
			$out = $this->Html->div($rootWrapClass, $out);
			if (!empty($withChecked)) {
				$out .= $this->Table->withChecked($withChecked);
			}
			$out = $this->Table->formWrap($out, $form);
			$out .= '<script type="text/javascript">$(".collapse-list").collapseList();</script>';
		}
		return $out;
	}
}
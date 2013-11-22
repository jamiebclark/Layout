<?php
/**
 * Helper for use with displaying contacts
 *
 **/App::uses('LayoutAppHelper', 'Layout.View/Helper');
class AddressBookHelper extends LayoutAppHelper {
	var $name = 'AddressBook';
	/*	function contactItemList($result, $options = array()) {
		$options = array_merge(array(
			'fields' => array('address', 'email', 'phone', 'website'),
		), $options);
		$list = array();
		$definition = Param::keyValCheck($options, 'definition', true);
		foreach ($options['fields'] as $key => $col) {
			if (!is_numeric($key)) {
				$params = $col;
				$col = $key;
			} else {
				$params = null;
			}
			
			if ($col == 'address' || $col == 'name') {
				$val = $result;
			} else {
				$val = isset($result[$col]) ? $result[$col] : null;
			}
			
			if (empty($val)) {
				continue;
			}
			
			if (!empty($params) && !is_array($params)) {
				$format = $params;
			} else {
				$format = Param::keyCheck($params, 'format', true);
			}
			if (empty($format)) {
				$format = $col;
			}
			if (method_exists($this, $format)) {
				$fOptions = Param::keyCheck($params, 'options');
				$val = call_user_func(array($this, $format), $val, $fOptions);
			}
			if(empty($val)) {
				continue;
			}
			$label = Param::keyCheck($params, 'label', false, Inflector::humanize($col));
			$list[$label] = $val;
		}
		if ($dListOptions = Param::keyValCheck($options, 'definitionList', true)) {
			if (!is_array($dListOptions)) {
				$dListOptions = null;
			}
			return $this->Layout->definitionList($list, $dListOptions);
		} else if ($infoTableOptions = Param::keyValCheck($options, 'infoTable', true)) {
			if (!is_array($infoTableOptions)) {
				$infoTableOptions = array();
			}
			return $this->Layout->infoTable($list, $infoTableOptions);
		} else {
			return $list;
		}
	}
	function date($dateStr = null) {
		return $this->Calendar->niceShort($dateStr);
	}
	
	function title($result, $options = array()) {
		$title = !empty($result['title']) ? $result['title'] : '<em>blank</em>';
		$class = $tag = null;
		if (!empty($options['div'])) {
			list($tag, $class) = array('div', $options['div']);
		}
		if (!empty($options['tag'])) {
			$tag = $options['tag'];
		}
		if (!empty($options['class'])) {
			$class = $options['class'];
		}
		if (!empty($tag)) {
			$title = $this->Html->tag($tag, $title, compact('class'));
		}
		return $title;
	}
	*/
	function resultTable($result, $fields, $options = array()) {
		return $this->Layout->infoTable($this->resultArray($result, $fields, $options));
	}
	
	function resultList($result, $fields, $options = array()) {
		return $this->Layout->definitionList($this->resultArray($result, $fields, $options));
	}
	
	function resultArray($result, $fields, $options = array()) {
		$resultFunctions = array('name', 'address', 'addressLine', 'cityState');
		$list = array();
		foreach ($fields as $field => $config) {
			if (is_numeric($field)) {
				$field = $config;
				$config = array();
			}
			if ($config === true) {
				$config = $field;
			}
			if (!is_array($config)) {
				$config = array('type' => $config);
			}
			if (!isset($config['type'])) {
				$config['type'] = $field;
			}
			if (!empty($config['label'])) {
				$label = $config['label'];
				unset($config['label']);
			} else {
				$label = Inflector::humanize($field);
			}
			$val = null;
			if (in_array($field, $resultFunctions)) {
				$val = $this->{$field}($result, $config);
			} else if (!empty($result[$field])) {
				$val = $result[$field];
				if (!empty($config['type']) && method_exists($this, $config['type'])) {
					$val = $this->{$config['type']}($val, $config);
					unset($config['type']);
				}
			}
			if (!empty($val)) {
				$list[$label] = $val;
			}
		}
		return $list;
	}
	
	function name($result, $options = array()) {
		if (!empty($result['full_name'])) {
			return $result['full_name'];
		} else if (isset($result['name'])) {
			return $result['name'];
		} else if (isset($result['first_name']) && isset($result['last_name'])) {
			return $result['first_name'] . ' ' . $result['last_name'];
		}
	}
	function phone($phoneStr = null) {
		$reg = '/^[1]{0,1}[^0-9]*([0-9]{3})[^0-9]*([0-9]{3})[^0-9]*([0-9]{4})[\s]*(.*?)$/';
		$phoneStr = trim(preg_replace($reg, '($1) $2-$3 $4', $phoneStr, -1, $count));
		return $phoneStr;
	}
	function email($email, $options = array()) {
		return $this->Html->link($email, 'mailto:' . $email, $options);
	}
	function website($url, $options = array()) {
		$host = Url::host($url);
		$url = Url::validate($url);
		
		return $this->Html->link('[' . $host . ']', $url, $options);
	}	
	function location($result, $options = array()) {
		if (!empty($options['beforeField']) && !is_array($options['beforeField'])) {
			$options['beforeField'] = array($options['beforeField']);
		}
		$options['beforeField'][] = 'location_name';
		return $this->address($result, $options);
	}
	
	function addressLine($result, $options = array()) {
		$options['singleLine'] = true;
		return $this->address($result, $options);
	}
	
	function address($result, $options = array()) {
		if (isset($options) && !is_array($options)) {
			$options = array('lineBreak' => $options);
		}
		$options = array_merge(array(
			'lineBreak' => "<br/>\n",
		), (array) $options);
		
		if (!empty($options['singleLine'])) {
			$options['lineBreak'] = ', ';
		}
		
		$lines = array(
			'addline1',
			'addline2',
			'addline3',
			array('city', 'state', 'zip', 'country')
		);
		if (!empty($options['location'])) {
			array_unshift($lines, 'location_name');
		}
		
		if (!empty($options['prefix'])) {
			foreach ($lines as &$line) {
				if (is_array($line)) {
					foreach ($line as &$subLine) {
						$subLine = $options['prefix'] . $subLine;
					}
				} else {
					$line = $options['prefix'] . $line;
				}
			}
			unset($line);
		}
		
		if (!empty($options['beforeField'])) {
			if (!is_array($options['beforeField'])) {
				$options['beforeField'] = array($options['beforeField']);
			}
			$lines = array_merge($options['beforeField'], $lines);
		}
		if (!empty($options['afterField'])) {
			if (!is_array($options['afterField'])) {
				$options['afterField'] = array($options['afterField']);
			}
			$lines = array_merge($lines, $options['afterField']);
		}
		if (!empty($options['before'])) {
			$return = $options['before'] . $options['lineBreak'];
		} else {
			$return = '';
		}
		$lineCount = count($lines);
		foreach ($lines as $k => $line) {
			$returnLine = '';
			if ($line == 'name' && !isset($result['name'])) {
				if (isset($result['first_name']) && isset($result['last_name'])) {
					$line = array('first_name', 'last_name');
				} else if (isset($result['full_name'])) {
					$line = 'full_name';
				}
			}
			if (!is_array($line)) {
				$line = array($line);
			}
			foreach ($line as $lineItem) {
				if (!empty($result[$lineItem])) {
					$returnLine .= $result[$lineItem].' ';
				}
			}
			$returnLine = trim($returnLine);
			
			if ($k == 0 && Param::keyValCheck($options, 'firstLineMap', true)) {
				$url = $this->googleMapsUrl($result, $options);
				if (!empty($url)) {
					$returnLine = $this->Html->link(
						$returnLine,
						$url,
						array('class' => 'secondary', 'target' => '_blank')
					);
				}
			}
			if (!empty($returnLine)) {
				$return .= $returnLine;
				if ($k < $lineCount - 1) {
					$return .= $options['lineBreak'];
				}
			}
		}
		if (in_array(trim($return), array('', 'US'))) {
			return '';
		}
		if (Param::keyValCheck($options, 'map')) {
			$return .= ' ' . $this->mapLink($result);
		}
		
		return $this->_out($return, $options);
	}
	
	function cityState($result, $options = array()) {
		$cityState = '';
		if (!empty($result['city'])) {
			$cityState .= $result['city'];
		}
		if (!empty($result['state'])) {
			if (!empty($cityState)) {
				$cityState .= ', ';
			}
			$cityState .= $result['state'];
		}
		if (!empty($result['country']) && $result['country'] != 'US') {
			if (!empty($cityState)) {
				$cityState .= ' ';
			}
			$cityState .= $result['country'];
		}
		return $this->_out($cityState, $options);
	}
	
	function mapLink($result, $options = array()) {
		$alt = 'View in Google Maps';
		$url = $this->googleMapsUrl($result, $options);
		if (!empty($url)) {
			return $this->Html->link(
				$this->Html->image('icn/16x16/map.png', array('alt' => $alt)),
				$url,
				array(
					'escape' => false, 
					'title' => $alt,
					'target' => '_blank',
				)
			);
		} else {
			return '';
		}
	}
		/*
	function contactMethodDisplays($result, $options = array()) {
		$return = array();
		$contactInfo = !empty($result['Contact']) ? $result['Contact'] : $result;
		
		$options = array_merge(array(
			'url' => array(
				'controller' => 'contact',
				'action' => 'view',
				$contactInfo['id'],
			),
		), $options);
		extract($options);
		
		foreach ($this->contactMethods as $model) {
			$val = '';
			if (!empty($result[$model])) {
				$modelInfo = $result[$model];
			} else if (!empty($result['Contact'][$model])) {
				$modelInfo = $result['Contact'][$model];
			} else {
				$modelInfo = array();
			}
			
			if ($modelCount = count($modelInfo)) {
				$val = $this->contactMethodOutput($model, $modelInfo[0]);
				if ($modelCount > 1) {
					$hover = array();
					for ($i = 0; $i < $modelCount; $i++) {
						$hover[$modelInfo[$i]['label']] = $this->contactMethodOutput($model, $modelInfo[$i]);
					}
					$val = $this->Layout->hover(
						$val . '&nbsp;' . $this->Html->link(
							'(+'.($modelCount-1).')', 
							$url, 
							array('class' => 'secondary')
						),
						$this->Layout->definitionList($hover)
					);
				}
			}
			$return[$model] = $val;
		}
		return $return;
	}	
	function contactMethodOutput($model, $result) {
		if ($model == 'ContactAddress') {
			return $this->addressLine($result);
		} else if ($model == 'ContactEmail') {
			return $this->email($result['value']);
		} else if ($model == 'ContactPhone') {
			return $this->phone($result['value']);
		} else {
			return '';
		}
	}
	*/	
	function googleMapsUrl($result, $options = array()) {
		$options['lineBreak'] = ', ';
		$str = $this->address($result, $options);
		if (in_array(trim($str), array('', 'US'))) {
			return false;
		}
		$url = 'http://maps.google.com/?q=' . urlencode($str);
		return $url;
	}
		/*
	function inputFormatTitle($name, $defaultValue = '', $options = array()) {
		$return = '';
		$nameParts = explode('.', $name);
		$field = array_pop($nameParts);
		$model = !empty($nameParts) ? array_shift($nameParts) : 'Contact';
		$count = !empty($nameParts) ? array_pop($nameParts) : null;
		
		if (is_array($defaultValue)) {
			$defaultValues = $defaultValue;
			$defaultValue = '';
			if (isset($count)) {
				if (!empty($defaultValues[$model][$count][$field])) {
					$defaultValue = $defaultValues[$model][$count][$field];
				}
			} else if (!empty($defaultValues[$model][$field])) {
				$defaultValue = $defaultValues[$model][$field];
			}
		}
		
		$prefix = "$model.";
		if (isset($count)) {
			$prefix .= "$count.";
		}
		
		$fieldKey = $field;
		if ($model != 'Contact') {
			$fieldKey = strtolower($model) . '_' . $fieldKey;
		}	

		$class = 'update_default_' . $field;
		
		$useDefault = false;
		if (!empty($defaultValue) || !$this->Html->value("$prefix$field")) {
			$useDefault = !$this->Html->value("$prefix$field") || $this->Html->value("$prefix$field") == $defaultValue;
		}
		
		$return .= $this->Html->div('format-title-field');
		$return .= $this->Form->hidden("$prefix{$field}_default", array('value' => $defaultValue) + compact('class'));
		
		$name = 'use_default_' . $field;
		$id = $prefix . InflectorPlus::modelize($name);
		
		$after = $this->Html->div('default-input');
		$after .= $this->Html->div('default-checkbox');
		$after .= $this->Form->input("$prefix$name", array(
			'type' => 'checkbox',
			'label' => false,
			'div' => false,
			'id' => $id,
			'checked' => $useDefault,
		));
		$after .= $this->Html->tag('label', $defaultValue, compact('class') + array('for' => $id));
		$after .= "</div>\n";
		$after .= "</div>\n";
		
		$return .= $this->Form->input("$prefix$field", array(
			'between' => '<div class="format-title-input-holder"><div class="user-input">',
			'after' => '</div>' . $after . '</div>',
		));
		$return .= "</div>\n";
		return $return;
	}
	*/	
	private function _out($output, $options = array()) {
		if (!empty($options['div'])) {
			$options['tag'] = 'div';
			$options['class'] = $options['div'];
		}
		if (!empty($options['tag'])) {
			$output = $this->Html->tag($options['tag'], $output, array(
				'class' => !empty($options['class']) ? $options['class'] : null,
			));
		}
		return $output;
	}
}
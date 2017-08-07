<?php
/**
 * Helper for use with displaying contacts
 *
 **/ 
App::uses('LayoutAppHelper', 'Layout.View/Helper');
App::uses('Inflector', 'Utility');

class AddressBookHelper extends LayoutAppHelper {
	public $name = 'AddressBook';

/**
 * Outputs a result as a table
 *
 * @param array $result A map result
 * @param array $fields An array of fields to look for in the result
 * @param array $options Additional options to customize table
 * @return string A table of results
 **/
	public function resultTable($result, $fields, $options = array()) {
		return $this->Layout->infoTable($this->resultArray($result, $fields, $options));
	}
	
/**
 * Outputs a result as definition list
 *
 * @param array $result A model result
 * @param array $fields An array of fields to look for in the result
 * @param array $options Additional options to customize table
 * @return string A definition list of results
 **/
 	public function resultList($result, $fields, $options = array()) {
		return $this->Layout->definitionList($this->resultArray($result, $fields, $options));
	}
/**
 * Converts a model result into an array of contact elements
 *	
 * @param array $result A model result
 * @param array $fields An array of fields to look for in the result
 * @param array $options Additional options to customize table
 * @return array An array of contact elements
 **/
	public function resultArray($result, $fields, $options = array()) {
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
	
/**
 * Outputs a contact name
 *
 * @param array $result A model result
 * @param array $options Additional options (not currently used)
 * @return string The formatted name
 **/
	public function name($result, $options = array()) {
		if (!empty($result['full_name'])) {
			return $result['full_name'];
		} else if (isset($result['name'])) {
			return $result['name'];
		} else if (isset($result['first_name']) && isset($result['last_name'])) {
			return $result['first_name'] . ' ' . $result['last_name'];
		}
	}
	
/**
 * Outputs a phone number in a formatted string
 * 
 * @param string $phoneStr The phone number string
 * @return The updated phone number
 **/
	public function phone($phoneStr = null) {
		$reg = '/^[1]{0,1}[^0-9]*([0-9]{3})[^0-9]*([0-9]{3})[^0-9]*([0-9]{4})[\s]*(.*?)$/';
		$phoneStr = trim(preg_replace($reg, '($1) $2-$3 $4', $phoneStr, -1, $count));
		return $phoneStr;
	}

	
/**
 * Outputs a formatted email string
 *
 * @param string $email The given email address
 * @param array $options Additional options
 * 	- link: Whether to display the email as a link
 * 	- protect: //Hides a certain percentage of the username with asterisks
 * @return string The formatted email
 **/
	public function email($email, $options = array()) {
		$options = array_merge(array(
			'link' => true,			//Link the email
			'protect' => false,		//Hides a certain percentage of the username with asterisks
		), $options);
		$isValid = strpos($email, '@');
		$out = $email;
		if (!empty($options['protect'])) {
			$options['link'] = false;
			if ($options['protect'] >= 1) {
				$options['protect'] = .5;
			}
			if ($isValid) {
				list($userName, $host) = explode('@', $email);
				$len = strlen($userName);
				$out = sprintf('%s%s@%s',
					substr($userName, 0, floor($len * $options['protect'])),
					str_repeat('*', ceil($len * (1 - $options['protect']))),
					$host
				);
			}
			unset($options['protect']);
		}
		if ($options['link']) {
			$out = $this->Html->link($out, 'mailto:' . $email, $options);
		}
		return $out;
	}

/**
 * Outputs a formatted website
 * 
 * @param string $url The url of the website
 * @param array $options Additional options found in the Html link method
 * @return string Formatted url;
 **/
	public function website($url, $options = array()) {
		$host = Url::host($url);
		$url = Url::validate($url);
		return $this->Html->link('[' . $host . ']', $url, $options);
	}
	
/**
 * Outputs a formatting address location name
 *
 * @param array $result A model result
 * @param array $options Additional options
 * @return string The formatted location name
 **/
	public function location($result, $options = array()) {
		if (!empty($options['beforeField']) && !is_array($options['beforeField'])) {
			$options['beforeField'] = array($options['beforeField']);
		}
		$options['beforeField'][] = 'location_name';
		return $this->address($result, $options);
	}
	
/**
 * Outputs a full address formatted on a single line
 *
 * @param array $result A model result
 * @param array $options Additional options for the address method
 * @return string The formatted address line
 **/
	public function addressLine($result, $options = array()) {
		$options['singleLine'] = true;
		return $this->address($result, $options);
	}

/**
 * Returns an address formatted as a URL-encoded string
 *
 * @param array $result A model result
 * @param array $options Additional options
 * @return string|bool A URL-encoded string of the address or false if invalid address
 **/
	public function addressUrlEncode($result, $options = []) {
		$options['lineBreak'] = ', ';
		$str = $this->address($result, $options);
		if (in_array(trim($str), array('', 'US'))) {
			return false;
		}
		return trim(urlencode(strip_tags($str)));		
	}

/**
 * Outputs a full address
 *
 * @param array $result A model result
 * @param array $options Additional options
 * 	- lineBreak: The line break between address lines
 * @return string The formatted name
 **/
	public function address($result, $options = array()) {
		if (isset($options) && !is_array($options)) {
			$options = array('lineBreak' => $options);
		}
		$options = array_merge(array(
			'lineBreak' => "<br/>\n",
		), (array) $options);
		
		if (!empty($options['singleLine'])) {
			$options['lineBreak'] = ', ';
		}
		
		// The fields associated with the address
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
					$returnLine .= $this->Html->tag('span', $result[$lineItem], ['class' => 'addressbook-' . $lineItem]);
					$returnLine .= ' ';
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
		if (Param::keyValCheck($options, 'link')) {
			$url = $options['link'] === true ? $this->googleMapsUrl($result) : $options['link'];
			$return = $this->Html->link($return, $url, ['escape' => false, 'target' => '_blank']);
		} else if (Param::keyValCheck($options, 'map')) {
			$return .= ' ' . $this->mapLink($result);
		}
		return $this->_out($return, $options);
	}
	
/**
 * Outputs a city and state combination
 *
 * @param array $result A model result
 * @param array $options Additional options
 * @return string The formatted city/state
 **/
	public function cityState($result, $options = array()) {
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
	
/**
 * Outputs a link to a map location
 *
 * @param array $result A model result
 * @param array $options Additional google maps options
 * @return string The formatted link
 **/
	public function mapLink($result, $options = array()) {
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
	
/**
 * Outputs a link to Google Maps
 *
 * @param array $result A model result
 * @param array $options Additional options
 * @return string A url back to Google Maps
 ***/
	public function googleMapsUrl($result, $options = array()) {
		if ($q = $this->addressUrlEncode($result, $options)) {
			return 'http://maps.google.com/?q=' . $q;
		}
		return false;
	}
	
/**
 * Outputs text with the option to add HTML formatting
 *
 * @param string $output The output to display
 * @param array $options Additional options to format the output
 *	- div: A div class to wrap the text
 *	- tag: An additional HTML tag to wrap the text
 *	- class: A class to use with the HTML tag
 * @return string The formatted output
 **/
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
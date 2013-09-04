<?php
class FindAllNeighborsBehavior extends ModelBehavior {
	var $name = 'FindAllNeighbors';
	
	function findAllNeighbors(Model $Model, $options = array()) {
		$options = array_merge(array(
			'limit' => 1,
			'dir' => 'ASC',
		), $options);
		
		$field = Param::keyCheck($options, 'field', true, $Model->displayField);
		$value = Param::keyCheck($options, 'value', true);
		
		if (strpos($field, '.') === false) {
			$field = $Model->escapeField($field);
		}
		
		unset($options['conditions'][$field]);
		
		$dir = strtolower($options['dir']);
		$prevSign = $dir == 'asc' ? '<' : '>';
		$prevDir = $dir == 'asc' ? 'DESC' : 'ASC';
		$nextSign = $dir == 'asc' ? '>' : '<';
		$nextDir = $dir == 'asc' ? 'ASC' : 'DESC';
		
		$prev = $options;
		$prev['conditions']["$field $prevSign"] = $value;
		$prev['order'] = "$field $prevDir";
		
		$next = $options;
		$next['conditions']["$field $nextSign"] = $value;
		$next['order'] = "$field $nextDir";
		$result['prev'] = $Model->find('all', $prev);
		
		if (count($result['prev']) < $options['limit']) {
			$next['limit'] += $options['limit'] - count($result['prev']);
		}
		$result['next'] = $Model->find('all', $next);
		if (count($result['next']) < $options['limit'] && count($result['prev'] == $options['limit'])) {
			$prev['limit'] += $options['limit'] - count($result['next']);
			$result['prev'] = $Model->find('all', $prev);
		}
		return $result;
	}
}
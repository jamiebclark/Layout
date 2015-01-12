<?php
class FindAllNeighborsBehavior extends ModelBehavior {
	var $name = 'FindAllNeighbors';
	
	function findAllNeighbors(Model $Model, $query = array()) {
		$query = array_merge(array(
			'limit' => 1,
			'dir' => 'ASC',
		), $query);
		
		$field = Param::keyCheck($query, 'field', true, $Model->displayField);
		$value = Param::keyCheck($query, 'value', true);
		
		if (strpos($field, '.') === false) {
			$field = $Model->escapeField($field);
		}
		
		unset($query['conditions'][$field]);
		unset($query['conditions'][$Model->escapeField()]);
		
		$dir = strtolower($query['dir']);
		$prevSign = $dir == 'asc' ? '<' : '>';
		$prevDir = $dir == 'asc' ? 'DESC' : 'ASC';
		$nextSign = $dir == 'asc' ? '>' : '<';
		$nextDir = $dir == 'asc' ? 'ASC' : 'DESC';
		
		$prev = $query;
		$prev['conditions']["$field $prevSign"] = $value;
		$prev['order'] = "$field $prevDir";
		
		$next = $query;
		$next['conditions']["$field $nextSign"] = $value;
		$next['order'] = "$field $nextDir";
		$result['prev'] = $Model->find('all', $prev);
		
		if (count($result['prev']) < $query['limit']) {
			$next['limit'] += $query['limit'] - count($result['prev']);
		}
		$result['next'] = $Model->find('all', $next);
		if (count($result['next']) < $query['limit'] && count($result['prev'] == $query['limit'])) {
			$prev['limit'] += $query['limit'] - count($result['next']);
			$result['prev'] = $Model->find('all', $prev);
		}
		return $result;
	}
}
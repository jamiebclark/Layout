<?php
class TextCleanup {
	function &getInstance() {
		static $instance = array();

		if (!$instance) {
			$instance[0] =& new TextCleanup();
		}
		return $instance[0];
	}

	function onlyNumeric($str) {
		return preg_replace('/[^\d\.]+/', '', $str);
	}
	
	function ms($str) {
		if (is_array($str)) {
			$self =& new TextCleanup();
			foreach ($str as $k=>$v) {
				$str[$k] = $self->ms($v);
			}
		} else {
			$search = array(
				'”' => '"',
				'“' => '"',
				chr(145) => "'",
				chr(146) => "'",
				chr(147) => '"',
				chr(148) => '"',
				chr(151) => '-',
				chr(133) => '...',
			//	chr(128) => "'",
				chr(153) => '',
				chr(150) => '-',
				chr(189) => '',
				chr(191) => "'",
				chr(201) => 'E', //'É',
				//chr(226) => '',
				chr(239) => '',
				'&#039;' => "'",
			);
			//$str = str_replace(array_keys($search), $search, $str);
			// First, replace UTF-8 characters.
			$str = str_replace(
			 array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
			 array("'", "'", '"', '"', '-', '--', '...'),
			 $str);
			// Next, replace their Windows-1252 equivalents.
			 $str = str_replace(
			 array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
			 array("'", "'", '"', '"', '-', '--', '...'),
			 $str);
		}
		return $str;
	}
}
?>
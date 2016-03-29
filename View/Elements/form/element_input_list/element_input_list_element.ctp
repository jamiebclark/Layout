<?php
if (!empty($element) && is_callable($element)) {
	$function = $element;
}
if (!empty($function)) {
	$content = $function($count);
} else if (!empty($element)) {
	if (empty($pass)) {
		$pass = [];
	}
	$pass = compact('count') + $pass;
	$content = $this->element($element, $pass);
} else {
	throw new Exception('No element input list content specified');
}
echo $content;
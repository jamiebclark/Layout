<?php
if (!empty($element) && is_callable($element)) {
	$function = $element;
}
if (!empty($function)) {
	$content = $function($count);
} else if (!empty($element)) {
	$content = $this->element($element, compact('count'));
} else {
	throw new Exception('No element input list content specified');
}
echo $content;
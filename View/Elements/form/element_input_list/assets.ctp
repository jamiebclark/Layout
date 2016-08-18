<?php
/** 
 * The assets required for the Element Input List
 * We put this in a separate element for easy inclusion on pages that are loading this page via an AJAX call
 *
 **/

$this->Html->script('Layout.element_input_list', ['inline' => false]);
$this->Html->css('Layout.elements/element_input_list', null, ['inline' => false]);

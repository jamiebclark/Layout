<?php
$models = array_keys($this->request->models);
$default = [
	'model' => array_shift($models),
	'modelHuman' => null,
	'element' => null,
	'function' => null,
	'count' => 1,
	'pass' => [],
];
$vars = array_merge($default, compact(array_keys($default)));
extract($vars);

if (empty($modelHuman)) {
	$modelHuman = Inflector::humanize(Inflector::underscore($model));
}

$this->Html->script('Layout.element_input_list', ['inline' => false]);
$this->Html->css('Layout.elements/element_input_list', null, ['inline' => false]);

if ($this->Form->value($model)) {
	$count = count($this->Form->value($model));
}
?>
<div class="element-input-list">
	<?php for ($i = 0; $i < $count; $i++): ?>
		<div class="element-input-list-item">
			<?php echo $this->element('Layout.form/element_input_list/element_input_list_element', ['count' => $i] + $vars); ?>
		</div>
	<?php endfor; ?>
	<div class="element-input-list-control">
		<?php echo $this->Html->link(
			'<i class="fa fa-plus"></i> Add ' . $modelHuman,
			'#',
			['class' => 'btn btn-default btn-sm element-input-list-add', 'escape' => false]
		); ?>
	</div>
	<div class="element-input-list-template">
		<?php echo $this->element('Layout.form/element_input_list/element_input_list_element', [
			'count' => '%TEMPLATE%'
		] + $vars); ?>
	</div>
</div>
<?php
$models = array_keys($this->request->models);
$default = [
	'model' => array_shift($models),
	'modelHuman' => null,
	'element' => null,
	'count' => 1,
];
extract(array_merge($default, compact(array_keys($default))));
if (empty($modelHuman)) {
	$modelHuman = Inflector::humanize(Inflector::underscore($model));
}

$this->Html->script('Layout.element_input_list', ['inline' => false]);
// $this->Html->style('element_input_list', null, ['inline' => false]);

if (!empty($this->request->data[$model])) {
	$count = count($this->request->data[$model]);
}
?>
<div class="element-input-list">
	<?php for ($i = 0; $i < $count; $i++): ?>
		<div class="element-input-list-item">
			<?php echo $this->element($element, ['count' => $i]); ?>
		</div>
	<?php endfor; ?>
	<div class="element-input-list-control">
		<?php echo $this->Html->link(
			'<i class="fa fa-plus"></i> Add Another ' . $modelHuman,
			'#',
			['class' => 'btn btn-default btn-sm element-input-list-add', 'escape' => false]
		); ?>
	</div>
	<div class="element-input-list-template">
		<?php echo $this->element($element, ['count' => '%TEMPLATE%']); ?>
	</div>
</div>
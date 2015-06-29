<?php 
$containerClass = !empty($fluid_layout_content) ? 'container-fluid' : 'container';

if (!empty($pre_crumb)) {
	echo $this->Html->div('pre-crumb', $pre_crumb);
}
if (!empty($this->Crumbs)) {
	$crumbs = $this->Crumbs->output();
} else {
	$crumbs = $this->Html->getCrumbs();
}
if (!empty($crumbs)): ?>
	<div id="breadcrumb">
		<?php echo $this->Html->div($containerClass, $crumbs); ?>
	</div>
<?php endif; ?>

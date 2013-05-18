<?php
$left = $this->fetch('liquidLeft');
$right = $this->fetch('liquidRight');

$hasLeft = !empty($left);
$hasRight = !empty($right);

$class = 'liquid-layout';
if ($hasLeft) {
	$class .= ' has-left';
}
if ($hasRight) {
	$class .= ' has-right';
}
?>
<div class="<?php echo $class;?>">
	<div class="liquid-layout-content">
		<div class="liquid-layout-inner">
			<!-- Liquid Inner Start --->
			<?php echo $this->fetch('content'); ?>
			<!-- Liquid Inner End --->
		</div>
	</div>
	<?php if ($hasLeft):?>
		<div class="liquid-layout-left">
			<div class="liquid-layout-inner">
				<?php echo $left; ?>
			</div>
		</div>
	<?php endif; ?>
	
	<?php if ($hasRight): ?>
		<div class="liquid-layout-right">
			<div class="liquid-layout-inner">
				<?php echo $right; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
<?php
/*
$leftSpan = 2;
$rightSpan = 3;
$centerSpan = 12;
if (!empty($left)) {
	$centerSpan -= $leftSpan;
}
if (!empty($right)) {
	$centerSpan -= $rightSpan;
}
?>
<div class="row">
	<?php
	if (!empty($left)) {
		echo $this->Html->div('span' . $leftSpan, $left);
	}
	
	echo $this->Html->div('span' . $centerSpan, $this->fetch('content'));
	
	if (!empty($right)) {
		echo $this->Html->div('span' . $rightSpan, $right);
	}
	?>
</div>
*/
<?php
if (empty($content)) {
	$content = $this->fetch('content');
}

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

// Escape if no side content is found
if (!$hasLeft && !$hasRight) {
	echo $content;
	return;
}

?>
<div class="<?php echo $class;?>">
	<div class="liquid-layout-content">
		<div class="liquid-layout-inner">
			<!-- Liquid Inner Start --->
			<?php echo $content; ?>
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
<script type="text/javascript">
(function($) {
	$.fn.liquidLayout = function() {
		return this.each(function() {
			var $layout = $(this),
				maxHeight = 0,
				$cols = $('.liquid-layout-content,.liquid-layout-left,.liquid-layout-right', $layout);
			$cols.each(function() {
				var h = $('.liquid-layout-inner',$(this)).height();
				if (h > maxHeight) {
					maxHeight = h;
				}
			}).each(function() {
				$(this).height(maxHeight);
			});
		});
	};
})(jQuery);
$(window)
	.load(function() {
		$('.liquid-layout').liquidLayout();
	})
	.resize(function() {
		$('.liquid-layout').liquidLayout();
	});
$(document).ajaxComplete(function() {
	$('.liquid-layout').liquidLayout();
});
</script>
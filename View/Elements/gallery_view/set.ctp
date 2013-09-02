<?php
// Controls
$this->start('galleryViewControls');
	if (!empty($prevUrl)) {
		echo $this->Html->link(
			$this->Html->tag('span', '&laquo;'), 
			$prevUrl, 
			array('escape' => false, 'class' => 'gallery-view-control prev')
		);
	}
	if (!empty($nextUrl)) {
		echo $this->Html->link(
			$this->Html->tag('span', '&raquo;'), 
			$nextUrl, 
			array('escape' => false, 'class' => 'gallery-view-control next')
		);
	}
$this->end();

// Thumbnails
if ($this->fetch('galleryViewThumbnails')) {
	$this->prepend('galleryViewThumbnails', '<div class="gallery-view-thumbnails">');
	$this->append('galleryViewThumbnails', '</div>');
}

// Main Image Display
$galleryViewImage = $this->fetch('galleryViewImage');
$this->assign('galleryViewImage', '');
$this->start('galleryViewImage'); ?>
	<div class="gallery-view-image">
		<div class="gallery-view-image-display">
			<?php echo $galleryViewImage; ?>
		</div>
		<?php echo $this->fetch('galleryViewControls'); ?>
	</div>
<?php $this->end();

// Wraps certain elements with a css class if they are not blank
$wrap = array('galleryViewInfo' => 'gallery-view-info', 'galleryViewCaption' => 'gallery-view-caption');
foreach ($wrap as $var => $class) {
	if ($content = $this->fetch($var)) {
		$this->assign($var, $this->Html->div($class, $content));
	}
}

$this->start('galleryViewImages'); ?>
	<div class="gallery-view-images"><?php
		echo $this->fetch('galleryViewImage');
		echo $this->fetch('galleryViewThumbnails');
	?></div><?php 
$this->end();

$this->start('galleryViewInfos'); ?>
	<div class="gallery-view-infos"><?php
		if (!empty($this->viewVars['galleryViewTitle'])):?>
			<div class="gallery-view-title">
				<?php echo $this->viewVars['galleryViewTitle']; ?>
			</div>
		<?php endif;
		echo $this->fetch('galleryViewCaption');
		echo $this->fetch('galleryViewInfo');
	?></div><?php
$this->end();	

// Display Layout
$this->start('galleryView');
?><div class="gallery-view"><?php 
	echo $this->fetch('galleryViewImages'); 
	echo $this->fetch('galleryViewInfos');
?></div>
<?php 
$this->end();

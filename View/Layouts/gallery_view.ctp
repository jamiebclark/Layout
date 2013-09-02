<?php 
$this->Asset->css('Layout.gallery_view'); 
$this->Asset->js('Layout.gallery_view'); 
echo $this->element('Layout.gallery_view/set');
?>
<div class="gallery-view">
	<?php if (!empty($this->viewVars['galleryViewHeading'])):?>
		<div class="gallery-view-heading">
			<?php echo $this->viewVars['galleryViewHeading']; ?>
		</div>
	<?php endif; ?>
	<div class="row-fluid">
		<div class="span8">
			<?php echo $this->fetch('galleryViewImages'); ?>
		</div>
		<div class="span4">
			<?php echo $this->fetch('galleryViewInfos'); ?>
		</div>
	</div>
</div>
<?php
echo $this->fetch('content');

<?php 
$this->Asset->css('Layout.gallery_view'); 
$this->Asset->js('Layout.gallery_view'); 
echo $this->element('Layout.gallery_view/set');
?>
<div id="gallery-view-layout">
	<div class="gallery-view">
		<?php if (!empty($this->viewVars['galleryViewHeading'])):?>
			<div class="gallery-view-heading">
				<?php echo $this->viewVars['galleryViewHeading']; ?>
			</div>
		<?php endif; ?>
		<div class="row-fluid">
			<?php if ($info =  $this->fetch('galleryViewInfos')): ?>
				<div class="span8">
					<?php echo $this->fetch('galleryViewImages'); ?>
				</div>
				<div class="span4">
					<?php echo $info; ?>
				</div>
			<?php else:
				echo $this->fetch('galleryViewImages');
			endif;
		?>
		</div>
	</div>
</div>
<?php
echo $this->fetch('content');

<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

$flashParams = array('element' => 'alert');
//$flashParams = [];

if (!empty($this->Flash)) {
	$flash = $this->Flash->render();
	$flash .= $this->Flash->render('auth', $flashParams);
} else {
	$flash = $this->Session->flash();
	$flash .= $this->Session->flash('auth', $flashParams);
}

$default = array(
	'content_class' => '',
	'header_class' => '',
	'footer_class' => '',
);
extract(array_merge($default, compact(array_keys($default))));
$header_class .= ' no-print';
$footer_class .= ' no-print';

if (empty($containerClass)) {
	$containerClass = '';
} else {
	$containerClass .= ' ';
}
$containerClass .= !empty($fluid_layout_content) ? 'container-fluid' : 'container';

 //<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
?>
<!DOCTYPE html>
<html>
<head>
	
	<?php echo $this->Html->charset(); ?>
	<title><?php echo trim($this->fetch('page_title') . ' ' . $title_for_layout); ?></title>
	<?php echo $this->Html->meta('icon'); ?>
	<?php //<meta name="viewport" content="width=device-width"/> ?>
	<?php echo $this->Html->meta(['property' => 'og:title', 'content' => trim($title_for_layout)]); ?>
	<?php echo $this->Html->meta(['property' => 'og:type', 'content' => 'website']); ?>
	<?php echo $this->Html->meta(['property' => 'og:url', 'content' => Router::url($this->request->here(false), true)]);  ?>

	<?php if (!empty($description_for_layout)):
			if (!empty($this->DisplayText)) {
				$description_for_layout = $this->DisplayText->text($description_for_layout);
			}
			$description_for_layout = trim(str_replace("\n", '', strip_tags($description_for_layout)));
			echo $this->Html->meta('description', $description_for_layout);
			echo $this->Html->meta(['property' => 'og:description', 'content' => $description_for_layout]);
		endif;
		
		if (!empty($image_for_layout)) {
			echo $this->Html->tag('link', '', array(
				'rel' => 'image_src',
				'href' => $image_for_layout
			));
			echo $this->Html->meta(['property' => 'og:image', 'content' => $image_for_layout]);
			if (!empty($image_for_layout_properties)) {
				foreach ($image_for_layout_properties as $key => $val) {
					echo $this->Html->meta(['property' => 'og:image:' . $key, 'content' => $val]);
				}
			}
		}

		if ($head = $this->fetch('head')) {
			echo $head;
		}
		
		echo $this->fetch('meta');
		echo $this->Asset->output(true, false, 'css');
	?>
</head>
<?php
if (empty($bodyAttributes)) {
	$bodyAttributes = array();
}
echo $this->Html->tag('body', null, $bodyAttributes);
?>
	<div id="container">
		<header id="header" class="<?php echo $header_class;?>">
			<?php echo $this->fetch('header'); ?>
		</header>
		<div id="content" class="<?php echo $content_class; ?>">
			<?php echo $this->element('Layout.layout/crumbs'); ?>			
			<?php
			$content = '';
			if (!empty($flash)) {
				$content .= $this->Html->div($containerClass, $flash);
			}
			$content .= $this->Html->div(
				$containerClass, 
				$this->fetch('content'), 
				array('id' => 'content-container')
			); 
			echo $content;
			//TODO: Test Liquid Layout
			//echo $this->element('Layout.liquid_content', compact('content'));
			?>
		</div>
		<footer id="footer" class="<?php echo $footer_class; ?>">
			<?php 
				echo $this->fetch('footer');
				if (!empty($adminDisplay)) {
					echo $this->element('sql_dump');
				}
			?>
		</footer>
	</div>
	<?php echo $this->Asset->output(true); ?>
</body>
</html>

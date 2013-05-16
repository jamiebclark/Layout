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

$flash = $this->Session->flash();
$flash .= $this->Session->flash('auth', array(
	'element' => 'alert',
	'params' => array('plugin' => 'TwitterBootstrap')
));
 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php echo $this->Html->charset(); ?>
	<title>
		<?php echo $this->fetch('page_title') . ' ' . $title_for_layout; ?>
	</title>
	<?php
		echo $this->Html->meta('icon');
		
		if (!empty($description_for_layout)) {
			$description_for_layout = str_replace("\n", '', strip_tags($description_for_layout));
			echo $this->Html->meta('description', $description_for_layout);
		}
		
		if (!empty($image_for_layout)) {
			echo $this->Html->tag('link', '', array(
				'rel' => 'image_src',
				'href' => $image_for_layout
			));
		}

		if ($head = $this->fetch('head')) {
			echo $head;
		}
		
		echo $this->fetch('meta');
		echo $this->Asset->output(true);
	?>
</head>
<body>
	<div id="container">
		<div id="header" class="no-print">
			<?php echo $this->fetch('header'); ?>
		</div>
		<div id="content">
			<?php 
				if (!empty($pre_crumb)) {
					echo $this->Html->div('pre-crumb', $pre_crumb);
				}
			?>

			<?php echo $this->Crumbs->output();?>
			<?php
			if (!empty($flash)) {
				echo $this->Html->div('container', $flash);
			}
			?>
			<?php echo $this->Html->div('container', $this->fetch('content')); ?>
		</div>
		<div id="footer">
			<?php 
				echo $this->fetch('footer');
				if (!empty($adminDisplay)) {
					echo $this->element('sql_dump');
				}
			?>
		</div>
	</div>
</body>
</html>

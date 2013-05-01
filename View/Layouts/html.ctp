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

		echo $this->fetch('meta');
		echo $this->Asset->output(true);
	?>
</head>
<body>
	<div id="container">
		<?php if ($header = $this->fetch('header')): ?>
			<div id="header">
				<?php echo $this->Html->div('container no-print', $header); ?>
			</div>
		<?php endif; ?>
		<div id="content" class="container">
			<?php echo $this->Crumbs->output();?>
			<?php echo $this->Session->flash(); ?>
			<?php echo $this->Session->flash('auth', array(
				'element' => 'alert',
				'params' => array('plugin' => 'TwitterBootstrap')
			));?>
			<?php echo $this->Html->div('container', $this->fetch('content')); ?>
		</div>
		<?php if ($footer = $this->fetch('footer')): ?>
			<div id="footer">
				<?php echo $this->Html->div('container no-print', $footer); ?>
			</div>
		<?php endif; ?>
	</div>
	<?php echo $this->element('sql_dump'); ?>
</body>
</html>

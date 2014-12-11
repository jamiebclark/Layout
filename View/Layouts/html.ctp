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
if (CakePlugin::loaded('TwitterBootstrap')) {
	$flashParams['params']['plugin'] = 'TwitterBootstrap';
}

$flash = $this->Session->flash();
$flash .= $this->Session->flash('auth', $flashParams);

 //<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	
	<?php echo $this->Html->charset(); ?>
	<title>
		<?php echo $this->fetch('page_title') . ' ' . $title_for_layout; ?>
	</title>
	<?php echo $this->Html->meta('icon'); ?>
	<?php //<meta name="viewport" content="width=device-width"/> ?>
	<?php		
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
		<div id="header" class="no-print">
			<?php echo $this->fetch('header'); ?>
		</div>
		<div id="content">
			<?php 
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
					<div class="container"><?php echo $crumbs;?></div>
				</div>
			<?php endif; ?>
			
			<?php
			$content = '';
			if (!empty($flash)) {
				$content .= $this->Html->div('container', $flash);
			}
			$content .= $this->Html->div('container', $this->fetch('content'), array('id' => 'content-container')); 
			echo $content;
			//TODO: Test Liquid Layout
			//echo $this->element('Layout.liquid_content', compact('content'));
			?>
		</div>
		<div id="footer" class="no-print">
			<?php 
				echo $this->fetch('footer');
				if (!empty($adminDisplay)) {
					echo $this->element('sql_dump');
				}
			?>
		</div>
	</div>
	<?php echo $this->Asset->output(true); ?>
</body>
</html>

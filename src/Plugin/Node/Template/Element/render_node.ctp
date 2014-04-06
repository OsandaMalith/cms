<?php
/**
 * This view elements is capable of handling multiple view-modes.
 * If you want to create a separated view element for each view mode
 * take a look to `NodeHook::renderNode()` method.
 */
?>
<article class="node node-<?php echo $node->node_type_slug; ?> viewmode-<?php echo $this->getViewMode(); ?>">
	<header>
		<?php if ($this->getViewMode() !== 'full'): ?>
		<h2><?php echo $this->hooktags($node->title); ?></h2>
		<?php else: ?>
		<h1><?php echo $this->hooktags($node->title); ?></h1>
		<?php endif; ?>
		<p><?php echo __d('node', 'Published'); ?>: <time pubdate="pubdate"><?php echo $this->Time->format(__d('node', 'F jS, Y h:i A'), $node->created); ?></time></p>
	</header>

	<?php foreach ($node->_fields as $field): ?>
		<?php echo $this->hooktags($this->render($field)); ?>
	<?php endforeach; ?>

	<?php if ($this->getViewMode() === 'full'): ?>
		<?php
			echo $this->element('Comment.render_comments', [
				'options' => [
					'entity' => $node,
					'visibility' => $node->comment,
				]
			]);
		?>
	<?php endif; ?>
</article>
<div id="app">
	<div id="app-navigation">
		<ul>
			<li id="note-add" data-new='1'>
				<a href='#'>+ <span><?php p($l->t('New Note')); ?></span></a>
			</li>
			<?php foreach ($_['notes'] as $note => $title): ?>
				<li data-note='<?php p($note);?>'>
					<a href='#<?php p($note);?>'><?php p($title);?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<div id="app-content">
		<textarea></textarea>
	</div>
</div>

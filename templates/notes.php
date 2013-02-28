<ul id="leftcontent">
	<li id="note-add" data-new='1'>
		<a href='#'>+ <span><?php p($l->t('New Note')); ?></span></a>
	</li>
	<?php foreach ($_['notes'] as $note => $title): ?>
		<li data-note='<?php p($note);?>'>
			<a href='#<?php p($note);?>'><?php p($title);?></a>
		</li>
	<?php endforeach; ?>
</ul>

<div id="rightcontent">
	<textarea></textarea>
</div>

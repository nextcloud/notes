<ul id="leftcontent">
	<li data-new='1'>
		<a href='#'>+</a>
	</li>
	<?php foreach ($_['notes'] as $note => $title): ?>
		<li data-note='<?php echo $note;?>'>
			<a href='#<?php echo $note;?>'><?php echo $title;?></a>
		</li>
	<?php endforeach; ?>
</ul>

<div id="rightcontent">
	<textarea></textarea>
</div>

<checkbox ng-model="markdown">

<textarea
	ng-model="note.content"
	ng-change="updateTitle()"
	notes-timeout-change="save()"
	autofocus tabindex="-1"></textarea>
<div btf-markdown="note.content" class="markdown">
</div>
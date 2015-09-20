<div id="app-navigation-toggle" class="icon-menu" style="display:none;"></div>
	<pre editor notes-timeout-change="save()" notes-autofocus class="editor" ng-class="{saving: isSaving()}">{{note.content}}</pre>
	<div class="note-meta">{{note.content | wordCount}}</div>
</div>

	<textarea editor notes-timeout-change="save()" name="editor"></textarea>
	<div class="note-meta">
		<span class="note-word-count" ng-if="note.content.length > 0">{{note.content | wordCount}}</span>
		<span class="note-meta-right">
			<button class="icon-fullscreen has-tooltip btn-fullscreen" notes-tooltip ng-click="toggleDistractionFree()"></button>
		</span>
	</div>

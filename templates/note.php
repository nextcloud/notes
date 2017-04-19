	<textarea editor notes-timeout-change="save()" name="editor"></textarea>
	<div class="note-meta">
		{{note.content | wordCount}}
		<span class="note-meta-right">
			<button class="icon-fullscreen has-tooltip" notes-tooltip ng-click="toggleDistractionFree()"></button>
		</span>
	</div>

	<textarea editor notes-timeout-change="onEdit()" name="editor" ng-if="note!==false"></textarea>
	<div class="note-meta" ng-if="note!==false">
		<span class="note-word-count" ng-if="note.content.length > 0">{{note.content | wordCount}}</span>
		<span class="note-unsaved" ng-if="note.unsaved" title="<?php p($l->t('The note has unsaved changes.')); ?>"><?php p($l->t('*')); ?></span>
		<span class="note-error" ng-if="note.error" ng-click="manualSave()" title="<?php p($l->t('Click here to try again')); ?>"><?php p($l->t('Saving failed!')); ?></span>
		<span class="saving" ng-if="isManualSaving()" title="<?php p($l->t('Note saved')); ?>"></span>
		<span class="note-meta-right">
			<button class="icon-fullscreen has-tooltip btn-fullscreen" notes-tooltip ng-click="toggleDistractionFree()"></button>
		</span>
	</div>

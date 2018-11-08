	<textarea editor notes-timeout-change="onEdit()" name="editor" ng-if="note!==false"></textarea>
	<div class="note-meta" ng-if="note!==false">
		<span class="note-category" ng-class="{ uncategorized: !note.category }" title="<?php p($l->t('Category')); ?>" ng-show="!editCategory" ng-click="showEditCategory()">{{ note.category || '<?php p($l->t('Uncategorized')) ?>' | categoryTitle}} <input type="button" class="edit icon icon-rename" title="<?php p($l->t('Edit category')); ?>"></span>
		<span class="note-category" title="<?php p($l->t('Edit category')); ?>" ng-show="editCategory"><form class="category" ng-submit="closeCategory()"><input type="text" id="category" name="category" ng-blur="closeCategory()" placeholder="<?php p($l->t('Uncategorized')); ?>"><input type="submit" class="icon-confirm" value=""></form></span>
		<span class="note-word-count" ng-if="note.content.length > 0">{{note.content | wordCount}}</span>
		<span class="note-unsaved" ng-if="note.unsaved" title="<?php p($l->t('The note has unsaved changes.')); ?>"><?php p($l->t('*')); ?></span>
		<span class="note-error" ng-if="note.error" ng-click="manualSave()" title="<?php p($l->t('Click here to try again')); ?>"><?php p($l->t('Saving failed!')); ?></span>
		<span class="saving" ng-if="isManualSaving()" title="<?php p($l->t('Note saved')); ?>"></span>
		<span class="note-meta-right">
			<button class="icon-fullscreen has-tooltip btn-fullscreen" notes-tooltip ng-click="toggleDistractionFree()"></button>
		</span>
	</div>

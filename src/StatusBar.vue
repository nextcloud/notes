<template>
	<div class="note-meta">
		<span v-show="!editCategory" class="note-category" :class="{ uncategorized: !note.category }"
			:title="t('notes', 'Category')" @click="onShowEditCategory()"
		>
			{{ note.category | categoryLabel }}
			<input type="button" class="edit icon icon-rename" :title="t('notes', 'Edit category')">
		</span>
		<span v-show="editCategory" class="note-category" :title="t('notes', 'Edit category')">
			<form class="category" @submit="onSaveCategory()">
				<input id="category" type="text" name="category"
					:disabled="isCategorySaving" :placeholder="t('notes', 'Uncategorized')" :class="{ 'icon-loading': isCategorySaving }"
					@blur="onSaveCategory()"
				><input v-show="!isCategorySaving" type="submit" class="icon-confirm"
					value=""
				><span v-show="isCategorySaving" class="icon icon-loading-small" />
			</form>
		</span>
		<span v-show="note.content.length > 0" class="note-word-count">
			{{ note.content | wordCount }}
		</span>
		<span v-show="note.unsaved" class="note-unsaved" :title="t('notes', 'The note has unsaved changes.')">
			*
		</span>
		<span v-show="note.error" class="note-error" :title="t('notes', 'Click here to try again')"
			@click="onManualSave()"
		>
			{{ t('notes', 'Saving failed!') }}
		</span>
		<span v-show="isManualSaving" class="saving" :title="t('notes', 'Note saved')" />
	</div>
</template>
<script>

import NotesService from './NotesService'

export default {
	name: 'StatusBar',

	components: {
	},

	filters: {
		categoryLabel: function(category) {
			return NotesService.categoryLabel(category)
		},
		wordCount: function(value) {
			if (value && (typeof value === 'string')) {
				var wordCount = value.split(/\s+/).filter(
					// only count words containing
					// at least one alphanumeric character
					function(value) {
						return value.search(/[A-Za-z0-9]/) !== -1
					}
				).length
				return n('notes', '%n word', '%n words', wordCount)
			} else {
				return 0
			}
		},
	},

	props: {
		note: { type: Object, default: null },
	},

	data: function() {
		return {
			editCategory: false,
			isCategorySaving: false,
		}
	},

	computed: {
		isManualSaving() {
			// TODO
			return false
		},
	},

	methods: {
		onShowEditCategory() {
			// TODO
		},

		onSaveCategory() {
			// TODO
		},

		onManualSave() {
			// TODO
		},

	},
}
</script>

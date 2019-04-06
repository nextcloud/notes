<template>
	<div class="note-meta" style="padding: 20px">
		<h2>{{ note.title }}</h2>
		<div>
			{{ t('notes', 'Last modified: {date}', { date: formattedDate }) }}
			<span v-show="note.unsaved" :title="t('notes', 'Note has unsaved changes')" @click="onManualSave"> * </span>
		</div>
		<div v-show="note.error" class="note-error" :title="t('notes', 'Click here to try again')"
			@click="onManualSave"
		>
			{{ t('notes', 'Saving failed!') }}
		</div>
		<div v-show="note.content && note.content.length > 0" class="note-word-count">
			{{ note.content | wordCount }}
		</div>
		<div class="note-category" :title="t('notes', 'Set category')">
			<form class="category" @submit.prevent.stop="">
				<Multiselect id="category" :value="category" :options="categories"
					:placeholder="t('notes', 'Uncategorized')"
					:disabled="loading.category"
					:class="{'icon-loading-small': loading.category}"
					:show-no-results="false"
					:taggable="true"
					:preserve-search="true"
					@input="onSaveCategory"
					@close="onFinishEditCategory"
					@search-change="onEditCategory"
				/>
				<input
					type="text" style="display: none"
				><input
					type="submit" value=""
					class="icon-confirm loading"
					:disabled="loading.category"
				>
			</form>
		</div>
		<a class="close icon-close" href="#" @click.prevent.stop="onCloseSidebar"><span class="hidden-visually">{{ t('notes', 'Close') }}</span></a>
	</div>
</template>
<script>

import { Multiselect } from 'nextcloud-vue'
import NotesService from './NotesService'
import store from './store'

export default {
	name: 'Sidebar',

	components: {
		Multiselect,
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
		noteId: {
			type: String,
			required: true,
		},
	},

	data: function() {
		return {
			loading: {
				category: false,
			},
			categoryInput: null,
		}
	},

	computed: {
		note() {
			return store.getters.getNote(parseInt(this.noteId))
		},
		category() {
			return this.note ? this.note.category : ''
		},
		formattedDate() {
			return OC.Util.formatDate(this.note.modified * 1000)
		},
		categories() {
			return NotesService.getCategories(0, false)
		},
	},

	methods: {
		onCloseSidebar() {
			store.commit('setSidebarOpen', false)
		},

		onEditCategory(text) {
			this.categoryInput = text
		},

		onFinishEditCategory(str) {
			if (this.categoryInput) {
				this.onSaveCategory(this.categoryInput)
			}
		},

		onSaveCategory(category) {
			this.categoryInput = null
			if (this.note.category !== category) {
				this.loading.category = true
				this.note.category = category
				NotesService.setCategory(this.note.id, category)
					.finally(() => {
						this.loading.category = false
					})
			}
		},

		onManualSave() {
			// TODO
		},

	},
}
</script>
<style scoped>
.close {
	position: absolute;
	top: 0;
	right: 0;
	opacity: 0.5;
	z-index: 1;
	width: 44px;
	height: 44px;
}

.note-error {
	background-color: var(--color-error);
	color: var(--color-primary-text);
	border-radius: 0.5ex;
	padding: 0.5ex 1ex;
}

.note-category {
	margin-top: 1ex;
}

form.category > .multiselect,
form.category > .icon-confirm {
	vertical-align: middle;
}
</style>

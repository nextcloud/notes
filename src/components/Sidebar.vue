<template>
	<AppSidebar v-if="sidebarOpen"
		:title="note.title" :subtitle="subtitle"
		:star-loading="loading.favorite"
		:starred="note.favorite"
		@update:starred="onSetFavorite"
		@close="onCloseSidebar"
	>
		<div class="sidebar-content-wrapper">
			<div class="note-category" :title="t('notes', 'Set category')">
				<h4>{{ t('notes', 'Category') }} <span v-tooltip="t('notes', 'You can create subcategories by using “/” as delimiter between parent category and subcategory, e.g. “{parent}/{sub}”.', { parent: t('notes', 'Category'), sub: t('notes', 'Subcategory')})" class="icon-info svg" /></h4>
				<form class="category" @submit.prevent.stop="">
					<Multiselect id="category" :value="category" :options="categories"
						:placeholder="t('notes', 'Uncategorized')"
						:disabled="loading.category"
						:class="['category-select', {'icon-loading-small': loading.category}]"
						:show-no-results="false"
						:taggable="true"
						:preserve-search="true"
						@input="onSaveCategory"
						@close="onFinishEditCategory"
						@search-change="onEditCategory"
					>
						<template #option="{ option }">
							<span :class="{ gray: option==='' }">{{ option | categoryOptionLabel }}</span>
						</template>
					</Multiselect>
					<input
						type="text" style="display: none"
					><input
						type="submit" value=""
						class="icon-confirm loading"
						:disabled="loading.category"
					>
				</form>
			</div>
			<div class="modified"
				:title="t('notes', 'Click here to save manually')"
				@click="onManualSave"
			>
				<div v-show="note.error" class="note-error">
					{{ t('notes', 'Saving failed!') }}
				</div>
				{{ t('notes', 'Last modified: {date}', { date: formattedDate }) }}
				<span v-show="note.unsaved" :title="t('notes', 'Note has unsaved changes')"> * </span>
			</div>
		</div>
	</AppSidebar>
</template>
<script>

import {
	AppSidebar,
	Multiselect,
	Tooltip,
} from 'nextcloud-vue'
import NotesService from '../NotesService'
import store from '../store'

export default {
	name: 'Sidebar',

	components: {
		AppSidebar,
		Multiselect,
	},

	directives: {
		tooltip: Tooltip,
	},

	filters: {
		categoryOptionLabel: function(obj) {
			const category = obj.isTag ? obj.label : obj
			return NotesService.categoryLabel(category)
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
				favorite: false,
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
		wordCount() {
			const value = this.note.content
			if (value && (typeof value === 'string')) {
				const wordCount = value.split(/\s+/).filter(
					// only count words containing
					// at least one alphanumeric character
					value => value.search(/[A-Za-z0-9]/) !== -1
				).length
				return n('notes', '%n word', '%n words', wordCount)
			} else {
				return null
			}
		},
		subtitle() {
			return this.wordCount
		},
		categories() {
			return [ '', ...NotesService.getCategories(0, false) ]
		},
		sidebarOpen() {
			return store.state.sidebarOpen
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

		onSetFavorite(favorite) {
			this.loading.favorite = true
			NotesService.setFavorite(this.note.id, favorite)
				.catch(() => {
				})
				.finally(() => {
					this.loading.favorite = false
				})
		},

		onSaveCategory(category) {
			this.categoryInput = null
			if (category !== null && this.note.category !== category) {
				this.loading.category = true
				this.note.category = category
				NotesService.setCategory(this.note.id, category)
					.catch(() => {
					})
					.finally(() => {
						this.loading.category = false
					})
			}
		},

		onManualSave() {
			NotesService.saveNoteManually(this.note.id)
		},

	},
}
</script>
<style scoped>
.sidebar-content-wrapper {
	padding: 0 10px;
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

form.category {
	display: flex;
	align-items: center;
}

.note-category .icon-info {
	padding: 11px 20px;
	vertical-align: super;
}

.category-select {
	flex-grow: 1;
}

.gray {
	opacity: 0.5;
}

.modified {
	position: absolute;
	bottom: 0;
	padding: 1ex 0;
	opacity: 0.5;
}
</style>

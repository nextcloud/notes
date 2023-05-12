<template>
	<NcAppSidebar v-if="sidebarOpen"
		:title="title"
		:subtitle="subtitle"
		:star-loading="loading.favorite"
		:starred="note.favorite"
		:title-editable.sync="titleEditable"
		:title-tooltip="titleTooltip"
		@update:starred="onSetFavorite"
		@update:title="onUpdateTitle"
		@submit-title="onRenameTitle"
		@close="onCloseSidebar"
	>
		<div class="sidebar-content-wrapper">
			<div v-if="!note.readonly" class="note-category">
				<h4>
					{{ t('notes', 'Category') }}
					<InfoIcon v-tooltip="categoriesInfo"
						:size="20"
						fill-color="var(--color-main-text)"
						style="display: inline-block; margin-left: 1ex;"
					/>
				</h4>
				<form class="category" @submit.prevent.stop="">
					<NcMultiselect id="category"
						:value="category"
						:options="categories"
						:placeholder="t('notes', 'Uncategorized')"
						:disabled="loading.category"
						:class="['category-select', {'icon-loading-small': loading.category}]"
						:show-no-results="false"
						:taggable="true"
						:preserve-search="true"
						:title="t('notes', 'Set category')"
						@input="onSaveCategory"
						@close="onFinishEditCategory"
						@search-change="onEditCategory"
					>
						<template #option="{ option }">
							<span :class="{ gray: option==='' }">{{ option | categoryOptionLabel }}</span>
						</template>
					</NcMultiselect>
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
	</NcAppSidebar>
</template>
<script>

import {
	NcAppSidebar,
	NcMultiselect,
	Tooltip,
} from '@nextcloud/vue'
import moment from '@nextcloud/moment'

import InfoIcon from 'vue-material-design-icons/Information.vue'

import { getCategories, setFavorite, setTitle, setCategory, saveNoteManually } from '../NotesService.js'
import { categoryLabel } from '../Util.js'
import store from '../store.js'

export default {
	name: 'Sidebar',

	components: {
		InfoIcon,
		NcAppSidebar,
		NcMultiselect,
	},

	directives: {
		tooltip: Tooltip,
	},

	filters: {
		categoryOptionLabel(obj) {
			const category = obj.isTag ? obj.label : obj
			return categoryLabel(category)
		},
	},

	props: {
		noteId: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			loading: {
				category: false,
				favorite: false,
				title: false, // TODO reflect this state in the UI
			},
			categoryInput: null,
			titleEditableInternal: false,
			newTitle: '',
		}
	},

	computed: {
		note() {
			return store.getters.getNote(parseInt(this.noteId))
		},
		titleEditable: {
			get() {
				return this.titleEditableInternal && !this.note.readonly
			},
			set(newValue) {
				if (newValue) {
					this.newTitle = this.title
				}
				this.titleEditableInternal = newValue
			},
		},
		title() {
			if (!this.titleEditable) {
				return this.note?.title || ''
			} else {
				return this.newTitle || ''
			}
		},
		category() {
			return this.note?.category || ''
		},
		formattedDate() {
			return moment(this.note.modified * 1000).format('LLL')
		},
		wordCount() {
			const value = this.note?.content
			if (value && (typeof value === 'string')) {
				const wordCount = value.split(/\s+/).filter(
					// only count words containing
					// at least one alphanumeric character
					value => value.search(/[A-Za-z0-9]/) !== -1
				).length
				const charCount = Array.from(value).length
				return n('notes', '%n word', '%n words', wordCount)
					+ ', ' + n('notes', '%n character', '%n characters', charCount)
			} else {
				return ''
			}
		},
		subtitle() {
			return this.wordCount
		},
		categoriesInfo() {
			return t('notes', 'You can create subcategories by using “/” as delimiter between parent category and subcategory, e.g. “{parent}/{sub}”.', { parent: t('notes', 'Category'), sub: t('notes', 'Subcategory') })
		},
		categories() {
			return ['', ...getCategories(0, false)]
		},
		sidebarOpen() {
			return store.state.app.sidebarOpen
		},
		titleTooltip() {
			return t('notes', 'Click to edit title')
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
			setFavorite(this.note.id, favorite)
				.catch(() => {
				})
				.then(() => {
					this.loading.favorite = false
				})
		},

		onUpdateTitle(newTitle) {
			this.newTitle = newTitle
		},

		onRenameTitle() {
			if (this.title !== this.newTitle) {
				this.loading.title = true
				setTitle(this.note.id, this.newTitle)
					.catch(() => {
					})
					.finally(() => {
						this.loading.title = false
					})
			}
		},

		onSaveCategory(category) {
			this.categoryInput = null
			if (category !== null && this.note.category !== category) {
				this.loading.category = true
				this.note.category = category
				setCategory(this.note.id, category)
					.catch(() => {
					})
					.then(() => {
						this.loading.category = false
					})
			}
		},

		onManualSave() {
			saveNoteManually(this.note.id)
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
	color: var(--color-primary-element-text);
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

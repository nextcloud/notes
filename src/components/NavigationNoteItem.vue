<template>
	<AppNavigationItem
		:title="title"
		:icon="icon"
		:menu-open.sync="actionsOpen"
		:to="{ name: 'note', params: { noteId: note.id.toString() } }"
		:class="{ actionsOpen }"
		:loading="loading.note"
		:editable="!note.readonly"
		:edit-label="t('notes', 'Rename')"
		:edit-placeholder="t('notes', 'Note\'s title')"
		@update:title="onRename"
	>
		<template #actions>
			<ActionButton :icon="actionFavoriteIcon" @click="onToggleFavorite">
				{{ actionFavoriteText }}
			</ActionButton>
			<ActionButton v-if="!note.readonly" :icon="actionDeleteIcon" @click="onDeleteNote">
				{{ t('notes', 'Delete note') }}
			</ActionButton>
			<ActionSeparator />
			<ActionButton icon="icon-files-dark" @click="onCategorySelected">
				{{ actionCategoryText }}
			</ActionButton>
		</template>
	</AppNavigationItem>
</template>

<script>
import {
	ActionButton,
	ActionSeparator,
	AppNavigationItem,
} from '@nextcloud/vue'
import { showError } from '@nextcloud/dialogs'

import { setFavorite, setTitle, fetchNote, deleteNote } from '../NotesService.js'
import { categoryLabel, routeIsNewNote } from '../Util.js'

export default {
	name: 'NavigationNoteItem',

	components: {
		ActionButton,
		ActionSeparator,
		AppNavigationItem,
	},

	props: {
		note: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			loading: {
				note: false,
				favorite: false,
				delete: false,
			},
			actionsOpen: false,
		}
	},

	computed: {
		icon() {
			let icon = ''
			if (this.note.error) {
				icon = 'nav-icon icon-error-color'
			} else if (this.note.favorite) {
				icon = 'nav-icon icon-starred'
			}
			return icon
		},

		title() {
			return this.note.title + (this.note.unsaved ? ' *' : '')
		},

		actionFavoriteText() {
			return this.note.favorite ? this.t('notes', 'Remove from favorites') : this.t('notes', 'Add to favorites')
		},

		actionFavoriteIcon() {
			let icon = this.note.favorite ? 'icon-star-dark' : 'icon-starred'
			if (this.loading.favorite) {
				icon += ' loading'
			}
			return icon
		},

		actionCategoryText() {
			return categoryLabel(this.note.category)
		},

		actionDeleteIcon() {
			return 'icon-delete' + (this.loading.delete ? ' loading' : '')
		},
	},

	methods: {
		onToggleFavorite() {
			this.loading.favorite = true
			setFavorite(this.note.id, !this.note.favorite)
				.catch(() => {
				})
				.then(() => {
					this.loading.favorite = false
					this.actionsOpen = false
				})
		},

		onCategorySelected() {
			this.actionsOpen = false
			this.$emit('category-selected', this.note.category)
		},

		onRename(newTitle) {
			this.loading.note = true
			setTitle(this.note.id, newTitle)
				.catch(() => {
				})
				.finally(() => {
					this.loading.note = false
				})
			if (routeIsNewNote(this.$route)) {
				this.$router.replace({
					name: 'note',
					params: { noteId: this.note.id.toString() },
				})
			}
		},

		async onDeleteNote() {
			this.loading.delete = true
			try {
				const note = await fetchNote(this.note.id)
				if (note.errorType) {
					throw new Error('Note has errors')
				}
				await deleteNote(this.note.id, () => {
					this.$emit('note-deleted', note)
					this.loading.delete = false
					this.actionsOpen = false
				})
			} catch (e) {
				showError(this.t('notes', 'Error during preparing note for deletion.'))
				this.loading.delete = false
				this.actionsOpen = false
			}
		},
	},
}
</script>

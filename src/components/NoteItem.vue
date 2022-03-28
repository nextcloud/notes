<template>
	<ListItem
		:title="title"
		:active="isSelected"
		:to="{ name: 'note', params: { noteId: note.id.toString() } }"
		:force-display-actions="true"
		@click="onNoteSelected(note.id)"
	>
		<template #actions>
			<ActionButton
				:icon="actionFavoriteIcon"
				:close-after-click="true"
				@click="onToggleFavorite"
			>
				{{ actionFavoriteText }}
			</ActionButton>
			<ActionButton
				:disabled="note.readonly"
				:icon="actionDeleteIcon"
				:close-after-click="true"
				@click="onDeleteNote"
			>
				{{ t('notes', 'Delete note') }}
			</ActionButton>
		</template>
	</ListItem>
</template>

<script>
import { ActionButton, ListItem } from '@nextcloud/vue'

import { showError } from '@nextcloud/dialogs'
import { setFavorite, setTitle, fetchNote, deleteNote } from '../NotesService'
import { routeIsNewNote } from '../Util'

export default {
	name: 'NoteItem',

	components: {
		ActionButton,
		ListItem,
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
		}
	},

	computed: {
		isSelected() {
			return this.$store.getters.getSelectedNote() === this.note.id
		},

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
				})
		},

		onNoteSelected(noteId) {
			this.$store.commit('setSelectedNote', noteId)
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
				})
			} catch (e) {
				showError(this.t('notes', 'Error during preparing note for deletion.'))
				this.loading.delete = false
			}
		},
	},
}
</script>

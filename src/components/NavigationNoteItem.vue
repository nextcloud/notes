<template>
	<AppNavigationItem
		:title="title"
		:icon="icon"
		:menu-open.sync="actionsOpen"
		:to="{ name: 'note', params: { noteId: note.id.toString() } }"
		:class="{ actionsOpen }"
	>
		<template slot="actions">
			<ActionButton :icon="actionFavoriteIcon" @click="onToggleFavorite">
				{{ actionFavoriteText }}
			</ActionButton>
			<ActionButton icon="icon-files-dark" @click="onCategorySelected">
				{{ actionCategoryText }}
			</ActionButton>
			<ActionButton :icon="actionDeleteIcon" @click="onDeleteNote">
				{{ t('notes', 'Delete note') }}
			</ActionButton>
		</template>
	</AppNavigationItem>
</template>

<script>
import {
	ActionButton,
	AppNavigationItem,
} from '@nextcloud/vue'
import NotesService from '../NotesService'

export default {
	name: 'NavigationNoteItem',

	components: {
		ActionButton,
		AppNavigationItem,
	},

	props: {
		note: {
			type: Object,
			required: true,
		},
	},

	data: function() {
		return {
			loading: {
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
			return NotesService.categoryLabel(this.note.category)
		},

		actionDeleteIcon() {
			return 'icon-delete' + (this.loading.delete ? ' loading' : '')
		},
	},

	methods: {
		onToggleFavorite() {
			this.loading.favorite = true
			NotesService.setFavorite(this.note.id, !this.note.favorite)
				.catch(() => {
				})
				.finally(() => {
					this.loading.favorite = false
					this.actionsOpen = false
				})
		},

		onCategorySelected() {
			this.actionsOpen = false
			this.$emit('category-selected', this.note.category)
		},

		onDeleteNote() {
			this.loading.delete = true
			NotesService.deleteNote(this.note.id)
				.then(() => {
					this.$emit('note-deleted')
				})
				.catch(() => {
				})
				.finally(() => {
					this.loading.delete = false
					this.actionsOpen = false
				})
			// TODO implement undo
		},
	},
}
</script>

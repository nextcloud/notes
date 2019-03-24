<template>
	<app-navigation-item :item="item" />
</template>

<script>
import {
	AppNavigationItem,
} from 'nextcloud-vue'
import NotesService from './NotesService'

export default {
	name: 'NavigationNoteItem',

	components: {
		AppNavigationItem,
	},

	props: {
		note: { type: Object, default: null },
	},

	data: function() {
		return {
			loading: {
				favorite: false,
				delete: false,
			},
		}
	},

	computed: {
		item() {
			return {
				text: this.note.title,
				icon: this.note.favorite ? 'nav-icon icon-starred' : '',
				router: {
					name: 'note',
					params: {
						noteId: this.note.id.toString(),
					},
				},
				utils: {
					actions: [
						{
							text: t('notes', 'Favorite'),
							icon: 'icon-starred' + (this.loading.favorite ? ' loading' : ''),
							action: this.onToggleFavorite,
						},
						{
							text: t('notes', 'Delete note'),
							icon: 'icon-delete' + (this.loading.delete ? ' loading' : ''),
							action: this.onDeleteNote,
						},
					],
				},
			}
		},
	},

	methods: {
		onToggleFavorite() {
			this.loading.favorite = true
			NotesService.setFavorite(this.note.id, !this.note.favorite)
				.then(() => {
					this.loading.favorite = false
					// TODO close action menu
				})
		},

		onDeleteNote() {
			// TODO disable editor
			this.loading.delete = true
			NotesService.deleteNote(this.note.id)
				.then(() => {
					this.loading.delete = false
					// TODO close action menu
					this.$emit('note-deleted')
				})
			// TODO implement undo
		},
	},
}
</script>

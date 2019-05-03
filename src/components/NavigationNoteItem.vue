<template>
	<AppNavigationItem :item="item" :menu-open.sync="menuOpen" />
</template>

<script>
import {
	AppNavigationItem,
} from 'nextcloud-vue'
import NotesService from '../NotesService'

export default {
	name: 'NavigationNoteItem',

	components: {
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
			menuOpen: false,
		}
	},

	computed: {
		item() {
			let icon = ''
			if (this.note.error) {
				icon = 'nav-icon icon-error-color'
			} else if (this.note.favorite) {
				icon = 'nav-icon icon-starred'
			}
			return {
				text: this.note.title + (this.note.unsaved ? ' *' : ''),
				icon: icon,
				router: {
					name: 'note',
					params: {
						noteId: this.note.id.toString(),
					},
				},
				utils: {
					actions: [
						{
							text: this.tn('Favorite'),
							icon: 'icon-starred' + (this.loading.favorite ? ' loading' : ''),
							action: this.onToggleFavorite,
						},
						{
							text: this.tn('Delete note'),
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
				.catch(() => {
				})
				.finally(() => {
					this.loading.favorite = false
					this.menuOpen = false
				})
		},

		onDeleteNote() {
			// TODO disable editor
			this.loading.delete = true
			NotesService.deleteNote(this.note.id)
				.then(() => {
					this.$emit('note-deleted')
				})
				.catch(() => {
				})
				.finally(() => {
					this.loading.delete = false
					this.menuOpen = false
				})
			// TODO implement undo
		},
	},
}
</script>

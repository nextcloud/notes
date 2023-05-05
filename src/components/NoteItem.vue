<template>
	<NcListItem
		:title="title"
		:active="isSelected"
		:to="{ name: 'note', params: { noteId: note.id.toString() } }"
		@click="onNoteSelected(note.id)"
	>
		<template #subtitle>
			{{ categoryTitle }}
		</template>
		<template #icon>
			<AlertOctagonIcon v-if="note.error"
				slot="icon"
				:size="20"
				fill-color="#E9322D"
			/>
			<StarIcon v-else-if="note.favorite"
				slot="icon"
				:size="20"
				fill-color="#FC0"
			/>
			<FileDocumentOutlineIcon v-else
				slot="icon"
				:size="20"
				fill-color="var(--color-text-lighter)"
			/>
		</template>
		<template #actions>
			<NcActionButton :icon="actionFavoriteIcon" @click="onToggleFavorite">
				{{ actionFavoriteText }}
			</NcActionButton>
			<NcActionButton @click="onToggleSidebar">
				<SidebarIcon slot="icon" :size="20" />
				{{ t('notes', 'Details') }}
			</NcActionButton>
			<NcActionButton v-if="!note.readonly" :icon="actionDeleteIcon" @click="onDeleteNote">
				{{ t('notes', 'Delete note') }}
			</NcActionButton>
			<NcActionSeparator />
			<NcActionButton icon="icon-files-dark" @click="onCategorySelected">
				{{ actionCategoryText }}
			</NcActionButton>
		</template>
	</NcListItem>
</template>

<script>
import { NcListItem, NcActionButton } from '@nextcloud/vue'
import AlertOctagonIcon from 'vue-material-design-icons/AlertOctagon.vue'
import FileDocumentOutlineIcon from 'vue-material-design-icons/FileDocumentOutline.vue'
import StarIcon from 'vue-material-design-icons/Star.vue'
import SidebarIcon from 'vue-material-design-icons/PageLayoutSidebarRight.vue'
import { categoryLabel, routeIsNewNote } from '../Util.js'
import { showError } from '@nextcloud/dialogs'
import store from '../store.js'
import { setFavorite, setTitle, fetchNote, deleteNote } from '../NotesService.js'

export default {
	name: 'NoteItem',

	components: {
		AlertOctagonIcon,
		FileDocumentOutlineIcon,
		NcActionButton,
		NcListItem,
		SidebarIcon,
		StarIcon,
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
			},
		}
	},

	computed: {
		isSelected() {
			return this.$store.getters.getSelectedNote() === this.note.id
		},

		title() {
			return this.note.title + (this.note.unsaved ? ' *' : '')
		},

		categoryTitle() {
			return categoryLabel(this.note.category)
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
		onNoteSelected(noteId) {
			this.$emit('note-selected', noteId)
		},
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
		onToggleSidebar() {
			this.actionsOpen = false
			store.commit('setSidebarOpen', !store.state.app.sidebarOpen)
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
<style scoped>
.material-design-icon {
	width: 44px;
}
</style>

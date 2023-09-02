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

			<NcActionButton v-if="!renaming" @click="startRenaming">
				<PencilIcon slot="icon" :size="20" />
				{{ t('notes', 'Rename') }}
			</NcActionButton>
			<NcActionInput v-else
				v-model.trim="newTitle"
				:disabled="!renaming"
				:placeholder="t('notes', 'Rename note')"
				:show-trailing-button="true"
				@input="onInputChange($event)"
				@submit="onRename"
			>
				<PencilIcon slot="icon" :size="20" />
			</NcActionInput>

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
import { NcListItem, NcActionButton, NcActionSeparator, NcActionInput } from '@nextcloud/vue'
import AlertOctagonIcon from 'vue-material-design-icons/AlertOctagon.vue'
import FileDocumentOutlineIcon from 'vue-material-design-icons/FileDocumentOutline.vue'
import PencilIcon from 'vue-material-design-icons/Pencil.vue'
import StarIcon from 'vue-material-design-icons/Star.vue'
import { categoryLabel, routeIsNewNote } from '../Util.js'
import { showError } from '@nextcloud/dialogs'
import { setFavorite, setTitle, fetchNote, deleteNote } from '../NotesService.js'

export default {
	name: 'NoteItem',

	components: {
		AlertOctagonIcon,
		FileDocumentOutlineIcon,
		NcActionButton,
		NcListItem,
		StarIcon,
		NcActionSeparator,
		NcActionInput,
		PencilIcon,
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
			newTitle: '',
			renaming: false,
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
		startRenaming() {
			this.renaming = true
			this.newTitle = this.note.title
			this.$emit('start-renaming', this.note.id)
		},
		onInputChange(event) {
			this.newTitle = event.target.value.toString()
		},
		async onRename() {
			const newTitle = this.newTitle.toString()
			if (!newTitle) {
				return
			}
			this.loading.note = true
			setTitle(this.note.id, newTitle)
				.then(() => {
					this.newTitle = ''
				})
				.catch((e) => {
					console.error('Failed to rename note', e)
					showError(this.t('notes', 'Error while renaming note.'))
				})
				.finally(() => {
					this.loading.note = false
					this.renaming = false
				})

			if (routeIsNewNote(this.$route)) {
				this.$router.replace({
					name: 'note',
					params: { noteId: this.note.id.toString() },
				})
			}
			this.renaming = false

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

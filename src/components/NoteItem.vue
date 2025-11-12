<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcListItem
		:name="title"
		:active="isSelected"
		:to="{ name: 'note', params: { noteId: note.id.toString() } }"
		one-line
		@update:menuOpen="onMenuChange"
		@click="onNoteSelected(note.id)"
	>
		<template #subtitle>
			{{ categoryTitle }}
		</template>
		<template #icon>
			<AlertOctagonOutlineIcon v-if="note.error"
				slot="icon"
				:size="20"
				fill-color="#E9322D"
			/>
			<StarOutlineIcon v-else-if="note.favorite"
				slot="icon"
				:size="20"
				fill-color="#FC0"
			/>
			<FileDocumentOutlineIcon v-else
				slot="icon"
				:size="20"
			/>
		</template>
		<template v-if="isShared" #indicator>
			<ShareVariantOutlineIcon :size="16" fill-color="#0082c9" />
		</template>
		<template #actions>
			<NcActionButton :icon="actionFavoriteIcon" @click="onToggleFavorite">
				{{ actionFavoriteText }}
			</NcActionButton>

			<NcActionButton @click="onToggleSharing">
				<template #icon>
					<ShareVariantOutlineIcon :size="20" />
				</template>
				{{ t('notes', 'Share') }}
			</NcActionButton>

			<NcActionButton v-if="!showCategorySelect" @click="showCategorySelect = true">
				<template #icon>
					<FolderOutlineIcon :size="20" />
				</template>
				{{ categoryTitle }}
			</NcActionButton>
			<NcActionInput
				v-else
				:value="note.category"
				type="multiselect"
				label="label"
				track-by="id"
				:multiple="false"
				:options="categories"
				:disabled="loading.category"
				:taggable="true"
				@input="onCategoryChange"
				@search-change="onCategoryChange"
			>
				<template #icon>
					<FolderOutlineIcon :size="20" />
				</template>
				{{ t('notes', 'Change category') }}
			</NcActionInput>

			<NcActionButton v-if="!renaming" @click="startRenaming">
				<PencilOutlineIcon slot="icon" :size="20" />
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
				<PencilOutlineIcon slot="icon" :size="20" />
			</NcActionInput>

			<NcActionSeparator />

			<NcActionButton v-if="!note.readonly"
				:icon="actionDeleteIcon"
				:close-after-click="true"
				@click="onDeleteNote"
			>
				{{ t('notes', 'Delete note') }}
			</NcActionButton>
		</template>
	</NcListItem>
</template>

<script>
import NcListItem from '@nextcloud/vue/components/NcListItem'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionSeparator from '@nextcloud/vue/components/NcActionSeparator'
import NcActionInput from '@nextcloud/vue/components/NcActionInput'
import AlertOctagonOutlineIcon from 'vue-material-design-icons/AlertOctagonOutline.vue'
import FileDocumentOutlineIcon from 'vue-material-design-icons/FileDocumentOutline.vue'
import FolderOutlineIcon from 'vue-material-design-icons/FolderOutline.vue'
import PencilOutlineIcon from 'vue-material-design-icons/PencilOutline.vue'
import StarOutlineIcon from 'vue-material-design-icons/StarOutline.vue'
import { categoryLabel, routeIsNewNote } from '../Util.js'
import { showError } from '@nextcloud/dialogs'
import { setFavorite, setTitle, fetchNote, deleteNote, setCategory } from '../NotesService.js'
import ShareVariantOutlineIcon from 'vue-material-design-icons/ShareVariantOutline.vue'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'

export default {
	name: 'NoteItem',

	components: {
		AlertOctagonOutlineIcon,
		FileDocumentOutlineIcon,
		FolderOutlineIcon,
		NcActionButton,
		NcListItem,
		StarOutlineIcon,
		NcActionSeparator,
		NcActionInput,
		PencilOutlineIcon,
		ShareVariantOutlineIcon,
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
				category: false,
			},
			newTitle: '',
			renaming: false,
			showCategorySelect: false,
			isShareCreated: false,
		}
	},

	computed: {
		isSelected() {
			return this.$store.getters.getSelectedNote() === this.note.id
		},
		isShared() {
			return this.note.isShared || this.isShareCreated
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
		categories() {
			return [
				{
					id: '',
					label: categoryLabel(''),
				},
				...this.$store.getters.getCategories(0, false).map((category) => ({
					id: category,
					label: categoryLabel(category),
				})),
			]
		},
	},

	mounted() {
		subscribe('files_sharing:share:created', this.onShareCreated)
	},

	destroyed() {
		unsubscribe('files_sharing:share:created', this.onShareCreated)
	},

	methods: {
		onMenuChange(state) {
			this.actionsOpen = state
			this.showCategorySelect = false
		},
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
		async onCategoryChange(result) {
			this.showCategorySelect = false
			const category = result?.id ?? result?.label ?? null
			if (category !== null && this.note.category !== category) {
				this.loading.category = true
				await setCategory(this.note.id, category)
				this.loading.category = false
			}
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
		onToggleSharing() {
			if (window?.OCA?.Files?.Sidebar?.setActiveTab) {
				emit('toggle-navigation', { open: false })
				setTimeout(() => {
					window.dispatchEvent(new Event('resize'))
				}, 200)
				window.OCA.Files.Sidebar.setActiveTab('sharing')
				window.OCA.Files.Sidebar.open(this.note.internalPath)
			}
		},
		async onShareCreated(event) {
			const { share } = event

			if (share.fileSource === this.note.id) {
				this.isShareCreated = true
			}
		},
	},
}
</script>
<style lang="scss" scoped>
.material-design-icon {
	width: var(--default-clickable-area);
	.list-item__wrapper--active & {
		color: var(--color-primary-element-text) !important;
	}
}
</style>

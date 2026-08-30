<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppContent paneConfigKey="note" :showDetails="showNote" @update:showDetails="hideNote">
		<template #list>
			<NcAppContentList class="content-list">
				<div class="content-list__search">
					<div class="content-list__actions">
						<NcButton variant="primary" :disabled="creatingNote || loadingTemplates" @click="onNewNote">
							<template #icon>
								<PlusIcon :size="20" />
							</template>
							{{ t('notes', 'New note') }}
						</NcButton>
					</div>
					<NcTextField
						v-model="searchText"
						:label="t('notes', 'Search for notes')"
						:showTrailingButton="searchText !== ''"
						trailingButtonIcon="close"
						:trailingButtonLabel="t('Clear search')"
						@trailingButtonClick="searchText = ''"
					/>
				</div>

				<NotesList v-if="groupedNotes.length === 1"
					:notes="groupedNotes[0].notes"
					:showCategoryTitle="category === null"
					@noteSelected="onNoteSelected"
					@noteDeleted="onNoteDeleted"
				/>
				<template v-for="(group, idx) in groupedNotes" v-else :key="idx">
					<NotesCaption v-if="group.category && category !== group.category"
						:key="group.category"
						:name="categoryToLabel(group.category)"
					/>
					<NotesCaption v-if="group.timeslot"
						:key="group.timeslot"
						:name="group.timeslot"
					/>
					<NotesList
						:notes="group.notes"
						:showCategoryTitle="category === null"
						@noteSelected="onNoteSelected"
						@noteDeleted="onNoteDeleted"
					/>
				</template>
				<div
					v-show="displayedNotes.length != filteredNotes.length"
					ref="endOfNotesLabel"
					class="loading-label"
				>
					{{ t('notes', 'Loading …') }}
				</div>
				<div v-if="getFilteredTotalCount > 0" class="content-list__search-more">
					<NcButton @click="onCategorySelected(null)">
						{{ t('notes', 'Find in all categories') }}
					</NcButton>
				</div>
			</NcAppContentList>
		</template>

		<NcAppContentDetails>
			<Note v-if="showNote" :noteId="noteId" @noteDeleted="onNoteDeleted" />
		</NcAppContentDetails>

		<TemplatePicker v-if="templatePickerOpen"
			:templates="templates"
			:creating="creatingNote"
			@close="templatePickerOpen = false"
			@select="onTemplateSelected"
		/>
	</NcAppContent>
</template>

<script>

import { showError } from '@nextcloud/dialogs'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppContentDetails from '@nextcloud/vue/components/NcAppContentDetails'
import NcAppContentList from '@nextcloud/vue/components/NcAppContentList'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import Note from './Note.vue'
import NotesCaption from './NotesCaption.vue'
import NotesList from './NotesList.vue'
import TemplatePicker from './TemplatePicker.vue'
import logger from '../Logger.js'
import { createNote } from '../NotesService.js'
import store from '../store.js'
import { fetchNoteTemplates, fetchTemplateContent } from '../TemplateService.js'
import { categoryLabel, rootCategory } from '../Util.js'

export default {
	name: 'NotesView',

	components: {
		NcAppContent,
		NcAppContentList,
		NcAppContentDetails,
		NcButton,
		NcTextField,
		Note,
		NotesList,
		NotesCaption,
		PlusIcon,
		TemplatePicker,
	},

	props: {
		noteId: {
			type: String,
			required: true,
		},
	},

	emits: [
		'noteDeleted',
	],

	data() {
		return {
			timeslots: [],
			monthFormat: new Intl.DateTimeFormat(OC.getLanguage(), { month: 'long', year: 'numeric' }),
			lastYear: new Date(new Date().getFullYear() - 1, 0),
			showFirstNotesOnly: true,
			showNote: true,
			searchText: '',
			creatingNote: false,
			templates: [],
			templatePickerOpen: false,
			loadingTemplates: false,
		}
	},

	computed: {
		getFilteredTotalCount() {
			return store.notes.getFilteredTotalCount()
		},

		category() {
			return store.notes.getSelectedCategory()
		},

		note() {
			const noteId = Number.parseInt(this.noteId, 10)
			return Number.isFinite(noteId) ? store.notes.getNote(noteId) : null
		},

		noteCategory() {
			return this.note?.category ?? null
		},

		filteredNotes() {
			return store.notes.getFilteredNotes()
		},

		displayedNotes() {
			if (this.filteredNotes.length > 40 && this.showFirstNotesOnly) {
				return this.filteredNotes.slice(0, 30)
			} else {
				return this.filteredNotes
			}
		},

		// group notes by time ("All notes") or by category (if category chosen)
		groupedNotes() {
			if (this.category === null) {
				return this.displayedNotes.reduce((g, note) => {
					const timeslot = this.getTimeslotFromNote(note)
					if (g.length === 0 || g[g.length - 1].timeslot !== timeslot) {
						g.push({ timeslot, notes: [] })
					}
					g[g.length - 1].notes.push(note)
					return g
				}, [])
			} else {
				return this.displayedNotes.reduce((g, note) => {
					if (g.length === 0 || g[g.length - 1].category !== note.category) {
						g.push({ category: note.category, notes: [] })
					}
					g[g.length - 1].notes.push(note)
					return g
				}, [])
			}
		},
	},

	watch: {
		category() {
			this.showFirstNotesOnly = true
			this.hideVisibleNoteOutsideSelectedCategory()
		},

		noteId() {
			this.showNote = true
			this.updateVisibleNoteSelection()
		},

		noteCategory() {
			this.updateVisibleNoteSelection()
		},

		showNote() {
			this.updateVisibleNoteSelection()
		},

		searchText(value) { store.app.updateSearchText(value) },
	},

	created() {
		this.updateTimeslots()
		this.updateVisibleNoteSelection()
		setInterval(this.updateTimeslots, 1000 * 60)
	},

	mounted() {
		this.setupEndOfNotesObserver()
	},

	beforeUnmount() {
		this.endOfNotesObserver.disconnect()
		this.clearVisibleNoteSelection()
	},

	methods: {
		clearVisibleNoteSelection() {
			if (store.notes.getSelectedNote() !== null) {
				store.notes.setSelectedNote(null)
			}
		},

		updateVisibleNoteSelection() {
			const noteId = Number.parseInt(this.noteId, 10)
			if (!this.showNote || !Number.isFinite(noteId)) {
				this.clearVisibleNoteSelection()
				return
			}

			if (store.notes.getSelectedNote() !== noteId) {
				store.notes.setSelectedNote(noteId)
			}

			if (this.note && store.notes.getSelectedCategory() !== null) {
				const category = rootCategory(this.note.category)
				if (store.notes.getSelectedCategory() !== category) {
					store.notes.setSelectedCategory(category)
				}
			}
		},

		hideVisibleNoteOutsideSelectedCategory() {
			if (!this.showNote || !this.note) {
				return
			}

			const selectedCategory = store.notes.getSelectedCategory()
			if (selectedCategory !== null && selectedCategory !== rootCategory(this.note.category)) {
				this.showNote = false
			}
		},

		updateTimeslots() {
			const now = new Date()
			// define the time groups we want to allow
			this.timeslots = [
				{ t: new Date(now.getFullYear(), now.getMonth(), now.getDate()), l: t('notes', 'Today') },
				{ t: new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1), l: t('notes', 'Yesterday') },
				{ t: new Date(now.getFullYear(), now.getMonth(), now.getDate() - now.getDay()), l: t('notes', 'This week') },
				{ t: new Date(now.getFullYear(), now.getMonth(), now.getDate() - now.getDay() - 7), l: t('notes', 'Last week') },
				{ t: new Date(now.getFullYear(), now.getMonth(), 1), l: t('notes', 'This month') },
				{ t: new Date(now.getFullYear(), now.getMonth() - 1, 1), l: t('notes', 'Last month') },
			]
		},

		categoryTitle(category) {
			return categoryLabel(category)
		},

		categoryToLabel(category) {
			return categoryLabel(category.substring(this.category.length + 1))
		},

		getTimeslotFromNote(note) {
			if (note.favorite) {
				return ''
			}
			const t = note.modified * 1000
			const timeslot = this.timeslots.find((timeslot) => t >= timeslot.t.getTime())
			if (timeslot !== undefined) {
				return timeslot.l
			} else if (t >= this.lastYear) {
				return this.monthFormat.format(new Date(t))
			} else {
				return new Date(t).getFullYear().toString()
			}
		},

		setupEndOfNotesObserver() {
			this.endOfNotesObserver = new IntersectionObserver((entries) => {
				if (entries[0].isIntersecting) {
					this.showFirstNotesOnly = false
				}
			})
			this.$nextTick(() => {
				this.endOfNotesObserver.observe(this.$refs.endOfNotesLabel)
			})
		},

		onCategorySelected(category) {
			store.notes.setSelectedCategory(category)
		},

		async onNewNote() {
			if (this.creatingNote || this.loadingTemplates || this.templatePickerOpen) {
				return
			}

			this.loadingTemplates = true
			try {
				this.templates = await fetchNoteTemplates()
			} finally {
				this.loadingTemplates = false
			}

			if (this.templates.length === 0) {
				this.createNoteFromTemplate(null)
				return
			}
			this.templatePickerOpen = true
		},

		onTemplateSelected(template) {
			this.createNoteFromTemplate(template)
		},

		createNoteFromTemplate(template) {
			if (this.creatingNote) {
				return
			}
			this.creatingNote = true

			this.templateContent(template)
				.then((content) => createNote(store.notes.getSelectedCategory(), '', content))
				.then((note) => {
					this.templatePickerOpen = false
					this.$router.push({
						name: 'note',
						params: { noteId: note.id.toString() },
						query: { new: null },
					})
				})
				.catch(() => {
					// createNote() reports its own failures
				})
				.finally(() => {
					this.creatingNote = false
				})
		},

		/**
		 * @param {object|null} template chosen template, null for a blank note
		 * @return {Promise<string>} the new note's initial content
		 */
		async templateContent(template) {
			if (!template) {
				return ''
			}
			try {
				return await fetchTemplateContent(template.templateId)
			} catch (error) {
				logger.error('Reading the note template has failed', { error })
				showError(t('notes', 'Could not read the template. Creating an empty note instead.'))
				return ''
			}
		},

		hideNote() {
			this.showNote = false
		},

		onNoteDeleted(note) {
			this.$emit('noteDeleted', note)
		},

		onNoteSelected() {
			this.showNote = true
		},
	},
}
</script>

<style lang="scss" scoped>
.content-list {
	padding: 0 4px;
	height: 100%;
	overflow-y: auto;
}

.content-list__search {
	padding: var(--app-navigation-padding);
	padding-inline-start: 50px;
	position: sticky;
	top: 0;
	background-color: var(--color-main-background-translucent);
	z-index: 1;

	input {
		width: 100%;
	}
}

.content-list__actions {
	display: flex;
	margin-bottom: 6px;
}

.content-list__actions :deep(.button-vue) {
	width: 100%;
	justify-content: center;
}

.content-list__search-more {
	.button {
		margin: auto;
	}
}

.app-content-details {
	height: 100%;
	overflow: auto;
}

.loading-label {
	color: var(--color-text-lighter);
	text-align: center;
}

.loading-label::before {
	content: ' ';
	height: 16px;
	width: 16px;
	display: inline-block;
	border-radius: 100%;
	-webkit-animation: rotate 0.8s infinite linear;
	animation: rotate 0.8s infinite linear;
	-webkit-transform-origin: center;
	-ms-transform-origin: center;
	transform-origin: center;
	border: 2px solid var(--color-loading-light);
	border-top-color: var(--color-loading-dark);
	vertical-align: top;
	margin-inline-end: 5px;
}
</style>

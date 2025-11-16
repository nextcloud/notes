<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppContent pane-config-key="note" :show-details="showNote" @update:showDetails="hideNote">
		<template slot="list">
			<NcAppContentList class="content-list">
				<div class="content-list__search">
					<NcTextField
						:value.sync="searchText"
						:label="t('notes', 'Search for notes')"
						:show-trailing-button="searchText !== ''"
						trailing-button-icon="close"
						:trailing-button-label="t('Clear search')"
						@trailing-button-click="searchText=''"
					/>
				</div>

				<NotesList v-if="groupedNotes.length === 1"
					:notes="groupedNotes[0].notes"
					@note-selected="onNoteSelected"
				/>
				<template v-for="(group, idx) in groupedNotes" v-else>
					<NotesCaption v-if="group.category && category!==group.category"
						:key="group.category"
						:name="categoryToLabel(group.category)"
					/>
					<NotesCaption v-if="group.timeslot"
						:key="group.timeslot"
						:name="group.timeslot"
					/>
					<NotesList
						:key="idx"
						:notes="group.notes"
						@note-selected="onNoteSelected"
					/>
				</template>
				<div
					v-if="displayedNotes.length != filteredNotes.length"
					v-observe-visibility="onEndOfNotes"
					class="loading-label"
				>
					{{ t('notes', 'Loading …') }}
				</div>
				<div v-if="getFilteredTotalCount > 0" class="content-list__search-more">
					<NcButton @click="onCategorySelected(null)">
						{{ t('notes', 'Find in all categories') }}
					</NcButton>
				</div>
			</NcAppContentList>
		</template>

		<NcAppContentDetails>
			<Note v-if="showNote" :note-id="noteId" @note-deleted="onNoteDeleted" />
		</NcAppContentDetails>
	</NcAppContent>
</template>

<script>

import {
	NcAppContent,
	NcAppContentList,
	NcAppContentDetails,
	NcButton,
	NcTextField,
} from '@nextcloud/vue'
import { categoryLabel } from '../Util.js'
import NotesList from './NotesList.vue'
import NotesCaption from './NotesCaption.vue'
import store from '../store.js'
import Note from './Note.vue'

import { ObserveVisibility } from 'vue-observe-visibility'

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
	},

	directives: {
		'observe-visibility': ObserveVisibility,
	},

	props: {
		noteId: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			timeslots: [],
			monthFormat: new Intl.DateTimeFormat(OC.getLanguage(), { month: 'long', year: 'numeric' }),
			lastYear: new Date(new Date().getFullYear() - 1, 0),
			displayedNotesCount: 50,
			isLoadingMore: false,
			showNote: true,
			searchText: '',
		}
	},

	computed: {
		getFilteredTotalCount() {
			return store.getters.getFilteredTotalCount()
		},

		category() {
			return store.getters.getSelectedCategory()
		},

		filteredNotes() {
			return store.getters.getFilteredNotes()
		},

		displayedNotes() {
			// Show notes up to displayedNotesCount, incrementally loading more as user scrolls
			return this.filteredNotes.slice(0, this.displayedNotesCount)
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
			this.displayedNotesCount = 50
			this.isLoadingMore = false
		},
		searchText(value) {
			store.commit('updateSearchText', value)
			this.displayedNotesCount = 50
			this.isLoadingMore = false
		},
	},

	created() {
		this.updateTimeslots()
		setInterval(this.updateTimeslots, 1000 * 60)
	},

	methods: {
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
			const timeslot = this.timeslots.find(timeslot => t >= timeslot.t.getTime())
			if (timeslot !== undefined) {
				return timeslot.l
			} else if (t >= this.lastYear) {
				return this.monthFormat.format(new Date(t))
			} else {
				return new Date(t).getFullYear().toString()
			}
		},

		onEndOfNotes(isVisible) {
			// Prevent rapid-fire loading by checking if we're already loading a batch
			if (!isVisible || this.isLoadingMore || this.displayedNotesCount >= this.filteredNotes.length) {
				return
			}

			// Set loading flag to prevent concurrent loads
			this.isLoadingMore = true

			// Use nextTick to ensure the loading flag is set before incrementing
			this.$nextTick(() => {
				// Load 50 more notes at a time
				this.displayedNotesCount = Math.min(
					this.displayedNotesCount + 50,
					this.filteredNotes.length
				)

				// Reset loading flag after DOM update
				this.$nextTick(() => {
					this.isLoadingMore = false
				})
			})
		},

		onCategorySelected(category) {
			store.commit('setSelectedCategory', category)
		},

		hideNote() {
			this.showNote = false
		},

		onNoteDeleted(note) {
			this.$emit('note-deleted', note)
		},

		onNoteSelected(noteId) {
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
	padding: 4px;
	padding-inline-start: 50px;
	position: sticky;
	top: 0;
	background-color: var(--color-main-background-translucent);
	z-index: 1;

	input {
		width: 100%;
	}
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

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
					v-if="hasMoreNotes"
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

import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppContentList from '@nextcloud/vue/components/NcAppContentList'
import NcAppContentDetails from '@nextcloud/vue/components/NcAppContentDetails'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { categoryLabel } from '../Util.js'
import { fetchNotes, searchNotes } from '../NotesService.js'
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
			searchDebounceTimer: null,
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

		chunkCursor() {
			// Get the cursor for next chunk from store
			return store.state.sync.chunkCursor
		},

		hasMoreNotes() {
			// There are more notes if either:
			// 1. We have more notes locally that aren't displayed yet, OR
			// 2. There's a cursor indicating more notes on the server
			return this.displayedNotes.length !== this.filteredNotes.length || this.chunkCursor !== null
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
			// Update store for client-side filtering (getFilteredNotes uses this)
			store.commit('updateSearchText', value)

			// Clear any existing debounce timer
			if (this.searchDebounceTimer) {
				clearTimeout(this.searchDebounceTimer)
				this.searchDebounceTimer = null
			}

			// Reset display state
			this.displayedNotesCount = 50
			this.isLoadingMore = false

			// Debounce search API calls (300ms delay)
			this.searchDebounceTimer = setTimeout(async () => {
				console.log('[NotesView] Search text changed:', value)

				if (value && value.trim() !== '') {
					// Perform server-side search
					console.log('[NotesView] Initiating server-side search')
					try {
						await searchNotes(value.trim(), 50, null)
						// Update cursor after search completes
						console.log('[NotesView] Search completed')
					} catch (err) {
						console.error('[NotesView] Search failed:', err)
					}
				} else {
					// Empty search - revert to normal pagination
					console.log('[NotesView] Empty search - reverting to pagination')
					// Clear notes and refetch (clearSyncCache not needed - fetchNotes will set new cursor)
					store.commit('removeAllNotes')
					try {
						await fetchNotes(50, null)
						// Reset display count after fetch completes
						this.displayedNotesCount = 50
						console.log('[NotesView] Reverted to normal notes view')
					} catch (err) {
						console.error('[NotesView] Failed to revert to normal view:', err)
					}
				}
			}, 300)
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

		async onEndOfNotes(isVisible) {
			console.log('[NotesView.onEndOfNotes] Triggered, isVisible:', isVisible, 'isLoadingMore:', this.isLoadingMore)
			// Prevent rapid-fire loading by checking if we're already loading a batch
			if (!isVisible || this.isLoadingMore) {
				console.log('[NotesView.onEndOfNotes] Skipping - not visible or already loading')
				return
			}

			// Set loading flag to prevent concurrent loads
			this.isLoadingMore = true

			try {
				// Check if there are more notes to fetch from the server
				const chunkCursor = store.state.sync.chunkCursor
				const isSearchMode = this.searchText && this.searchText.trim() !== ''
				console.log('[NotesView.onEndOfNotes] Current cursor:', chunkCursor, 'searchMode:', isSearchMode)
				console.log('[NotesView.onEndOfNotes] displayedNotesCount:', this.displayedNotesCount, 'filteredNotes.length:', this.filteredNotes.length)

				if (chunkCursor) {
					// Fetch next chunk from the API (using search or normal fetch based on mode)
					console.log('[NotesView.onEndOfNotes] Fetching next chunk from API')
					const data = isSearchMode
						? await searchNotes(this.searchText.trim(), 50, chunkCursor)
						: await fetchNotes(50, chunkCursor)
					console.log('[NotesView.onEndOfNotes] Fetch complete, data:', data)

					if (data && data.noteIds) {
						// Update cursor for next fetch
						console.log('[NotesView.onEndOfNotes] Updating cursor to:', data.chunkCursor)
						store.commit('setNotesChunkCursor', data.chunkCursor || null)

						// Increment display count to show newly loaded notes
						const newCount = Math.min(
							this.displayedNotesCount + 50,
							this.filteredNotes.length
						)
						console.log('[NotesView.onEndOfNotes] Updating displayedNotesCount from', this.displayedNotesCount, 'to', newCount)
						this.displayedNotesCount = newCount
					}
				} else if (this.displayedNotesCount < this.filteredNotes.length) {
					// No more chunks to fetch, but we have cached notes to display
					console.log('[NotesView.onEndOfNotes] No cursor, but have cached notes to display')
					this.$nextTick(() => {
						const newCount = Math.min(
							this.displayedNotesCount + 50,
							this.filteredNotes.length
						)
						console.log('[NotesView.onEndOfNotes] Updating displayedNotesCount from', this.displayedNotesCount, 'to', newCount)
						this.displayedNotesCount = newCount
					})
				} else {
					console.log('[NotesView.onEndOfNotes] All notes loaded, nothing to do')
				}
			} finally {
				// Reset loading flag after operation completes
				this.$nextTick(() => {
					console.log('[NotesView.onEndOfNotes] Resetting isLoadingMore flag')
					this.isLoadingMore = false
				})
			}
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

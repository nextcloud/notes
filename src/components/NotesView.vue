<template>
	<NcAppContent pane-config-key="note" :show-details="showNote" @update:showDetails="hideNote">
		<template slot="list">
			<NcAppContentList class="content-list">
				<div class="content-list__search">
					<input type="search" :placeholder="t('notes', 'Search for notes')" @input="event => searchText = event.target.value">
				</div>

				<NotesList v-if="groupedNotes.length === 1"
					:notes="groupedNotes[0].notes"
					@note-selected="onNoteSelected"
				/>
				<template v-for="(group, idx) in groupedNotes" v-else>
					<NotesCaption v-if="group.category && category!==group.category"
						:key="group.category"
						:title="categoryToLabel(group.category)"
					/>
					<NotesCaption v-if="group.timeslot"
						:key="group.timeslot"
						:title="group.timeslot"
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
			showFirstNotesOnly: true,
			showNote: true,
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
		searchText: {
			get() {
				return store.state.searchText
			},
			set(value) {
				store.commit('updateSearchText', value)
			},
		},
	},

	watch: {
		category() { this.showFirstNotesOnly = true },
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
			if (isVisible) {
				this.showFirstNotesOnly = false
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
}

.content-list__search {
	padding: 4px;
	padding-left: 50px;
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
	margin-right: 5px;
}
</style>

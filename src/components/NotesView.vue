<template>
	<div class="notes-view">
		<div class="spacer" />
		<div class="notes-list">
			<NotesList v-if="groupedNotes.length === 1"
				:notes="groupedNotes[0].notes"
				@note-deleted="$emit('note-deleted', $event)"
			/>
			<template v-for="(group, idx) in groupedNotes" v-else>
				<AppNavigationCaption v-if="group.category && category!==group.category"
					:key="group.category"
					icon="icon-files"
					:title="categoryToLabel(group.category)"
				/>
				<AppNavigationCaption v-if="group.timeslot"
					:key="group.timeslot"
					:title="group.timeslot"
				/>
				<NotesList
					:key="idx"
					:notes="group.notes"
					@note-deleted="$emit('note-deleted', $event)"
				/>
			</template>
			<!--<AppNavigationItem
				v-if="displayedNotes.length != filteredNotes.length"
				v-observe-visibility="onEndOfNotes"
				:title="t('notes', 'Loading …')"
				:loading="true"
			/> !-->
		</div>
	</div>
</template>

<script>

import { AppNavigationCaption } from '@nextcloud/vue'
import { categoryLabel } from '../Util'
import NotesList from './NotesList'
import store from '../store'

import { ObserveVisibility } from 'vue-observe-visibility'

export default {
	name: 'NotesView',

	components: {
		AppNavigationCaption,
		NotesList,
	},

	directives: {
		'observe-visibility': ObserveVisibility,
	},

	data() {
		return {
			timeslots: [],
			monthFormat: new Intl.DateTimeFormat(OC.getLanguage(), { month: 'long', year: 'numeric' }),
			lastYear: new Date(new Date().getFullYear() - 1, 0),
			showFirstNotesOnly: true,
		}
	},

	computed: {
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
	},
}
</script>
<style scoped>
	.notes-view {
		min-width: 300px;
		position: sticky;
		position: -webkit-sticky;
		height: calc(100vh - 50px);
		top: 50px;
		border-right: 1px solid var(--color-border);
	}

	.spacer{
		height: 50px;
	}

	.notes-list {
		overflow-y: auto;
		height: calc(100vh - 100px);
	}
</style>

<template>
	<Fragment>
		<!-- collapsible categories -->
		<NavigationCategoriesItem
			v-if="numNotes"
			:selected-category="category"
			@category-selected="$emit('category-selected', $event)"
		/>

		<!-- list of notes -->
		<template v-for="item in noteItems">
			<AppNavigationCaption v-if="category!==null && category!==item.category"
				:key="item.category"
				icon="icon-files"
				class="app-navigation-noclose"
				:title="categoryToLabel(item.category)"
				@click.native="$emit('category-selected', item.category)"
			/>
			<AppNavigationCaption v-if="category===null && item.timeslot"
				:key="item.timeslot"
				:title="item.timeslot"
			/>
			<NavigationNoteItem v-for="note in item.notes"
				:key="note.id"
				:note="note"
				@category-selected="$emit('category-selected', $event)"
				@note-deleted="$emit('note-deleted', $event)"
			/>
		</template>
		<AppNavigationItem
			v-if="notes.length != filteredNotes.length"
			v-observe-visibility="onEndOfNotes"
			:title="t('notes', 'Loading …')"
			:loading="true"
		/>
	</Fragment>
</template>

<script>
import {
	AppNavigationCaption,
	AppNavigationItem,
} from '@nextcloud/vue'
import { Fragment } from 'vue-fragment'

import { categoryLabel } from '../Util'
import NavigationCategoriesItem from './NavigationCategoriesItem'
import NavigationNoteItem from './NavigationNoteItem'
import store from '../store'

import { ObserveVisibility } from 'vue-observe-visibility'

export default {
	name: 'NavigationList',

	components: {
		AppNavigationCaption,
		AppNavigationItem,
		Fragment,
		NavigationCategoriesItem,
		NavigationNoteItem,
	},

	directives: {
		'observe-visibility': ObserveVisibility,
	},

	props: {
		filteredNotes: {
			type: Array,
			required: true,
		},
		category: {
			type: String,
			default: null,
		},
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
		numNotes() {
			return store.getters.numNotes()
		},

		notes() {
			if (this.filteredNotes.length > 40 && this.showFirstNotesOnly) {
				return this.filteredNotes.slice(0, 30)
			} else {
				return this.filteredNotes
			}
		},

		// group notes by time ("All notes") or by category (if category chosen)
		groupedNotes() {
			if (this.category === null) {
				return this.notes.reduce((g, note) => {
					const timeslot = this.getTimeslotFromNote(note)
					if (g.length === 0 || g[g.length - 1].timeslot !== timeslot) {
						g.push({ timeslot, notes: [] })
					}
					g[g.length - 1].notes.push(note)
					return g
				}, [])
			} else {
				return this.notes.reduce((g, note) => {
					if (g.length === 0 || g[g.length - 1].category !== note.category) {
						g.push({ category: note.category, notes: [] })
					}
					g[g.length - 1].notes.push(note)
					return g
				}, [])
			}
		},

		noteItems() {
			return this.groupedNotes
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
	},
}
</script>

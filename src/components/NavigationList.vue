<template>
	<ul>
		<!-- collapsible categories -->
		<NavigationCategoriesItem
			v-if="numNotes"
			:selected-category="category"
			@category-selected="$emit('category-selected', $event)"
		/>

		<!-- search result header -->
		<li v-if="search && filteredNotes.length" class="search-result-header">
			<a class="icon-search active">
				<span v-if="category">
					{{ t('notes', 'Search result for “{search}” in {category}', { search: search, category: category }) }}
				</span>
				<span v-else>
					{{ t('notes', 'Search result for “{search}”', { search: search }) }}
				</span>
			</a>
		</li>

		<!-- nothing found -->
		<li v-if="search && !filteredNotes.length">
			<span class="nav-entry">
				<div id="emptycontent" class="emptycontent-search">
					<div class="icon-search" />
					<h2 v-if="category">
						{{ t('notes', 'No search result for “{search}” in {category}', { search: search, category: category }) }}
					</h2>
					<h2 v-else>
						{{ t('notes', 'No search result for “{search}”', { search: search }) }}
					</h2>
				</div>
			</span>
		</li>

		<!-- list of notes -->
		<template v-for="item in noteItems">
			<AppNavigationItem v-if="category!==null && category!==item.category"
				:key="item.category" :item="categoryToItem(item.category)"
			/>
			<AppNavigationItem v-if="category===null && item.timeslot"
				:key="item.timeslot" :item="timeslotToItem(item.timeslot)"
				class="timeslot"
			/>
			<NavigationNoteItem v-for="note in item.notes"
				:key="note.id" :note="note"
				@category-selected="$emit('category-selected', $event)"
				@note-deleted="$emit('note-deleted')"
			/>
		</template>
	</ul>
</template>

<script>
import {
	AppNavigationItem,
} from 'nextcloud-vue'
import NavigationCategoriesItem from './NavigationCategoriesItem'
import NavigationNoteItem from './NavigationNoteItem'
import NotesService from '../NotesService'
import store from '../store'

export default {
	name: 'NavigationList',

	components: {
		AppNavigationItem,
		NavigationCategoriesItem,
		NavigationNoteItem,
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
		search: {
			type: String,
			default: '',
		},
	},

	data: function() {
		return {
			timeslots: [],
			monthFormat: new Intl.DateTimeFormat(OC.getLanguage(), { month: 'long', year: 'numeric' }),
			lastYear: new Date(new Date().getFullYear() - 1, 0),
		}
	},

	computed: {
		numNotes() {
			return store.getters.numNotes()
		},

		// group notes by time ("All notes") or by category (if category chosen)
		groupedNotes() {
			if (this.category === null) {
				return this.filteredNotes.reduce((g, note) => {
					const timeslot = this.getTimeslotFromNote(note)
					if (g.length === 0 || g[g.length - 1].timeslot !== timeslot) {
						g.push({ timeslot: timeslot, notes: [] })
					}
					g[g.length - 1].notes.push(note)
					return g
				}, [])
			} else {
				return this.filteredNotes.reduce((g, note) => {
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

		categoryToItem(category) {
			const label = '…/' + category.substring(this.category.length + 1)
			return {
				text: NotesService.categoryLabel(label),
				classes: 'app-navigation-caption caption-item app-navigation-noclose',
				icon: 'nav-icon-files',
				action: this.$emit.bind(this, 'category-selected', category),
			}
		},

		timeslotToItem(timeslot) {
			return {
				caption: true,
				text: timeslot,
			}
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
				return new Date(t).getFullYear()
			}
		},
	},
}
</script>
<style scoped>
.search-result-header > a,
.search-result-header > a * {
	font-style: italic;
	cursor: default;
}

li .nav-entry .emptycontent-search {
	white-space: normal;
}

@media (max-height: 600px) {
	li .nav-entry .emptycontent-search {
		margin-top: inherit;
	}
}

</style>

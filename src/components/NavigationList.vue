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

	computed: {
		numNotes() {
			return store.getters.numNotes()
		},

		groupedNotes() {
			return this.filteredNotes.reduce(function(g, note) {
				if (g.length === 0 || g[g.length - 1].category !== note.category) {
					g.push({ category: note.category, notes: [] })
				}
				g[g.length - 1].notes.push(note)
				return g
			}, [])
		},

		noteItems() {
			if (this.category == null) {
				return [ { notes: this.filteredNotes } ]
			} else {
				return this.groupedNotes
			}
		},
	},

	methods: {
		categoryToItem(category) {
			const label = '…/' + category.substring(this.category.length + 1)
			return {
				isLabel: true,
				text: NotesService.categoryLabel(label),
				classes: 'app-navigation-caption app-navigation-noclose',
				icon: 'nav-icon-files',
				action: this.$emit.bind(this, 'category-selected', category),
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

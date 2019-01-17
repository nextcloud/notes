<template>
	<div id="app-navigation">
		<app-navigation :menu="menu">
			<template slot="settings-content">Example settings</template>
		</app-navigation>
	</div>
</template>

<script>
import { AppNavigation } from 'nextcloud-vue'
import NotesService from './NotesService'
import store from './store'

export default {
	name: 'AppNotesNavigation',
	components: {
		AppNavigation
	},
	data: function() {
		return {
		}
	},
	computed: {
		notes() {
			return store.state.notes
		},
		menu() {
			var items = [];

			var notes = this.notes;
			var categories = store.getters.getCategories(1, true);
			var categoryItems = [];
			categoryItems.push({
				text: t('notes', 'All notes'),
				icon: 'nav-icon-recent',
				utils: {
					counter: notes.length,
				},
			});
			for(var i=0; i<categories.length; i++) {
				var category = categories[i];
				var item = {
					text: category.name=='' ? t('notes', 'Uncategorized') : category.name,
					icon: category.name=='' ? 'nav-icon-emptyfolder' : 'nav-icon-files',
					utils: {
						counter: category.count,
					},
				}
				categoryItems.push(item);
			}

			var categoryItem = {
				text: t('notes', 'Categories'), // TODO set to category, if chosen
				icon: 'nav-icon-files',
				collapsible: true,
				classes: 'app-navigation-noclose separator-below',
				children: categoryItems,
			};
			items.push(categoryItem);

			for(var i=0; i<notes.length; i++) {
				var item = { text: notes[i].title };
				items.push(item);
			}

			return {
				new: {
					id: 'new-note-button',
					text: t('notes', 'New note'),
					icon: 'icon-add',
					action: this.newNote,
				},
				items: items,
				loading: false
			}
		},
	},
	filters: {
	},
	methods: {
		newNote() {
			// TODO create new note
		},
	},
}
</script>

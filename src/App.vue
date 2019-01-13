<template>
	<div id="content" class="app-notes">
		<div id="app-navigation">
			<app-navigation :menu="menu">
				<template slot="settings-content">Example settings</template>
			</app-navigation>
		</div>
		<div id="app-content">
			Hier kommt der Inhalt hin ...
		</div>
	</div>
</template>

<script>
import { AppNavigation, Multiselect } from 'nextcloud-vue'
import axios from 'nextcloud-axios'

export default {
	name: 'App',
	components: {
		AppNavigation, Multiselect
	},
	data: function() {
		return {
			notes: [],
		}
	},
	computed: {
		// App navigation
		menu() {
			var items = [];
			for(var i=0; i<this.notes.length; i++) {
				var item = { text: this.notes[i].title }
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
		}
	},
	filters: {
	},
	created() {
		this.fetchNotes();
	},
	methods: {
		newNote() {
			// TODO create new note
		},
		url(url) {
			url = `/apps/notes${url}`
			return OC.generateUrl(url)
		},
		fetchNotes() {
			axios
				.get(this.url('/notes'))
				.then(response => {
					console.log(response.data);
					this.notes = response.data;
				})
				.catch(err => {
					console.log(err);
					// TODO error handling
				});
		},
	},
}
</script>

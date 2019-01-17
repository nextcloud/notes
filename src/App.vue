<template>
	<div id="content" class="app-notes">
		<app-notes-navigation></app-notes-navigation>
		<div id="app-content">
			Hier kommt der Inhalt hin ...
		</div>
	</div>
</template>

<script>
import AppNotesNavigation from './AppNotesNavigation'
import NotesService from './NotesService'

export default {
	name: 'App',
	components: {
		AppNotesNavigation
	},
	data: function() {
		return {
		}
	},
	computed: {
	},
	filters: {
	},
	created() {
		NotesService.fetchNotes();
	},
	methods: {

	},
}
</script>

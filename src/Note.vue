<template>
	<div>
		<div v-if="loading" class="loading">
			Loading
		</div>
		<div v-if="note">
			<pre>{{ note.content }}</pre>
		</div>
	</div>
</template>
<script>

import NotesService from './NotesService'
// import store from './store'

export default {
	name: 'Note',

	components: {
	},

	props: {
		noteId: { type: Number, default: 0 },
	},

	data: function() {
		return {
			loading: false,
			note: null,
		}
	},

	computed: {
	},

	watch: {
		// call again the method if the route changes
		'$route': 'fetchData'
	},

	created() {
		this.fetchData()
	},

	methods: {
		fetchData() {
			this.loading = true
			this.note = null
			NotesService.fetchNote(this.noteId)
				.then(response => {
					this.note = response.data
					this.loading = false
					console.debug(this.note) // TODO remove log
				})
				.catch(err => {
					console.error(err)
					// TODO error handling
				})
		},
	},
}
</script>
<style scoped>
</style>

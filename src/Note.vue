<template>
	<div class="note-wrapper" :class="{ loading: loading }">
		<div v-if="note" id="note-editor" class="note-editor">
			<b>{{ note.title }}</b>
			<p>{{ note.content }}</p>
			<span class="action-buttons">
				<button class="icon-fullscreen btn-fullscreen" @click="onToggleDistractionFree()" />
			</span>
		</div>
		<status-bar v-if="note" :note="note" class="note-status-bar" />
	</div>
</template>
<script>

import NotesService from './NotesService'
import StatusBar from './StatusBar'
// import store from './store'

export default {
	name: 'Note',

	components: {
		StatusBar,
	},

	props: {
		noteId: { type: String, default: '' },
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
		'$route': 'fetchData',
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
		onToggleDistractionFree() {
			function launchIntoFullscreen(element) {
				if (element.requestFullscreen) {
					element.requestFullscreen()
				} else if (element.mozRequestFullScreen) {
					element.mozRequestFullScreen()
				} else if (element.webkitRequestFullscreen) {
					element.webkitRequestFullscreen()
				} else if (element.msRequestFullscreen) {
					element.msRequestFullscreen()
				}
			}

			function exitFullscreen() {
				if (document.exitFullscreen) {
					document.exitFullscreen()
				} else if (document.mozCancelFullScreen) {
					document.mozCancelFullScreen()
				} else if (document.webkitExitFullscreen) {
					document.webkitExitFullscreen()
				}
			}

			if (document.fullscreenElement || document.mozFullScreenElement || document.webkitFullscreenElement) {
				exitFullscreen()
			} else {
				launchIntoFullscreen(document.getElementById('note-editor'))
			}
		},
	},
}
</script>
<style scoped>
.note-editor {
	background-color: var(--color-main-background);
}

.action-buttons {
	position: fixed;
	bottom: 4px;
	right: 20px;
	z-index: 2000;
}

.btn-fullscreen {
	padding: 15px;
}
</style>

<template>
	<AppContent :class="{ loading: loading, 'icon-error': !loading && (!note || note.error), 'sidebar-open': sidebarOpen }">
		<div v-if="!loading && note && !note.error && !note.deleting"
			id="note-container"
			class="note-container"
			:class="{ fullscreen: fullscreen }"
		>
			<div class="note-editor">
				<component :is="viewer.component"
					ref="texteditor"
					:fileid="fileId"
					:basename="title"
					:active="true"
					:has-preview="true"
					mime="text/markdown"
					class="text-editor"
					@ready="onEditorReady"
				/>
			</div>
			<span class="action-buttons">
				<Actions :open.sync="actionsOpen" menu-align="right">
					<ActionButton v-show="!sidebarOpen && !fullscreen"
						icon="icon-details"
						@click="onToggleSidebar"
					>
						{{ t('notes', 'Details') }}
					</ActionButton>
					<ActionButton
						icon="icon-fullscreen"
						:class="{ active: fullscreen }"
						@click="onToggleDistractionFree"
					>
						{{ fullscreen ? t('notes', 'Exit full screen') : t('notes', 'Full screen') }}
					</ActionButton>
				</Actions>
			</span>
		</div>
	</AppContent>
</template>
<script>

import {
	Actions,
	ActionButton,
	AppContent,
	Tooltip,
	isMobile,
} from '@nextcloud/vue'
import { emit } from '@nextcloud/event-bus'

import { config } from '../config'
import { autotitleNote, routeIsNewNote } from '../NotesService'
import store from '../store'

export default {
	name: 'Note',

	components: {
		Actions,
		ActionButton,
		AppContent,
	},

	directives: {
		tooltip: Tooltip,
	},

	mixins: [isMobile],

	props: {
		noteId: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			loading: false,
			fullscreen: false,
			actionsOpen: false,
			autotitleTimer: null,
			etag: null,
		}
	},

	computed: {
		note() {
			return store.getters.getNote(parseInt(this.noteId))
		},
		title() {
			return this.note ? this.note.title : ''
		},
		isNewNote() {
			return routeIsNewNote(this.$route)
		},
		sidebarOpen() {
			return store.state.app.sidebarOpen
		},
		fileId() {
			return parseInt(this.noteId)
		},
		viewer() {
			return OCA.Viewer.availableHandlers.filter(h => h.id === 'text')[0]
		},
	},

	watch: {
		$route(to, from) {
			if (to.name !== from.name || to.params.noteId !== from.params.noteId) {
				// this.loading = true
				this.initNote()
				this.$refs.texteditor.$children[0].reconnect()
			}
		},
		title: 'onUpdateTitle',
	},

	created() {
		this.initNote()
		document.addEventListener('webkitfullscreenchange', this.onDetectFullscreen)
		document.addEventListener('mozfullscreenchange', this.onDetectFullscreen)
		document.addEventListener('fullscreenchange', this.onDetectFullscreen)
		document.addEventListener('keydown', this.onKeyPress)
	},

	destroyed() {
		document.removeEventListener('webkitfullscreenchange', this.onDetectFullscreen)
		document.removeEventListener('mozfullscreenchange', this.onDetectFullscreen)
		document.removeEventListener('fullscreenchange', this.onDetectFullscreen)
		document.removeEventListener('keydown', this.onKeyPress)
		store.commit('setSidebarOpen', false)
		this.onUpdateTitle(null)
	},

	methods: {
		initNote() {
			store.commit('setSidebarOpen', false)

			if (this.isMobile) {
				emit('toggle-navigation', { open: false })
			}

			this.onUpdateTitle(this.title)
		},

		onUpdateTitle(title) {
			const defaultTitle = store.state.app.documentTitle
			if (title) {
				document.title = title + ' - ' + defaultTitle
			} else {
				document.title = defaultTitle
			}
		},

		onDetectFullscreen() {
			this.fullscreen = document.fullScreen || document.mozFullScreen || document.webkitIsFullScreen
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

			if (this.fullscreen) {
				exitFullscreen()
			} else {
				launchIntoFullscreen(document.getElementById('note-container'))
			}
			this.actionsOpen = false
		},

		onToggleSidebar() {
			store.commit('setSidebarOpen', !store.state.app.sidebarOpen)
			this.actionsOpen = false
		},

		onEditorReady() {
			console.debug('onEditorReady')
			this.loading = false
		},

		onEdit(newContent) {
			if (this.note.content !== newContent) {
				const note = {
					...this.note,
					content: newContent,
					unsaved: true,
				}
				store.commit('updateNote', note)
				this.$forceUpdate()

				// stop old autotitle timer
				if (this.autotitleTimer !== null) {
					clearTimeout(this.autotitleTimer)
					this.autotitleTimer = null
				}
				// start autotitle timer if note is new
				if (this.isNewNote) {
					this.autotitleTimer = setTimeout(() => {
						this.autotitleTimer = null
						if (this.isNewNote) {
							autotitleNote(note.id)
						}
					}, config.interval.note.autotitle * 1000)
				}
			}
		},

		onKeyPress(event) {
		},
	},
}
</script>
<style scoped>
.note-container {
	min-height: 100%;
	width: 100%;
	background-color: var(--color-main-background);
}

.note-editor {
	max-width: 47em;
	font-size: 16px;
	padding: 1em;
	padding-bottom: 0;
}

.text-editor {
	position: absolute !important;
	top: 0 !important;
}

/* center editor on large screens */
@media (min-width: 1600px) {
	.note-editor {
		margin: 0 auto;
	}
	.note-container {
		padding-right: 250px;
		transition-duration: var(--animation-quick);
		transition-property: padding-right;
	}
	.sidebar-open .note-container {
		padding-right: 0px;
	}
}

/* distraction free styles */
.note-container.fullscreen {
	width: 100vw;
	height: 100vh;
	overflow-y: auto;
	padding: 0;
}

.note-container.fullscreen .note-editor {
	margin: 0 auto;
}

/* placeholder */
.placeholder {
	position: absolute;
	padding: 1em;
	opacity: 0.5;
}

/* main editor button */
.action-buttons {
	position: fixed;
	top: 50px;
	right: 20px;
	width: 44px;
	margin-top: 1em;
	z-index: 2000;
}

.action-buttons .action-error {
	width: 44px;
	height: 44px;
}

.note-container.fullscreen .action-buttons {
	top: 0px;
}

.action-buttons button {
	padding: 15px;
}
</style>

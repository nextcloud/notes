<template>
	<NcAppContent :class="{ loading: loading || isManualSave, 'icon-error': !loading && (!note || note.error), 'sidebar-open': sidebarOpen }">
		<div v-if="!loading && note && !note.error && !note.deleting"
			id="note-container"
			class="note-container"
			:class="{ fullscreen: fullscreen }"
		>
			<NcModal v-if="note.conflict && showConflict" size="full" @close="showConflict=false">
				<div class="conflict-modal">
					<div class="conflict-header">
						<SyncAlertIcon slot="icon" :size="30" fill-color="var(--color-error)" />
						{{ t('notes', 'The note has been changed in another session. Please choose which version should be saved.') }}
					</div>
					<div class="conflict-solutions">
						<ConflictSolution
							:content="note.conflict.content"
							:reference="note.reference.content"
							:button="t('notes', 'Use version from server')"
							@on-choose-solution="onUseRemoteVersion"
						/>
						<ConflictSolution
							:content="note.content"
							:reference="note.reference.content"
							:button="t('notes', 'Use current version')"
							@on-choose-solution="onUseLocalVersion"
						/>
					</div>
				</div>
			</NcModal>
			<div class="note-editor">
				<div v-show="!note.content" class="placeholder">
					{{ preview ? t('notes', 'Empty note') : t('notes', 'Write …') }}
				</div>
				<ThePreview v-if="preview"
					:value="note.content"
					:noteid="noteId"
					:readonly="note.readonly"
					@input="onEdit"
				/>
				<TheEditor v-else
					:value="note.content"
					:noteid="noteId"
					:notecategory="note.category"
					:readonly="note.readonly"
					@input="onEdit"
				/>
			</div>
			<span class="action-buttons">
				<NcActions :open.sync="actionsOpen" container=".action-buttons" menu-align="right">
					<NcActionButton v-show="!sidebarOpen && !fullscreen"
						icon="icon-details"
						@click="onToggleSidebar"
					>
						<SidebarIcon slot="icon" :size="20" />
						{{ t('notes', 'Details') }}
					</NcActionButton>
					<NcActionButton
						v-tooltip.left="t('notes', 'CTRL + /')"
						@click="onTogglePreview"
					>
						<EditIcon v-if="preview" slot="icon" :size="20" />
						<EyeIcon v-else slot="icon" :size="20" />
						{{ preview ? t('notes', 'Edit') : t('notes', 'Preview') }}
					</NcActionButton>
					<NcActionButton
						:class="{ active: fullscreen }"
						@click="onToggleDistractionFree"
					>
						<FullscreenIcon slot="icon" :size="20" />
						{{ fullscreen ? t('notes', 'Exit full screen') : t('notes', 'Full screen') }}
					</NcActionButton>
				</NcActions>
				<NcActions v-if="note.readonly">
					<NcActionButton>
						<NoEditIcon slot="icon" :size="20" />
						{{ t('notes', 'Note is read-only. You cannot change it.') }}
					</NcActionButton>
				</NcActions>
				<NcActions v-if="note.saveError" class="action-error">
					<NcActionButton @click="onManualSave">
						<SyncAlertIcon slot="icon" :size="20" fill-color="var(--color-text)" />
						{{ t('notes', 'Save failed. Click to retry.') }}
					</NcActionButton>
				</NcActions>
				<NcActions v-if="note.conflict" class="action-error">
					<NcActionButton @click="showConflict=true">
						<SyncAlertIcon slot="icon" :size="20" fill-color="var(--color-text)" />
						{{ t('notes', 'Update conflict. Click for resolving manually.') }}
					</NcActionButton>
				</NcActions>
			</span>
		</div>
	</NcAppContent>
</template>
<script>

import {
	NcActions,
	NcActionButton,
	NcAppContent,
	NcModal,
	Tooltip,
	isMobile,
} from '@nextcloud/vue'
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'

import EditIcon from 'vue-material-design-icons/LeadPencil.vue'
import EyeIcon from 'vue-material-design-icons/Eye.vue'
import FullscreenIcon from 'vue-material-design-icons/Fullscreen.vue'
import NoEditIcon from 'vue-material-design-icons/PencilOff.vue'
import SidebarIcon from 'vue-material-design-icons/PageLayoutSidebarRight.vue'
import SyncAlertIcon from 'vue-material-design-icons/SyncAlert.vue'

import { config } from '../config.js'
import { fetchNote, refreshNote, saveNoteManually, queueCommand, conflictSolutionLocal, conflictSolutionRemote } from '../NotesService.js'
import { routeIsNewNote } from '../Util.js'
import TheEditor from './EditorEasyMDE.vue'
import ThePreview from './EditorMarkdownIt.vue'
import ConflictSolution from './ConflictSolution.vue'
import store from '../store.js'

export default {
	name: 'NotePlain',

	components: {
		ConflictSolution,
		EditIcon,
		EyeIcon,
		FullscreenIcon,
		NcActions,
		NcActionButton,
		NcAppContent,
		NcModal,
		NoEditIcon,
		SidebarIcon,
		SyncAlertIcon,
		TheEditor,
		ThePreview,
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
			preview: false,
			actionsOpen: false,
			autosaveTimer: null,
			autotitleTimer: null,
			refreshTimer: null,
			etag: null,
			showConflict: false,
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
		isManualSave() {
			return store.state.app.isManualSave
		},
		sidebarOpen() {
			return store.state.app.sidebarOpen
		},
	},

	watch: {
		$route(to, from) {
			if (to.name !== from.name || to.params.noteId !== from.params.noteId) {
				this.fetchData()
			}
		},
		title: 'onUpdateTitle',
		'note.conflict'(newConflict, oldConflict) {
			if (newConflict) {
				this.showConflict = true
			}
		},
	},

	created() {
		this.fetchData()
		document.addEventListener('webkitfullscreenchange', this.onDetectFullscreen)
		document.addEventListener('mozfullscreenchange', this.onDetectFullscreen)
		document.addEventListener('fullscreenchange', this.onDetectFullscreen)
		document.addEventListener('keydown', this.onKeyPress)
		document.addEventListener('visibilitychange', this.onVisibilityChange)
	},

	destroyed() {
		this.stopRefreshTimer()
		document.removeEventListener('webkitfullscreenchange', this.onDetectFullscreen)
		document.removeEventListener('mozfullscreenchange', this.onDetectFullscreen)
		document.removeEventListener('fullscreenchange', this.onDetectFullscreen)
		document.removeEventListener('keydown', this.onKeyPress)
		document.removeEventListener('visibilitychange', this.onVisibilityChange)
		this.onUpdateTitle(null)
	},

	methods: {
		fetchData() {
			this.etag = null
			this.stopRefreshTimer()

			if (this.isMobile) {
				emit('toggle-navigation', { open: false })
			}

			this.onUpdateTitle(this.title)
			this.loading = true
			this.preview = store.state.app.settings.noteMode === 'preview' && !this.isNewNote
			fetchNote(parseInt(this.noteId))
				.then((note) => {
					if (note.error) {
						showError(t('notes', 'Error from Nextcloud server: {msg}', { msg: note.errorType }))
					}
					this.startRefreshTimer()
				})
				.catch(() => {
					// note not found
				})
				.then(() => {
					this.loading = false
				})
		},

		onUpdateTitle(title) {
			const defaultTitle = store.state.app.documentTitle
			if (title) {
				document.title = title + ' - ' + defaultTitle
			} else {
				document.title = defaultTitle
			}
		},

		onTogglePreview() {
			this.preview = !this.preview
			this.actionsOpen = false
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

		onVisibilityChange() {
			if (document.visibilityState === 'visible') {
				this.stopRefreshTimer()
				this.refreshNote()
			}
		},

		stopRefreshTimer() {
			if (this.refreshTimer !== null) {
				clearTimeout(this.refreshTimer)
				this.refreshTimer = null
			}
		},

		startRefreshTimer() {
			this.stopRefreshTimer()
			const interval = document.visibilityState === 'visible' ? config.interval.note.refresh : config.interval.note.refreshHidden
			this.refreshTimer = setTimeout(() => {
				this.refreshTimer = null
				this.refreshNote()
			}, interval * 1000)
		},

		refreshNote() {
			if (this.note.unsaved && !this.note.conflict) {
				this.startRefreshTimer()
				return
			}
			refreshNote(parseInt(this.noteId), this.etag).then(etag => {
				if (etag) {
					this.etag = etag
					this.$forceUpdate()
				}
				this.startRefreshTimer()
			})
		},

		onEdit(newContent) {
			if (this.note.content !== newContent) {
				this.stopRefreshTimer()
				const note = {
					...this.note,
					content: newContent,
					unsaved: true,
				}
				store.commit('updateNote', note)
				this.$forceUpdate()

				// queue auto saving note content
				if (this.autosaveTimer === null) {
					this.autosaveTimer = setTimeout(() => {
						this.autosaveTimer = null
						queueCommand(note.id, 'content')
					}, config.interval.note.autosave * 1000)
				}

				// (re-) start auto refresh timer
				// TODO should be after save is finished
				this.startRefreshTimer()

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
							queueCommand(note.id, 'autotitle')
						}
					}, config.interval.note.autotitle * 1000)
				}
			}
		},

		onKeyPress(event) {
			if (event.ctrlKey || event.metaKey) {
				switch (event.key.toLowerCase()) {
				case 's':
					event.preventDefault()
					this.onManualSave()
					break
				case '/':
					event.preventDefault()
					this.onTogglePreview()
					break
				}
			}
		},

		onManualSave() {
			const note = {
				...this.note,
			}
			store.commit('updateNote', note)
			saveNoteManually(this.note.id)
		},

		onUseLocalVersion() {
			console.debug('conflict solution: use local version')
			conflictSolutionLocal(this.note)
			this.showConflict = false
		},

		onUseRemoteVersion() {
			console.debug('conflict solution: use remote version')
			conflictSolutionRemote(this.note)
			this.showConflict = false
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
	background-color: var(--color-error);
	margin-top: 1ex;
}

.note-container.fullscreen .action-buttons {
	top: 0px;
}

/* Conflict Modal */
.conflict-modal {
	width: 70vw;
	margin: auto;
}

.conflict-header {
	padding: 1ex 1em;
}

.conflict-solutions {
	display: flex;
	flex-direction: row-reverse;
	max-height: 75vh;
	overflow-y: auto;
}

@media (max-width: 60em) {
	.conflict-solutions {
		flex-direction: column;
	}
}

</style>

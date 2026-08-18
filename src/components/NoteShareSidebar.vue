<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppSidebar
		v-model:active="activeTab"
		data-cy-notes-share-sidebar
		forceMenu
		:loading="isOpen && loading"
		:name="note?.title || t('notes', 'Note')"
		noToggle
		:open="isOpen"
		@closed="onClosed"
		@update:open="onToggle"
	>
		<template v-if="subnameSize || subnameTimestamp" #subname>
			<span v-if="subnameSize">{{ subnameSize }}</span>
			<span v-if="subnameSize && subnameTimestamp"> · </span>
			<NcDateTime v-if="subnameTimestamp" :timestamp="subnameTimestamp" />
		</template>

		<NcAppSidebarTab v-if="note"
			id="notes-info"
			:name="t('notes', 'Details')"
			:order="0"
		>
			<template #icon>
				<InformationIcon v-if="resolvedTab === 'notes-info'" :size="20" />
				<InformationOutlineIcon v-else :size="20" />
			</template>
			<NoteInfo
				:note="note"
				:contentLoading="loadingContent"
				:contentError="contentError"
			/>
		</NcAppSidebarTab>

		<NcAppSidebarTab
			v-if="sharingTab"
			:id="sharingTab.id"
			:name="sharingTab.displayName"
			:order="sharingTab.order"
		>
			<template #icon>
				<ShareVariantIcon v-if="resolvedTab === sharingTab.id" :size="20" />
				<ShareVariantOutlineIcon v-else :size="20" />
			</template>

			<NcEmptyContent v-if="loading">
				<template #icon>
					<NcLoadingIcon />
				</template>
			</NcEmptyContent>

			<NcEmptyContent v-else-if="!currentNode || error">
				<template #icon>
					<ShareVariantOutlineIcon :size="44" />
				</template>
				{{ error || t('notes', 'Unable to load the selected note.') }}
			</NcEmptyContent>

			<component
				:is="sharingTab.tagName"
				v-else
				:active.prop="resolvedTab === sharingTab.id"
				:folder.prop="currentFolder"
				:node.prop="currentNode"
				:view.prop="currentView"
			/>
		</NcAppSidebarTab>

		<NcEmptyContent v-else-if="isOpen && !note">
			<template #icon>
				<ShareVariantOutlineIcon :size="44" />
			</template>
			{{ t('notes', 'Sharing is not available right now.') }}
		</NcEmptyContent>
	</NcAppSidebar>
</template>

<script>
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { formatFileSize, getSidebarTabs } from '@nextcloud/files'
import NcAppSidebar from '@nextcloud/vue/components/NcAppSidebar'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import InformationIcon from 'vue-material-design-icons/Information.vue'
import InformationOutlineIcon from 'vue-material-design-icons/InformationOutline.vue'
import ShareVariantIcon from 'vue-material-design-icons/ShareVariant.vue'
import ShareVariantOutlineIcon from 'vue-material-design-icons/ShareVariantOutline.vue'
import NoteInfo from './NoteInfo.vue'
import logger from '../Logger.js'
import { fetchNote } from '../NotesService.js'
import store from '../store.js'
import { fetchDavNode } from '../WebdavService.js'

export default {
	name: 'NoteShareSidebar',

	components: {
		NcAppSidebar,
		NcAppSidebarTab,
		NcDateTime,
		NcEmptyContent,
		NcLoadingIcon,
		InformationIcon,
		InformationOutlineIcon,
		NoteInfo,
		ShareVariantIcon,
		ShareVariantOutlineIcon,
	},

	data() {
		return {
			activeTab: 'sharing',
			contentError: false,
			contextError: '',
			contextRequestToken: 0,
			currentFolder: null,
			currentNode: null,
			initializingTabs: new Set(),
			initializedTabs: new Set(),
			isOpen: false,
			loadingContentFor: null,
			loadingContext: false,
			loadingTab: false,
			noteId: null,
			tabError: '',
		}
	},

	computed: {
		error() {
			return this.tabError || this.contextError
		},

		loading() {
			return this.loadingContext || this.loadingTab
		},

		loadingContent() {
			return this.loadingContentFor !== null
		},

		note() {
			if (!Number.isFinite(this.noteId)) {
				return null
			}
			return store.notes.getNote(this.noteId)
		},

		sharingTab() {
			return getSidebarTabs().find((tab) => tab.id === 'sharing') || null
		},

		subnameSize() {
			const size = this.currentNode?.size
			return size === undefined || size === null ? '' : formatFileSize(size)
		},

		subnameTimestamp() {
			// the API reports seconds, NcDateTime expects milliseconds
			return this.note?.modified ? this.note.modified * 1000 : null
		},

		/** Ids of the tabs the sidebar renders, in the order they appear */
		availableTabIds() {
			return [
				...(this.note ? ['notes-info'] : []),
				...(this.sharingTab ? [this.sharingTab.id] : []),
			]
		},

		/**
		 * NcAppSidebar falls back to its first tab when the active one is not
		 * among them, but does not report that back, so the tab id has to be
		 * clamped here as well for `active` to reach the right custom element.
		 */
		resolvedTab() {
			const ids = this.availableTabIds
			return ids.includes(this.activeTab) ? this.activeTab : (ids[0] ?? this.activeTab)
		},

		routeNoteId() {
			const noteId = Number(this.$route?.params?.noteId)
			return Number.isFinite(noteId) ? noteId : null
		},

		currentView() {
			return {
				id: 'notes',
				name: this.t('notes', 'Notes'),
			}
		},
	},

	watch: {
		resolvedTab: 'ensureContent',

		/**
		 * The sidebar is opened for one note but the list keeps navigating, so an
		 * open sidebar follows whichever note is being looked at, the way the
		 * Files sidebar does. The tab in view is kept.
		 *
		 * @param {number|null} noteId the note the route moved to
		 */
		routeNoteId(noteId) {
			if (this.isOpen && noteId !== null && noteId !== this.noteId) {
				this.onSidebarOpen({ noteId, tab: this.activeTab })
			}
		},
	},

	mounted() {
		// the share event is kept so anything already emitting it keeps working
		subscribe('notes:share:open', this.onShareOpen)
		subscribe('notes:sidebar:open', this.onSidebarOpen)
	},

	unmounted() {
		unsubscribe('notes:share:open', this.onShareOpen)
		unsubscribe('notes:sidebar:open', this.onSidebarOpen)
	},

	methods: {
		/**
		 * The note list payload excludes content, so a note that has never been
		 * opened has none. The Details tab needs it for the reading estimate —
		 * fetch it, but only once that tab is actually being looked at, so
		 * opening the sidebar to share a note does not pull its whole body down.
		 */
		async ensureContent() {
			if (this.resolvedTab !== 'notes-info') {
				return
			}
			const noteId = this.noteId
			if (!Number.isFinite(noteId) || typeof this.note?.content === 'string') {
				return
			}
			if (this.loadingContentFor === noteId) {
				return
			}

			this.loadingContentFor = noteId
			this.contentError = false
			try {
				// fetchNote() only rejects on a missing note and reports anything
				// else itself, so the content is what says whether it worked
				const note = await fetchNote(noteId)
				if (this.loadingContentFor !== noteId) {
					return
				}
				this.contentError = typeof note?.content !== 'string'
			} catch (error) {
				if (this.loadingContentFor !== noteId) {
					return
				}
				logger.error('Failed to load the note body for the Details tab', { error })
				this.contentError = true
			} finally {
				if (this.loadingContentFor === noteId) {
					this.loadingContentFor = null
				}
			}
		},

		async initializeSharingTab() {
			const tab = this.sharingTab
			if (!tab) {
				this.loadingTab = false
				this.tabError = this.t('notes', 'Sharing is not available right now.')
				return
			}

			if (window.customElements.get(tab.tagName) || this.initializedTabs.has(tab.tagName)) {
				this.loadingTab = false
				this.tabError = ''
				return
			}

			if (this.initializingTabs.has(tab.tagName)) {
				this.loadingTab = true
				return
			}

			this.initializingTabs.add(tab.tagName)
			this.loadingTab = true
			this.tabError = ''

			try {
				await tab.onInit?.()
				await window.customElements.whenDefined(tab.tagName)
				this.initializedTabs.add(tab.tagName)
			} catch (error) {
				logger.error('Failed to initialize the sharing sidebar tab in Notes', { error })
				this.tabError = this.t('notes', 'Failed to load the sharing sidebar.')
			} finally {
				this.initializingTabs.delete(tab.tagName)
				this.loadingTab = false
			}
		},

		async loadNodeContext() {
			const internalPath = this.note?.internalPath
			if (!internalPath) {
				this.loadingContext = false
				this.currentNode = null
				this.currentFolder = null
				this.contextError = this.t('notes', 'Unable to load the selected note.')
				return
			}

			const requestToken = ++this.contextRequestToken
			this.loadingContext = true
			this.contextError = ''

			try {
				const node = await fetchDavNode(internalPath)
				let folder = null

				try {
					folder = await fetchDavNode(node.dirname || '/')
				} catch (error) {
					logger.error('Failed to load the parent folder for the Notes sidebar', { error })
				}

				if (requestToken !== this.contextRequestToken) {
					return
				}

				this.currentNode = node
				this.currentFolder = folder
			} catch (error) {
				if (requestToken !== this.contextRequestToken) {
					return
				}

				logger.error('Failed to load the selected note for the Notes sidebar', { error })
				this.currentNode = null
				this.currentFolder = null
				this.contextError = this.t('notes', 'Unable to load the selected note.')
			} finally {
				if (requestToken === this.contextRequestToken) {
					this.loadingContext = false
				}
			}
		},

		onShareOpen({ noteId }) {
			return this.onSidebarOpen({ noteId, tab: 'sharing' })
		},

		async onSidebarOpen({ noteId, tab = 'sharing' }) {
			this.contextRequestToken += 1
			this.noteId = Number(noteId)
			this.activeTab = tab
			this.isOpen = true
			this.contentError = false
			this.contextError = ''
			this.tabError = ''
			this.currentNode = null
			this.currentFolder = null
			this.loadingContentFor = null
			this.loadingContext = false
			this.loadingTab = false

			await Promise.all([
				this.initializeSharingTab(),
				this.loadNodeContext(),
				this.ensureContent(),
			])
		},

		onToggle(open) {
			if (!open) {
				this.isOpen = false
			}
		},

		onClosed() {
			if (this.isOpen) {
				return
			}

			this.contextRequestToken += 1
			this.noteId = null
			this.contentError = false
			this.contextError = ''
			this.currentNode = null
			this.currentFolder = null
			this.loadingContentFor = null
			this.loadingContext = false
			this.loadingTab = false
			this.tabError = ''
		},
	},
}
</script>

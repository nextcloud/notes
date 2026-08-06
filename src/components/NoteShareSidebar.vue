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
		<NcAppSidebarTab v-for="tab in tabs"
			:id="tab.id"
			:key="tab.id"
			:name="tab.displayName"
			:order="tab.order"
		>
			<template #icon>
				<NcIconSvgWrapper :svg="tab.iconSvgInline" />
			</template>

			<NcEmptyContent v-if="loading">
				<template #icon>
					<NcLoadingIcon />
				</template>
			</NcEmptyContent>

			<NcEmptyContent v-else-if="!currentNode || contextError">
				<template #icon>
					<FileOutlineIcon :size="44" />
				</template>
				{{ contextError || t('notes', 'Unable to load the selected note.') }}
			</NcEmptyContent>

			<component
				:is="tab.tagName"
				v-else
				:active.prop="activeTab === tab.id"
				:folder.prop="currentFolder"
				:node.prop="currentNode"
				:view.prop="currentView"
			/>
		</NcAppSidebarTab>

		<NcEmptyContent v-if="isOpen && tabs.length === 0">
			<template #icon>
				<FileOutlineIcon :size="44" />
			</template>
			{{ tabError || t('notes', 'Sharing and versions are not available right now.') }}
		</NcEmptyContent>
	</NcAppSidebar>
</template>

<script>
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { getSidebarTabs } from '@nextcloud/files'
import NcAppSidebar from '@nextcloud/vue/components/NcAppSidebar'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import FileOutlineIcon from 'vue-material-design-icons/FileOutline.vue'
import logger from '../Logger.js'
import { selectNoteSidebarTabs } from '../sidebarTabs.js'
import store from '../store.js'
import { fetchDavNode } from '../WebdavService.js'

// customElements.whenDefined() never settles for an element that is never
// defined, so a tab whose onInit() does not deliver one must not be waited for
// forever
const TAB_DEFINITION_TIMEOUT = 10000

export default {
	name: 'NoteShareSidebar',

	components: {
		NcAppSidebar,
		NcAppSidebarTab,
		NcEmptyContent,
		NcIconSvgWrapper,
		NcLoadingIcon,
		FileOutlineIcon,
	},

	data() {
		return {
			activeTab: 'sharing',
			contextError: '',
			contextRequestToken: 0,
			currentFolder: null,
			currentNode: null,
			pendingTabs: new Map(),
			initializedTabs: new Set(),
			failedTabs: new Set(),
			isOpen: false,
			loadingContext: false,
			loadingTab: false,
			noteId: null,
			tabError: '',
		}
	},

	computed: {
		loading() {
			return this.loadingContext || this.loadingTab
		},

		note() {
			if (!Number.isFinite(this.noteId)) {
				return null
			}
			return store.notes.getNote(this.noteId)
		},

		availableTabs() {
			return selectNoteSidebarTabs(getSidebarTabs(), {
				node: this.currentNode,
				folder: this.currentFolder,
				view: this.currentView,
			})
		},

		tabs() {
			return this.availableTabs.filter((tab) => !this.failedTabs.has(tab.tagName))
		},

		currentView() {
			return {
				id: 'notes',
				name: this.t('notes', 'Notes'),
			}
		},
	},

	watch: {
		// the versions tab drops out once the node says it is not applicable,
		// so what was requested is not necessarily still renderable
		tabs(tabs) {
			this.activeTab = this.resolveTab(this.activeTab, tabs)
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
		async initializeTabs() {
			const tabs = this.availableTabs
			if (tabs.length === 0) {
				this.loadingTab = false
				return
			}

			const requestToken = this.contextRequestToken

			// One tab failing to define its element must not hide the others, so
			// they are initialised independently and only a total failure is
			// reported as an error.
			const results = await Promise.all(tabs.map((tab) => this.initializeTab(tab)))

			if (requestToken !== this.contextRequestToken) {
				return
			}

			this.loadingTab = false
			this.tabError = results.includes(true)
				? ''
				: this.t('notes', 'Failed to load the note sidebar.')
		},

		/**
		 * @param {object} tab a registered Files sidebar tab
		 * @return {Promise<boolean>} whether the tab is usable
		 */
		async initializeTab(tab) {
			if (window.customElements.get(tab.tagName) || this.initializedTabs.has(tab.tagName)) {
				return true
			}

			this.loadingTab = true

			// an open while another one is still initializing the same element
			// has to await that initialization, not assume it succeeded
			const pending = this.pendingTabs.get(tab.tagName)
			if (pending) {
				return pending
			}

			const initialization = this.defineTabElement(tab)
			this.pendingTabs.set(tab.tagName, initialization)

			try {
				return await initialization
			} finally {
				this.pendingTabs.delete(tab.tagName)
			}
		},

		/**
		 * @param {object} tab a registered Files sidebar tab
		 * @return {Promise<boolean>} whether its custom element got defined
		 */
		async defineTabElement(tab) {
			let timeout
			try {
				await Promise.race([
					(async () => {
						await tab.onInit?.()
						await window.customElements.whenDefined(tab.tagName)
					})(),
					new Promise((resolve, reject) => {
						timeout = setTimeout(
							() => reject(new Error(`${tab.tagName} was not defined in time`)),
							TAB_DEFINITION_TIMEOUT,
						)
					}),
				])
				this.initializedTabs.add(tab.tagName)
				return true
			} catch (error) {
				logger.error('Failed to initialize a sidebar tab in Notes', { error, tab: tab.id })
				this.failedTabs.add(tab.tagName)
				return false
			} finally {
				clearTimeout(timeout)
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

			const requestToken = this.contextRequestToken
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

		/**
		 * NcAppSidebar falls back to its first tab when the active one is not
		 * among them, but does not report that back, so the tab id here has to
		 * be clamped as well for `active` to reach the right custom element.
		 *
		 * @param {string} tab the requested tab id
		 * @param {Array<object>} tabs the tabs currently rendered
		 * @return {string} the requested tab if renderable, the first one otherwise
		 */
		resolveTab(tab, tabs) {
			if (tabs.length === 0 || tabs.some(({ id }) => id === tab)) {
				return tab
			}
			return tabs[0].id
		},

		onShareOpen({ noteId }) {
			return this.onSidebarOpen({ noteId, tab: 'sharing' })
		},

		async onSidebarOpen({ noteId, tab = 'sharing' }) {
			this.contextRequestToken += 1
			this.noteId = Number(noteId)
			this.isOpen = true
			this.contextError = ''
			this.tabError = ''
			this.currentNode = null
			this.currentFolder = null
			this.loadingContext = false
			this.loadingTab = false
			this.failedTabs.clear()
			this.activeTab = this.resolveTab(tab, this.tabs)

			if (this.availableTabs.length === 0) {
				await this.initializeTabs()
				return
			}

			await Promise.all([
				this.initializeTabs(),
				this.loadNodeContext(),
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
			this.contextError = ''
			this.currentNode = null
			this.currentFolder = null
			this.loadingContext = false
			this.loadingTab = false
			this.tabError = ''
		},
	},
}
</script>

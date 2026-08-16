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
		<template v-if="currentNode" #subname>
			<NoteSidebarSubname :node="currentNode" />
		</template>

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
				:active.prop="resolvedTab === tab.id"
				:folder.prop="currentFolder"
				:node.prop="currentNode"
				:view.prop="currentView"
			/>
		</NcAppSidebarTab>

		<NcEmptyContent v-if="isOpen && tabs.length === 0">
			<template #icon>
				<FileOutlineIcon :size="44" />
			</template>
			{{ t('notes', 'Sharing and versions are not available right now.') }}
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
import NoteSidebarSubname from './NoteSidebarSubname.vue'
import logger from '../Logger.js'
import { selectNoteSidebarTabs } from '../sidebarTabs.js'
import store from '../store.js'
import { fetchDavNode } from '../WebdavService.js'

const TAB_DEFINITION_TIMEOUT = 10000

const pendingTabs = new Map()

/**
 * @param {object} tab a registered Files sidebar tab
 * @return {Promise<boolean>} whether its custom element got defined
 */
async function defineTabElement(tab) {
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
		return true
	} catch (error) {
		logger.error('Failed to initialize a sidebar tab in Notes', { error, tab: tab.id })
		return false
	} finally {
		clearTimeout(timeout)
	}
}

/**
 * @param {object} tab a registered Files sidebar tab
 * @return {Promise<boolean>} whether the tab is usable
 */
function initializeTab(tab) {
	if (window.customElements.get(tab.tagName)) {
		return Promise.resolve(true)
	}

	if (!pendingTabs.has(tab.tagName)) {
		pendingTabs.set(
			tab.tagName,
			defineTabElement(tab).finally(() => pendingTabs.delete(tab.tagName)),
		)
	}

	return pendingTabs.get(tab.tagName)
}

export default {
	name: 'NoteShareSidebar',

	components: {
		NcAppSidebar,
		NcAppSidebarTab,
		NcEmptyContent,
		NcIconSvgWrapper,
		NcLoadingIcon,
		FileOutlineIcon,
		NoteSidebarSubname,
	},

	data() {
		return {
			activeTab: 'sharing',
			contextError: '',
			contextRequestToken: 0,
			currentFolder: null,
			currentNode: null,
			failedTabs: new Set(),
			isOpen: false,
			loadingContext: false,
			loadingTab: false,
			noteId: null,
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

		/**
		 * NcAppSidebar falls back to its first tab when the active one is not
		 * among them, but does not report that back, so the tab id has to be
		 * clamped here as well for `active` to reach the right custom element.
		 */
		resolvedTab() {
			if (this.tabs.some(({ id }) => id === this.activeTab)) {
				return this.activeTab
			}
			return this.tabs[0]?.id ?? this.activeTab
		},

		currentView() {
			return {
				id: 'notes',
				name: this.t('notes', 'Notes'),
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
		async initializeTabs() {
			const tabs = this.availableTabs
			if (tabs.length === 0) {
				this.loadingTab = false
				return
			}

			const requestToken = this.contextRequestToken
			this.loadingTab = true

			const results = await Promise.all(tabs.map(initializeTab))

			if (requestToken !== this.contextRequestToken) {
				return
			}

			tabs.forEach((tab, index) => {
				if (!results[index]) {
					this.failedTabs.add(tab.tagName)
				}
			})

			this.loadingTab = false
		},

		resetContext() {
			this.contextRequestToken += 1
			this.contextError = ''
			this.currentNode = null
			this.currentFolder = null
			this.loadingContext = false
			this.loadingTab = false
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

		onShareOpen({ noteId }) {
			return this.onSidebarOpen({ noteId, tab: 'sharing' })
		},

		async onSidebarOpen({ noteId, tab = 'sharing' }) {
			this.resetContext()
			this.noteId = Number(noteId)
			this.isOpen = true
			this.failedTabs.clear()
			this.activeTab = tab

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

			this.resetContext()
			this.noteId = null
		},
	},
}
</script>

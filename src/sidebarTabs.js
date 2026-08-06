/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import logger from './Logger.js'

/**
 * Files sidebar tabs the Notes sidebar hosts, and nothing else.
 *
 * Notes dispatches OCA\Files\Event\LoadSidebar when rendering its page, so every
 * app that registers a sidebar tab has registered one by the time this runs —
 * including tabs that make no sense for a note. This is an allow-list so a newly
 * installed app cannot start appearing in the Notes sidebar unannounced.
 *
 * @type {string[]}
 */
export const NOTE_SIDEBAR_TAB_IDS = ['sharing', 'files_versions']

/**
 * The tabs to render, in the order the registering apps asked for.
 *
 * A tab's own `enabled()` predicate has the final say — the versions tab for
 * instance hides itself on public shares and for anything that is not a file —
 * but it needs a node to judge, so while the node is still loading the tabs are
 * kept and filtered again once it arrives. A predicate that throws is treated as
 * "not usable" rather than being allowed to take the sidebar down.
 *
 * @param {Array<object>} tabs all registered tabs, from getSidebarTabs()
 * @param {object} context what the tab is being asked about
 * @param {object|null} context.node the note's DAV node, null while loading
 * @param {object|null} context.folder the note's parent folder
 * @param {object|null} context.view the pseudo view Notes reports
 * @return {Array<object>} tabs to render, sorted by their declared order
 */
export function selectNoteSidebarTabs(tabs, { node = null, folder = null, view = null } = {}) {
	return (tabs ?? [])
		.filter((tab) => NOTE_SIDEBAR_TAB_IDS.includes(tab?.id))
		.filter((tab) => {
			if (typeof tab.enabled !== 'function' || node === null) {
				return true
			}
			try {
				return tab.enabled({ node, folder, view })
			} catch (error) {
				logger.error('Sidebar tab predicate failed in Notes, dropping the tab', { error, tab: tab.id })
				return false
			}
		})
		.sort((a, b) => (a.order ?? 0) - (b.order ?? 0))
}

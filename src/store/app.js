/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { set } from 'vue'

const state = {
	settings: {},
	isSaving: false,
	isManualSave: false,
	documentTitle: null,
	searchText: '',
	toggleNotesList: false,
}

const getters = {
	getToggleNotesList: (state) => () => {
		return state.toggleNotesList
	},
}

const mutations = {
	setSettings(state, settings) {
		state.settings = settings
	},

	setNoteMode(state, mode) {
		set(state.settings, 'noteMode', mode)
	},

	setSaving(state, isSaving) {
		state.isSaving = isSaving
	},

	setManualSave(state, isManualSave) {
		state.isManualSave = isManualSave
	},

	setDocumentTitle(state, title) {
		state.documentTitle = title
	},

	updateSearchText(state, searchText) {
		state.searchText = searchText
	},

	setToggleNotesList(state, value) {
		state.toggleNotesList = value
	},
}

const actions = {
}

export default { state, getters, mutations, actions }

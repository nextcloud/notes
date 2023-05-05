import { set } from 'vue'

const state = {
	settings: {},
	isSaving: false,
	isManualSave: false,
	documentTitle: null,
	sidebarOpen: false,
	searchText: '',
}

const getters = {
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

	setSidebarOpen(state, open) {
		state.sidebarOpen = open
	},

	updateSearchText(state, searchText) {
		state.searchText = searchText
	},
}

const actions = {
}

export default { state, getters, mutations, actions }

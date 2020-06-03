const state = {
	settings: {},
	isSaving: false,
	isManualSave: false,
	documentTitle: null,
	sidebarOpen: false,
}

const getters = {
}

const mutations = {
	setSettings(state, settings) {
		state.settings = settings
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
}

const actions = {
}

export default { state, getters, mutations, actions }

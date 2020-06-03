const state = {
	etag: null,
	lastModified: 0,
	active: false,
	// TODO add list of notes with changes during sync
}

const getters = {
}

const mutations = {
	setSyncETag(state, etag) {
		if (etag) {
			state.etag = etag
		}
	},

	setSyncLastModified(state, strLastModified) {
		const lastModified = Date.parse(strLastModified)
		if (lastModified) {
			state.lastModified = lastModified / 1000
		}
	},

	setSyncActive(state, active) {
		state.active = active
	},
}

const actions = {
}

export default { state, getters, mutations, actions }

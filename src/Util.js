export const noteAttributes = [
	'id',
	'etag',
	'title',
	'content',
	'modified',
	'favorite',
	'category',
]

export const copyNote = (from, to, exclude) => {
	if (exclude === undefined) {
		exclude = []
	}
	noteAttributes.forEach(attr => {
		if (!exclude.includes(attr)) {
			to[attr] = from[attr]
		}
	})
	return to
}

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

export const categoryLabel = (category) => {
	return category === '' ? t('notes', 'Uncategorized') : category.replace(/\//g, ' / ')
}

export const routeIsNewNote = ($route) => {
	return {}.hasOwnProperty.call($route.query, 'new')
}

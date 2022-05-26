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

export const getDefaultSampleNote = () => {
	return t('notes', `# My notes

* 📅 15 January 2021, via Nextcloud Notes
* 👥 Me, you, and all our friends!

## Tasks ✅

* [ ] Write nice todo lists
* [ ] Buy Fries
* [ ] …

## Birthdays

* Jen, in three days!
* Moss, 21.03.1973
* Roy, 1979

## Review Steps 🔁

1. Turn PC off
2. Turn PC on
3. Then call IT

## Quotes 💬

>Dear Sir stroke Madam, I am writing to inform you of a fire which has broken out on the premises of… no, that’s too formal. Dear Sir stroke Madam. Fire…exclamation mark. Fire…exclamation mark. Help me…exclamation mark. 123 Carrendon Road. Looking forward to hearing from you. All the best, Maurice Moss.
`)
}

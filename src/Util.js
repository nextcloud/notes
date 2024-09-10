/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

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

export const getDefaultSampleNoteTitle = () => {
	return t('notes', 'Sample note')
}

export const getDefaultSampleNote = () => {
	return '# ' + getDefaultSampleNoteTitle() + `

* 📅 ` + t('notes', '15 January 2021, via Nextcloud Notes') + `
* 👥 ` + t('notes', 'Me, you, and all our friends!') + `

## ` + t('notes', 'Tasks') + ` ✅

* [ ] ` + t('notes', 'Write nice todo lists') + `
* [ ] ` + t('notes', 'Buy Fries') + `
* [ ] …

## ` + t('notes', 'Birthdays') + `

* ` + t('notes', 'Jen, in three days!') + `
* ` + t('notes', 'Moss, 21.03.1973') + `
* ` + t('notes', 'Roy, 1979') + `

## ` + t('notes', 'Review Steps') + ` 🔁

1. ` + t('notes', 'Turn PC off') + `
2. ` + t('notes', 'Turn PC on') + `
3. ` + t('notes', 'Then call IT') + `

## ` + t('notes', 'Quotes') + ` 💬

> ` + t('notes', 'Nextcloud, a safe home for all your data') + `
`
}

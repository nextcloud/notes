<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<Fragment>
		<div class="feature icon-phone">
			{{ t('notes', 'Install the app for your mobile phone in order to access your notes from everywhere.') }}
		</div>
		<div class="badge-wrapper">
			<a target="_blank" href="https://github.com/nextcloud/notes-android">
				{{ t('notes', 'Android app: {notes}', {notes: 'Nextcloud Notes'}) }}
			</a>
			<div>
				<div class="badge">
					<a target="_blank" href="https://play.google.com/store/apps/details?id=it.niedermann.owncloud.notes">
						<img :src="getRoute('badge_playstore.svg')" class="appstore-badge badge-playstore-fix">
					</a>
				</div>
				<div class="badge">
					<a target="_blank" href="https://f-droid.org/repository/browse/?fdid=it.niedermann.owncloud.notes">
						<img :src="getRoute('badge_fdroid.svg')" class="appstore-badge">
					</a>
				</div>
			</div>
		</div>
		<div class="badge-wrapper">
			<a target="_blank" href="https://github.com/nextcloud/notes-ios">
				{{ t('notes', 'iOS app: {notes}', {notes: 'Nextcloud Notes'}) }}
			</a>
			<div>
				<div class="badge">
					<a target="_blank" href="https://apps.apple.com/app/nextcloud-notes/id813973264">
						<img :src="getRoute('badge_applestore.svg')" class="appstore-badge badge-playstore-fix">
					</a>
				</div>
			</div>
		</div>
	</Fragment>
</template>
<script>

import { generateFilePath } from '@nextcloud/router'

import { Fragment } from 'vue-frag'

export default {
	components: {
		Fragment,
	},

	methods: {
		getRoute(file) {
			return generateFilePath('notes', 'img', file)
		},
	},
}

</script>
<style scoped>
.badge-wrapper {
	margin-top: 2em;
	margin-inline-start: 2em;
	width: 100%;
	clear:both;
}

body[dir='ltr'] .badge {
	float: left;
}

body[dir='rtl'] .badge {
	float: right;
}

.appstore-badge {
	height: 72px;
	box-sizing: border-box;
}

.badge-playstore-fix {
	padding: 12px;
}

</style>

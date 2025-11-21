<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppContent class="loading" />
</template>
<script>

import NcAppContent from '@nextcloud/vue/components/NcAppContent'

export default {
	name: 'Loading',

	components: {
		NcAppContent,
	},
}

</script>

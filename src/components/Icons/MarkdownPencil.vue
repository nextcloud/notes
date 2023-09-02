<template>
  <span class="material-design-icon">
     <svg-icon type="mdi" :path="path" :width="size" :height="size"></svg-icon>
  </span>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import { mdiPencil } from '@mdi/js';
export default {
	name: "MarkdownPencil",
	components: {
		SvgIcon
	},
	props: {
		size: {
			type: Number,
			default: 24
		},
	},
	data() {
		return {
			path: mdiPencil,
		}
	}
}
</script>

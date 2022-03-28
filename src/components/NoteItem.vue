<template>
	<ListItem
		:title="title"
		:active="isSelected"
		:to="{ name: 'note', params: { noteId: note.id.toString() } }"
		@click="onNoteSelected(note.id)"
	>
		<template #subtitle>
			{{ categoryTitle }}
		</template>
		<template #icon>
			<AlertOctagonIcon v-if="note.error"
				slot="icon"
				:size="20"
				fillColor="#E9322D"
			/>
			<StarIcon v-else-if="note.favorite"
				slot="icon"
				:size="20"
				fill-color="#FC0"
			/>
			<NoteTextOutlineIcon v-else
				slot="icon"
				:size="20"
				fill-color="var(--color-text-lighter)"
			/>
		</template>
	</ListItem>
</template>

<script>
import { ListItem } from '@nextcloud/vue'
import AlertOctagonIcon from 'vue-material-design-icons/AlertOctagon'
import StarIcon from 'vue-material-design-icons/Star'
import NoteTextOutlineIcon from 'vue-material-design-icons/NoteTextOutline'
import { categoryLabel } from '../Util'

export default {
	name: 'NoteItem',

	components: {
		ListItem,
		AlertOctagonIcon,
		StarIcon,
		NoteTextOutlineIcon,
	},

	props: {
		note: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			loading: {
				note: false,
			},
		}
	},

	computed: {
		isSelected() {
			return this.$store.getters.getSelectedNote() === this.note.id
		},

		title() {
			return this.note.title + (this.note.unsaved ? ' *' : '')
		},

		categoryTitle() {
			return categoryLabel(this.note.category)
		},
	},

	methods: {
		onNoteSelected(noteId) {
			this.$store.commit('setSelectedNote', noteId)
		},
	},
}
</script>

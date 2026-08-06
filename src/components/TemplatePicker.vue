<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog :open="true"
		size="large"
		:name="t('notes', 'New note')"
		@update:open="onClose"
	>
		<ul class="template-picker__list">
			<li v-for="option in options" :key="option.key" class="template-picker__item">
				<!--
					A radio rather than a button: this is a single choice out of a
					set, so screen readers and arrow keys should treat it as one.
					The input itself is visually hidden and the label is the card.
				-->
				<input :id="'template-' + option.key"
					v-model="selected"
					type="radio"
					name="note-template"
					class="template-picker__radio"
					:value="option.key"
				>
				<label class="template-picker__label" :for="'template-' + option.key">
					<span class="template-picker__preview"
						:class="{ 'template-picker__preview--selected': selected === option.key }"
					>
						<img v-if="option.previewUrl"
							:src="option.previewUrl"
							alt=""
							class="template-picker__image"
							@error="onPreviewFailed(option)"
						>
						<!-- eslint-disable-next-line vue/no-v-html -- server-provided app icon, same as the Files template picker -->
						<span v-else-if="option.iconSvgInline" class="template-picker__icon" v-html="option.iconSvgInline" />
						<FileOutlineIcon v-else :size="44" />
					</span>
					<span class="template-picker__title">{{ option.label }}</span>
				</label>
			</li>
		</ul>

		<template #actions>
			<NcButton variant="tertiary" :disabled="creating" @click="onClose">
				{{ t('notes', 'Cancel') }}
			</NcButton>
			<NcButton variant="primary" :disabled="creating" @click="onCreate">
				<template v-if="creating" #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{ t('notes', 'Create note') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import FileOutlineIcon from 'vue-material-design-icons/FileOutline.vue'
import { templateLabel } from '../TemplateService.js'

const BLANK = 'blank'

export default {
	name: 'TemplatePicker',

	components: {
		FileOutlineIcon,
		NcButton,
		NcDialog,
		NcLoadingIcon,
	},

	props: {
		/** Templates as returned by fetchNoteTemplates() */
		templates: {
			type: Array,
			required: true,
		},

		/** Whether a note is currently being created */
		creating: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['close', 'select'],

	data() {
		return {
			selected: BLANK,
			// previews that 404ed, so the card falls back to an icon
			brokenPreviews: [],
		}
	},

	computed: {
		options() {
			return [
				{
					key: BLANK,
					label: t('notes', 'Blank note'),
					template: null,
					previewUrl: null,
					iconSvgInline: null,
				},
				...this.templates.map((template) => ({
					key: String(template.fileid ?? template.templateId),
					label: templateLabel(template),
					template,
					// the service resolves this to a usable URL or null; a URL that
					// still fails to load is remembered so the card falls back
					previewUrl: this.brokenPreviews.includes(template.fileid)
						? null
						: template.previewUrl,
					iconSvgInline: template.iconSvgInline,
				})),
			]
		},
	},

	methods: {
		onPreviewFailed(option) {
			const fileid = option.template?.fileid
			if (fileid !== undefined && !this.brokenPreviews.includes(fileid)) {
				this.brokenPreviews.push(fileid)
			}
		},

		onCreate() {
			const option = this.options.find((candidate) => candidate.key === this.selected)
			this.$emit('select', option?.template ?? null)
		},

		onClose() {
			if (!this.creating) {
				this.$emit('close')
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.template-picker__list {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
	gap: calc(var(--default-grid-baseline) * 3);
	padding: calc(var(--default-grid-baseline) * 2);
	margin: 0;
}

.template-picker__radio {
	/* visually hidden but still focusable, so the card is keyboard reachable */
	position: absolute;
	width: 1px;
	height: 1px;
	opacity: 0;
	pointer-events: none;
}

.template-picker__label {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: var(--default-grid-baseline);
	cursor: pointer;
}

.template-picker__preview {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 100%;
	aspect-ratio: 1 / 1.4;
	overflow: hidden;
	border: 2px solid var(--color-border);
	border-radius: var(--border-radius-large);
	background-color: var(--color-main-background);
}

.template-picker__preview--selected {
	border-color: var(--color-primary-element);
}

/* the focus ring has to come from the card, since the input is hidden */
.template-picker__radio:focus-visible + .template-picker__label .template-picker__preview {
	outline: 2px solid var(--color-main-text);
	outline-offset: 2px;
}

.template-picker__image {
	width: 100%;
	height: 100%;
	object-fit: cover;
	object-position: top center;
}

.template-picker__icon:deep(svg) {
	width: 44px;
	height: 44px;
}

.template-picker__title {
	text-align: center;
	overflow-wrap: anywhere;
}
</style>

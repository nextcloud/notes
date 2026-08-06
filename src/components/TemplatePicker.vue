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
			<li v-for="(option, index) in options" :key="option.key" class="template-picker__item">
				<!--
					A radio rather than a button: one choice out of a set. The input
					is visually hidden, the label is the card, and the id comes from
					the position because the key can be a file path.
				-->
				<input :id="`note-template-${index}`"
					v-model="selected"
					type="radio"
					name="note-template"
					class="template-picker__radio"
					:value="option.key"
				>
				<label class="template-picker__label" :for="`note-template-${index}`">
					<span class="template-picker__preview"
						:class="{
							'template-picker__preview--selected': selected === option.key,
							'template-picker__preview--icon': !option.previewUrl,
						}"
					>
						<img v-if="option.previewUrl"
							:src="option.previewUrl"
							alt=""
							class="template-picker__image"
							@error="onPreviewFailed(option)"
						>
						<NcIconSvgWrapper v-else-if="option.iconSvgInline" :svg="option.iconSvgInline" :size="44" />
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
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
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
		NcIconSvgWrapper,
		NcLoadingIcon,
	},

	props: {
		/** Templates as returned by fetchNoteTemplates() */
		templates: {
			type: Array,
			required: true,
		},

		creating: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['close', 'select'],

	data() {
		return {
			selected: BLANK,
			// keys of previews that failed to load, so the card falls back to an icon
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
				...this.templates.map((template) => {
					const key = String(template.fileid ?? template.templateId)

					return {
						key,
						label: templateLabel(template),
						template,
						previewUrl: this.brokenPreviews.includes(key) ? null : template.previewUrl,
						iconSvgInline: template.iconSvgInline,
					}
				}),
			]
		},
	},

	methods: {
		onPreviewFailed(option) {
			if (!this.brokenPreviews.includes(option.key)) {
				this.brokenPreviews.push(option.key)
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
	/* fixed card width and centred columns, like the Files template picker */
	grid-template-columns: repeat(auto-fit, 240px);
	/* every card in a row gets the height of the tallest preview */
	grid-auto-rows: 1fr;
	justify-content: center;
	gap: calc(var(--default-grid-baseline) * 4);
	padding: calc(var(--default-grid-baseline) * 2);
	margin: 0;
}

.template-picker__item {
	display: flex;
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
	flex: 1 1;
	flex-direction: column;
	align-items: center;
	gap: var(--default-grid-baseline);
	cursor: pointer;
}

.template-picker__preview {
	display: flex;
	flex: 1 1;
	/* a note reads from its top, so the preview is not centred vertically */
	align-items: flex-start;
	justify-content: center;
	width: 100%;
	overflow: hidden;
	border: 2px solid var(--color-border);
	border-radius: var(--border-radius-large);
	background-color: var(--color-main-background);
}

.template-picker__preview--icon {
	align-items: center;
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
	/* the natural height keeps the whole preview visible instead of cropping it */
	max-width: 100%;
}

.template-picker__title {
	text-align: center;
	overflow-wrap: anywhere;
}
</style>

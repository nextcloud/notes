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
		<ul class="template-picker__list" :style="style">
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
							'template-picker__preview--icon': option.isIcon,
						}"
					>
						<img :src="option.imageUrl"
							alt=""
							draggable="false"
							class="template-picker__image"
							@error="onPreviewFailed(option)"
						>
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
import { NOTE_MIMETYPE, templateLabel } from '../TemplateService.js'

const BLANK = 'blank'

/** Card metrics of the Files template picker, in pixels. */
const MARGIN = 8
const BORDER = 2

/** Aspect ratio to lay the cards out with when a creator reports none. */
const FALLBACK_RATIO = 1.77

export default {
	name: 'TemplatePicker',

	components: {
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
					imageUrl: OC.MimeType.getIconUrl(NOTE_MIMETYPE),
					isIcon: true,
				},
				...this.templates.map((template) => {
					const key = String(template.fileid ?? template.templateId)
					const previewUrl = this.brokenPreviews.includes(key) ? null : template.previewUrl

					return {
						key,
						label: templateLabel(template),
						template,
						imageUrl: previewUrl ?? OC.MimeType.getIconUrl(template.mime),
						isIcon: !previewUrl,
					}
				}),
			]
		},

		/**
		 * Card geometry as CSS variables, the way the Files picker sizes its own
		 * cards: portrait templates get narrower cards than landscape ones, and
		 * the preview is clipped to the creator's aspect ratio.
		 *
		 * @return {object} CSS variables for the card grid
		 */
		style() {
			const ratio = this.templates.find((template) => template.ratio)?.ratio ?? FALLBACK_RATIO
			const width = ratio > 1 ? MARGIN * 30 : MARGIN * 20

			return {
				'--margin': `${MARGIN}px`,
				'--border': `${BORDER}px`,
				'--width': `${width}px`,
				'--height': `${Math.round(width / ratio)}px`,
				'--fullwidth': `${width + 2 * MARGIN + 2 * BORDER}px`,
			}
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
	/* at most 5 columns, centred: the gaps keep 6 card widths out of reach */
	grid-template-columns: repeat(auto-fit, var(--fullwidth));
	/* every card in a row gets the height of the tallest one */
	grid-auto-rows: 1fr;
	justify-content: center;
	max-width: calc(var(--fullwidth) * 6);
	gap: calc(var(--margin) * 2);
	padding: var(--margin) 0;
	margin: 0 auto;
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
	cursor: pointer;
}

.template-picker__preview {
	display: block;
	/* the preview is clipped to the card instead of scaling it down */
	overflow: hidden;
	flex: 1 1;
	width: var(--width);
	min-height: var(--height);
	max-height: var(--height);
	border: var(--border) solid var(--color-border);
	border-radius: var(--border-radius-large);
	background-color: var(--color-main-background);
}

.template-picker__preview--icon {
	/* centres the mimetype icon of a card without a thumbnail */
	display: flex;
	background-color: transparent;
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
	max-width: 100%;
	object-fit: cover;
}

.template-picker__preview--icon .template-picker__image {
	width: calc(var(--margin) * 8);
	margin: auto;
	object-fit: initial;
}

.template-picker__title {
	max-width: calc(var(--width) + 2 * var(--border));
	padding: var(--margin);
	text-align: center;
	overflow-wrap: anywhere;
}
</style>

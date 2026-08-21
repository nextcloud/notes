<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\Service;

use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

/**
 * Keeps the visible label of note-to-note links in step with the note's title.
 *
 * Notes are linked with ordinary markdown links whose target is the note's id:
 *
 *     [Shopping list](/index.php/apps/notes/note/42)
 *
 * Because the target is an id, renaming note 42 can never break a link to it —
 * only the label goes stale. This service refreshes those labels.
 *
 * Two deliberate limits, because this edits notes the user is not looking at:
 *
 *  - Only labels that still match the *old* title are rewritten. If somebody
 *    wrote `[my weekly shop](…/note/42)` that is their wording, not a stale
 *    copy of the title, and it is left alone.
 *  - Only explicit renames call this. Titles also change through `autotitle`,
 *    which fires while a new note is being typed, and rewriting the whole
 *    collection on every one of those would be both wasteful and surprising.
 */
class NoteLinkService {
	public function __construct(
		private NotesService $notesService,
		private IURLGenerator $urlGenerator,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Rewrite `[$oldTitle](<link to $noteId>)` to use `$newTitle`.
	 *
	 * Never throws: a rename must not fail because a link could not be tidied
	 * up. Notes that cannot be read or written are skipped.
	 *
	 * @return int number of notes changed
	 */
	public function refreshLinkLabels(string $userId, int $noteId, string $oldTitle, string $newTitle): int {
		if ($oldTitle === '' || $oldTitle === $newTitle) {
			return 0;
		}

		$pattern = $this->linkPattern($noteId, $oldTitle);
		// ${1} is the target, ${2} any trailing slashes it was written with
		$replacement = '[' . $this->escapeReplacement($newTitle) . '](${1}${2})';
		$changed = 0;

		foreach ($this->notesService->getAll($userId)['notes'] as $note) {
			if ($note->getId() === $noteId) {
				continue;
			}

			try {
				$content = $note->getContent();
				$updated = preg_replace($pattern, $replacement, $content);
				if ($updated === null || $updated === $content) {
					continue;
				}
				$note->setContent($updated);
				$changed++;
			} catch (\Throwable $e) {
				// a read-only note, or one that vanished mid-walk
				$this->logger->debug('Could not refresh note links in ' . $note->getId(), ['exception' => $e]);
			}
		}

		return $changed;
	}

	/**
	 * Matches a markdown link whose label is $oldTitle and whose target is any
	 * spelling of the route to $noteId — absolute or root-relative, with or
	 * without the /index.php prefix.
	 */
	private function linkPattern(int $noteId, string $oldTitle): string {
		$targets = [];
		foreach (['/apps/notes/note/', '/index.php/apps/notes/note/'] as $path) {
			$absolute = $this->urlGenerator->getAbsoluteURL($path . $noteId);
			$targets[] = $absolute;
			$relative = parse_url($absolute, PHP_URL_PATH);
			if (is_string($relative) && $relative !== '') {
				$targets[] = $relative;
			}
		}

		$targets = array_map(
			static fn (string $target): string => preg_quote($target, '/'),
			array_values(array_unique($targets)),
		);

		return '/\[' . preg_quote($oldTitle, '/') . '\]\(\s*(' . implode('|', $targets) . ')(\/*)\s*\)/u';
	}

	/**
	 * `$` and `\` carry meaning in a preg_replace replacement, so a title
	 * containing them would otherwise corrupt the link.
	 */
	private function escapeReplacement(string $value): string {
		return str_replace(['\\', '$'], ['\\\\', '\\$'], $value);
	}
}

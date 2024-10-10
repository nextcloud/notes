<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notes\AppInfo;

use OCA\Notes\Service\Note;
use OCA\Notes\Service\NotesService;
use OCA\Notes\Service\Util;

use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

class SearchProvider implements IProvider {
	private Util $util;
	private NotesService $notesService;
	private IURLGenerator $url;

	public function __construct(
		Util $util,
		NotesService $notesService,
		IURLGenerator $url,
	) {
		$this->util = $util;
		$this->notesService = $notesService;
		$this->url = $url;
	}


	public function getId(): string {
		return Application::APP_ID;
	}

	public function getName(): string {
		return $this->util->l10n->t('Notes');
	}

	public function getOrder(string $route, array $routeParameters): int {
		if (strpos($route, 'files' . '.') === 0) {
			return 25;
		} elseif (strpos($route, Application::APP_ID . '.') === 0) {
			return -1;
		}
		return 4;
	}

	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$notes = $this->notesService->search($user->getUID(), $query->getTerm());
		// sort by modified time
		usort($notes, function (Note $a, Note $b) {
			return $b->getModified() - $a->getModified();
		});
		// create SearchResultEntry from Note
		$result = array_map(
			function (Note $note) : SearchResultEntry {
				$excerpt = $note->getCategory();
				try {
					$excerpt = $note->getExcerpt();
				} catch (\Throwable $e) {
				}
				return new SearchResultEntry(
					'',
					$note->getTitle(),
					$excerpt,
					$this->url->linkToRouteAbsolute('notes.page.indexnote', [ 'id' => $note->getId() ]),
					'icon-notes-trans'
				);
			},
			$notes
		);
		return SearchResult::complete(
			$this->getName(),
			$result
		);
	}
}

<?php

declare(strict_types=1);

namespace OCA\Notes\AppInfo;

use OCA\Notes\Service\Note;
use OCA\Notes\Service\NotesService;
use OCA\Notes\Service\Util;

use OCP\IUser;
use OCP\IURLGenerator;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

class SearchProvider implements IProvider {

	/** @var Util */
	private $util;
	/** @var NotesService */
	private $notesService;
	/** @var IURLGenerator */
	private $url;

	public function __construct(
		Util $util,
		NotesService $notesService,
		IURLGenerator $url
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
					$this->url->linkToRouteAbsolute('notes.page.index') . 'note/'.$note->getId(),
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

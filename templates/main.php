<?php
/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * Copyright (c) 2013, Jan-Christoph Borchardt http://jancborchardt.net
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */


\OCP\Util::addScript('notes', 'vendor/markdown/lib/markdown');
\OCP\Util::addScript('notes', 'vendor/bootstrap/tooltip');
\OCP\Util::addScript('notes', 'vendor/angular/angular');
\OCP\Util::addScript('notes', 'vendor/angular-route/angular-route');
\OCP\Util::addScript('notes', 'vendor/restangular/dist/restangular');
\OCP\Util::addScript('notes', 'vendor/underscore/underscore');
\OCP\Util::addScript('notes', 'public/app');

\OCP\Util::addStyle('notes', 'vendor/bootstrap/tooltip');
\OCP\Util::addStyle('notes', 'notes');

?>

<div id="app" ng-app="Notes" ng-controller="AppController"
	ng-init="init(<?php p($_['lastViewedNote']); ?>)">

	<?php print_unescaped($this->inc('translations')); ?>

	<script type="text/ng-template" id="note.html">
		<?php print_unescaped($this->inc('note')); ?>
	</script>

	<div id="app-navigation" ng-controller="NotesController">
		<ul>
			<!-- new note button -->
			<li id="note-add" ng-click="create()"
				oc-click-focus="{ selector: '#app-content textarea' }">
				<a href='#'>+ <span><?php p($l->t('New note')); ?></span></a>
			</li>
			<!-- notes list -->
			<li ng-repeat="note in notes|orderBy:'modified':'reverse'"
				ng-class="{ active: note.id == route.noteId }">
				<a href="#/notes/{{ note.id }}">
					{{ note.title }}
				</a>
				<span class="utils">
					<button class="svg action delete-icon"
						title="<?php p($l->t('Delete note')); ?>"
						notes-tooltip
						data-placement="bottom"
						ng-click="delete(note.id)"></button>	
				</span>
			</li>

		</ul>
	</div>

	<div id="app-content" ng-view ng-class="{loading: is.loading}"></div>
</div>

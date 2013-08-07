<?php 
/**
 * Copyright (c) 2013, Bernhard Posselt <nukeawhale@gmail.com>
 * Copyright (c) 2013, Jan-Christoph Borchardt http://jancborchardt.net
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING file.
 */


\OCP\Util::addScript('notes', 'vendor/angular/angular');
\OCP\Util::addScript('notes', 'vendor/restangular/restangular');
\OCP\Util::addScript('notes', 'vendor/underscore/underscore');
\OCP\Util::addScript('notes', 'public/app');

\OCP\Util::addStyle('notes', 'notes');

?>

<div id="app" ng-app="Notes">

	<script type="text/ng-template" id="note.html">
		<?php print_unescaped($this->inc('note')); ?>
	</script>

	<div id="app-navigation" ng-controller="NotesController">
		<ul>
			<!-- new note button -->
			<li id="note-add" ng-click="createNew()"
				oc-click-focus="{ selector: '#app-content textarea' }">
				<a href='#'>+ <span><?php p($l->t('New Note')); ?></span></a>
			</li>
			<!-- notes list -->
			<li ng-repeat="note in notes|orderBy:'modified':'reverse'" 
				ng-class="{ active: note.id == route.noteId }">
				<a href="#/notes/{{ note.id }}">
					{{ note.title }} 
				</a>
			</li>
			
		</ul>
	</div>

	<div id="app-content" ng-view></div>
</div>

/*global SimpleMDE*/
app.directive('editor', ['$timeout',
                         'urlFinder',
                         function ($timeout, urlFinder) {
	'use strict';
	return {
		restrict: 'A',
		link: function(scope, element) {

			var simplemde = new SimpleMDE({
				element: element[0],
				spellChecker: false,
				autoDownloadFontAwesome: false,
				toolbar: false,
				status: false,
				forceSync: true
			});
			var editorElement = $(simplemde.codemirror.getWrapperElement());

			simplemde.value(scope.note.content);
			simplemde.codemirror.focus();

			/* Initialize Checkboxes */
			$('.CodeMirror').on('click.checkbox', '.cm-formatting-task', function (e) {
				e.stopPropagation();
				e.preventDefault();

				scope.toggleCheckbox(e.target);
			});


			simplemde.codemirror.on('change', function() {
				$timeout(function() {
					scope.$apply(function () {
						scope.note.content = simplemde.value();
						scope.onEdit();
						scope.updateTitle();
					});
				});
			});

			editorElement.on('click', '.cm-link, .cm-url', function(event) {
				if(event.ctrlKey) {
					var url = urlFinder(this);
					if(angular.isDefined(url)) {
						window.open(url, '_blank');
					}
				}
			});
		}
	};
}]);

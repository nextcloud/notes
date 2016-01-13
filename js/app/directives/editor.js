/*global mdEdit*/
app.directive('editor', ['$timeout', function ($timeout) {
	'use strict';
	return {
		restrict: 'A',
		link: function(scope, element) {
			var editor = mdEdit(element[0], {change: function(value) {
				$timeout(function(){
					scope.$apply(function() {
						scope.note.content = value;
						scope.updateTitle();
					});
				});
			}});
			editor.setValue(scope.note.content);
			element.on('click', '.link', function(event) {
				if(event.ctrlKey) {
					var url = $(this).find('.link-params-inner').text();
					window.open(url, '_blank');
				}
			});
		}
	};
}]);

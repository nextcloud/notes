app.controller('NotesSettingsController', function($scope, Restangular, $document) {
    'use strict';

    Restangular.one('settings').get().then(function(settings) {
        if(angular.isObject(settings))
            $scope.settings = settings;
        else $scope.settings = Restangular.one('settings');
    });

    $document.on('change', '#notesPath', function(event) {
        $scope.settings.put();
    });
});
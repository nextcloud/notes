app.controller('NotesSettingsController',
               function($scope, Restangular, $document) {
    'use strict';

    $scope.extensions = ['.txt', '.md'];

    Restangular.one('settings').get().then(function(settings) {
        if(angular.isObject(settings)) {
            $scope.settings = settings;
        } else {
            $scope.settings = Restangular.one('settings');
        }
    });

    $document.on('change', '#notesPath', function() {
        var msg = t('notes', 'Please wait while new settings are applied ...');
        OC.Notification.showTemporary(msg);
        $scope.settings.put().then(function() {
            window.location.reload(true);
        });
    });

    $document.on('change', '#fileSuffix', function() {
        $scope.settings.put();
    });
});

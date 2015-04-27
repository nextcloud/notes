/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

describe('NotesController', function() {
    'use strict';

    var controller,
        scope,
        model,
        routeParams,
        location,
        http;

    // use the Notes container
    beforeEach(module('Notes'));

    beforeEach(inject(function ($controller, $rootScope, $httpBackend,
        NotesModel) {
        http = $httpBackend;
        scope = $rootScope.$new();
        routeParams = {
            noteId: 3
        };
        model = NotesModel;
        location = {
            path: jasmine.createSpy('path')
        };
        controller = $controller;
    }));


    it ('should load notes and attach them to scope', function() {
        var notes = [
            {id: 3, title: 'hey'}
        ];
        http.expectGET('/notes').respond(200, notes);

        controller = controller('NotesController', {
            $routeParams: routeParams,
            $scope: scope,
            $location: location,
            NotesModel: model
        });

        http.flush(1);

        expect(scope.notes[0].title).toBe('hey');
        expect(scope.route).toBe(routeParams);
    });


    it ('should do a create request', function() {
        http.expectGET('/notes').respond(200, [{}]);

        controller = controller('NotesController', {
            $routeParams: routeParams,
            $scope: scope,
            $location: location,
            NotesModel: model
        });

        http.flush(1);

        var note = {
            id: 3,
            title: 'yo'
        };
        http.expectPOST('/notes').respond(note);
        scope.create();
        http.flush(1);

        expect(model.get(3).title).toBe('yo');
        expect(location.path).toHaveBeenCalledWith('/notes/3');
    });


    it ('should delete a note', function () {
        var notes = [
            {id: 3, title: 'hey'}
        ];

        http.expectGET('/notes').respond(200, notes);

        controller = controller('NotesController', {
            $routeParams: routeParams,
            $scope: scope,
            $location: location,
            NotesModel: model
        });

        http.flush(1);

        http.expectDELETE('/notes/3').respond(200, {});
        scope.$emit = jasmine.createSpy('$emit');
        scope.delete(3);
        http.flush(1);

        expect(model.get(3)).not.toBeDefined();
        expect(scope.$emit).toHaveBeenCalledWith('$routeChangeError');
    });


    afterEach(function() {
        http.verifyNoOutstandingExpectation();
        http.verifyNoOutstandingRequest();
    });


});








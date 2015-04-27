/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

describe('SaveQueue', function() {
    'use strict';

    var http,
        q,
        rootScope;

    beforeEach(module('Notes'));

    beforeEach(inject(function ($httpBackend, $q, $rootScope) {
        http = $httpBackend;
        q = $q;
        rootScope = $rootScope;
    }));

    it ('should sync a note', inject(function(SaveQueue) {
        var request = q.defer();
        var note = {
            id: 3,
            put: function () {
                return request.promise;
            }
        };
        SaveQueue.add(note);

        request.resolve({
            title: 'yo',
            modified: 4
        });

        // $q needs a digest on $rootScope
        rootScope.$apply();

        expect(note.title).toBe('yo');
        expect(note.modified).toBe(4);
    }));


    it ('should show if it is saving a note', inject(function(SaveQueue) {
        var request = q.defer();
        var note = {
            id: 3,
            put: function () {
                return request.promise;
            }
        };
        SaveQueue.add(note);
        expect(SaveQueue.isSaving()).toBe(true);

        request.resolve({
            title: 'yo',
            modified: 4
        });

        // $q needs a digest on $rootScope
        rootScope.$apply();

        expect(SaveQueue.isSaving()).toBe(false);
    }));

    it ('should sync more notes', inject(function(SaveQueue) {
        // note 1
        var requestNote1 = q.defer();
        var note1 = {
            id: 3,
            put: function () {
                return requestNote1.promise;
            }
        };
        SaveQueue.add(note1);

        // note 2
        var requestNote2 = q.defer();
        var note2 = {
            id: 4,
            put: function () {
                return requestNote2.promise;
            }
        };
        SaveQueue.add(note2);

        requestNote1.resolve({
            title: 'note1',
            modified: 6
        });

        requestNote2.resolve({
            title: 'note2',
            modified: 7
        });

        // $q needs a digest on $rootScope
        rootScope.$apply();

        expect(note1.title).toBe('note1');
        expect(note1.modified).toBe(6);

        expect(note2.title).toBe('note2');
        expect(note2.modified).toBe(7);
    }));


    afterEach(function() {
        http.verifyNoOutstandingExpectation();
        http.verifyNoOutstandingRequest();
    });


});
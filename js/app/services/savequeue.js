/**
 * Copyright (c) 2013, Bernhard Posselt <dev@bernhard-posselt.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING file.
 */

app.factory('SaveQueue', function($q) {
    'use strict';

    var SaveQueue = function () {
        this._queue = {};
        this._flushLock = false;
    };

    SaveQueue.prototype = {
        add: function (note) {
            this._queue[note.id] = note;
            this._flush();
        },
        _flush: function () {
            // if there are no changes dont execute the requests
            var keys = Object.keys(this._queue);
            if(keys.length === 0 || this._flushLock) {
                return;
            } else {
                this._flushLock = true;
            }

            var self = this;
            var requests = [];

            // iterate over updated objects and run an update request for
            // each one of them
            for(var i=0; i<keys.length; i+=1) {
                var note = this._queue[keys[i]];
                // if the update finished, update the modified and title
                // attributes on the note
                requests.push(note.put().then(
                    this._noteUpdateRequest.bind(null, note))
                );
            }
            this._queue = {};

            // if all update requests are completed, run the flush
            // again to update the next batch of queued notes
            $q.all(requests).then(function () {
                self._flushLock = false;
                self._flush();
            });
        },
        _noteUpdateRequest: function (note, response) {
            note.title = response.title;
            note.modified = response.modified;
        },
        isSaving: function () {
            return this._flushLock;
        }
    };

    return new SaveQueue();
});
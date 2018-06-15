/* global OC */

(function($) {
    "use strict";
    $(function() {
        $('#notesSettings [name="notesPath"]').change(function() {
            $.ajax({
                method: 'POST',
                url: OC.generateUrl('apps/notes/settings/notesPath'),
                data: { notesPath: $(this).val(), },
            });
        });
    });
}(jQuery));
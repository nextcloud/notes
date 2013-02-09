$(document).ready(function () {

	var container = $('#rightcontent'), left = $('#leftcontent'), textArea = container.children('textarea');

	left.on('click', 'a', function () {
		var li = $(this).parent();
		$('#leftcontent').find('li').removeClass('active');
		Notes.save.current();
		if (li.data('new')) {
			Notes.active = '';
			textArea.val('');
		} else {
			var note = li.data('note');
			Notes.active = note;
			Notes.loadNote(note);
		}
		textArea.focus();
	});

	var note = location.hash.substr(1);
	if (note) {
		Notes.loadNote(note);
		Notes.active = note;
	}
	textArea.focus();
	textArea.keydown(Notes.onType);
	$(document).keydown(Notes.handleKey);

	setInterval(Notes.save.auto, 10 * 1000);
	setInterval(Notes.onType, 1000);

	$(window).on('beforeunload', function () {
		Notes.save.current(true);
	});
});

Notes = {};
Notes.category = '';
Notes.active = '';
Notes.oldContent = '';

Notes.get = function (category, note) {
	return $.get(OC.filePath('notes', 'ajax', 'get.php'), {category: category, note: note});
};

Notes.loadNote = function (note) {
	Notes.get(Notes.category, note).then(function (text) {
		$('#rightcontent').children('textarea').val(text);
		if (text) {
			var title = Notes.getTitle(text);
			$('#leftcontent').find('li[data-note="' + note + '"]').addClass('active');
			Notes.setTitle(title);
			Notes.oldContent = text;
		} else {
			Notes.active = '';
			location.hash = '';
		}
	});
};

Notes.getTitle = function (text) {
	return text.split('\n').shift();
};

Notes.newNote = function (title) {
	if (!title) {
		return;
	}
	var right = $('#rightcontent'),
		li = $('<li/>'),
		link = $('<a/>');
	li.append(link);
	li.attr('data-note', '');
	link.text(title);
	li.addClass('active');
	$('#leftcontent').children().first().after(li);
};

Notes.onType = function () {
	setTimeout(function () {
		var li, left = $('#leftcontent'), right = $('#rightcontent'), text = right.find('textarea').val(),
			title = Notes.getTitle(text),
			link = left.find('a.active');
		link.text(title);
		Notes.setTitle(title);
		li = left.find('li[data-note="' + Notes.active + '"]');
		if (li.length) {
			li.children('a').text(title);
			if (title) {
				li.show();
			} else {
				li.hide();
			}
		} else {
			Notes.newNote(title);
		}
		Notes.save.auto();
	}, 100);
};

Notes.setTitle = function (title) {
	if (title) {
		document.title = title + ' | ownCloud(' + OC.currentUser + ')';
	}
};

Notes.rename = function (old, newName) {
	var left = $('#leftcontent'), li = left.find('li[data-note="' + old + '"]');
	li.attr('data-note', newName);
	li.children('a').attr('href', '#' + newName);
	left.children().first().after(li);
	if (Notes.active == old) {
		Notes.active = newName;
	}
};

Notes.remove = function (old) {
	var li = $('#leftcontent').find('li[data-note="' + old + '"]').remove();
};

Notes.save = function (oldName, content, sync) {
	if (!sync) {
		var def = $.post(OC.filePath('notes', 'ajax', 'save.php'), {oldname: oldName, content: content, category: Notes.category});
		Notes.save.last = (new Date()).getTime();
		Notes.save.active = true;
		Notes.oldContent = content;
		def.then(function (newName) {
			Notes.save.active = false;
			if (content.trim()) {
				Notes.rename(oldName, newName);
			} else {
				Notes.remove(oldName);
			}
		});
		return def;
	} else {
		url = OC.filePath('notes', 'ajax', 'save.php');
		$.ajax({
			type: 'POST',
			url: url,
			data: {
				oldname: oldName,
				content: content,
				category: Notes.category
			},
			'async': false
		});
		return null;
	}
}
;

Notes.save.current = function (sync) {
	var content = $('#rightcontent').find('textarea').val();
	if (content != Notes.oldContent && !Notes.save.active) {
		Notes.save(Notes.active, content, sync);
	}
};

Notes.handleKey = function (event) {
	if (event.which === 9) { //tab
		event.preventDefault();
	}
};

Notes.save.last = (new Date()).getTime();
Notes.save.active = false;

Notes.save.auto = function () {
	now = (new Date()).getTime();
	if ((now - Notes.save.last) > 5 * 1000) {
		Notes.save.current();
		Notes.save.last = now;
	}
};

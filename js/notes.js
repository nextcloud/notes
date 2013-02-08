$(document).ready(function () {
	var container = $('#rightcontent'), edits;
	edits = container.find('img.edit');
	edits.tipsy({gravity: 'e'});
	edits.click(function () {
		var li = $(this).parent().parent();
		Notes.showEdit(li.data('note'), li);
	});
	container.find('button.save').click(function () {
		var li = $(this).parent().parent();
		Notes.saveEdit(li.data('note'), li);
	});

	$('#leftcontent').on('click', 'a', function () {
		var li = $(this).parent();
		$('#leftcontent').find('a').removeClass('active');
		if (li.data('new')) {
			Notes.newNote();
			Notes.active = '_';
		} else {
			$(this).addClass('active');
			var note = li.data('note');
			Notes.active = note;
			Notes.loadNote(note);
		}
	});

	var note = location.hash.substr(1);
	if (note) {
		Notes.loadNote(note);
	}
});

Notes = {};
Notes.category = '';
Notes.active = '';

Notes.showEdit = function (note, li) {
	Notes.getSource(Notes.category, note).then(function (source) {
		li.addClass('editing');
		var head = li.find('h2'),
			content = li.children('div'),
			contentEdit = $('<textarea/>'),
			nameEdit = $('<input/>');

		nameEdit.val(note);
		contentEdit.val(source);
		head.append(nameEdit);
		li.append(contentEdit);
	});
};

Notes.saveEdit = function (note, li) {
	var newName = li.find('input').val(),
		content = li.find('textarea').val();
	$.post(OC.filePath('notes', 'ajax', 'savedit.php'), {category: Notes.category, old: note, 'new': newName, content: content}).then(function (html) {
		li.find('h2 > span').text(newName);
		li.find('h2 > input').remove();
		li.find('textarea').remove();
		li.find('div').html(html);
		li.removeClass('editing');
	});
};

Notes.get = function (category, note) {
	return $.get(OC.filePath('notes', 'ajax', 'get.php'), {category: category, note: note});
};

Notes.loadNote = function (note) {
	Notes.get(Notes.category, note).then(function (text) {
		$('#rightcontent').children('textarea').val(text);
		var title = Notes.getTitle(text),
			titleParts = document.title.split(' | ');
		titleParts.shift();
		titleParts.unshift(title);
		document.title = titleParts.join(' | ');
	});
};

Notes.getTitle = function (text) {
	return text.split('\n').shift();
};

Notes.newNote = function () {
	$('#rightcontent').children('textarea').val('');
	var li = $('<li/>');
	var link = $('<a/>');
	li.append(link);
	$('#leftcontent').children().first().after(li);
};

Notes.onType = function () {
	if (!Notes.active) {
		Notes.newNote();
	}
	var text = $('#rightcontent').find('textarea');
	var title = Notes.getTitle(text);
};

Notes.rename = function (old, newName) {
	var li = $('#leftcontent').find('li[data-note="' + Notes.active + '"]');
	li.data('')
};

function getEventTarget(e) {
	e = e || window.event;
	return e.target || e.srcElement;
}

function checkform() {
	if (String.trim(document.notes_save.title.value) == "") {
		alert(t('notes', 'The title can not be empty') + "!");
		return false;
	}

	var invalid_characters = ['\\', '/', '<', '>', ':', '"', '|', '?', '*'];
	for (var i = 0; i < invalid_characters.length; i++) {
		if (document.notes_save.title.value.indexOf(invalid_characters[i]) != -1) {
			alert(t('notes', 'Invalid title') + "!\\n" + t('notes', 'The following characters are not allowed') + ": '\\', '/', '<', '>', ':', '\"', '|', '?', '*'");
			return false;
		}
	}

	return true;
}

$(document).ready(function () {
	var ul = document.getElementById('entries');
	ul.onclick = function (event) {
		var target = getEventTarget(event);
		var txt = target.innerHTML;
		var arr = txt.split('"');
		url = arr[1]
		url = arr[1].replace(/&amp;/g, "&");
		if (url != "active") {
// 	    alert(url);
			window.open(url, "_self");
		}
	};

	$('#notes_cat_select').on('change', function(){
		window.open('?app=notes&category='+this.options[this.selectedIndex].value, '_self');
	})
});


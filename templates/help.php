
<div id="controls">
<form action="?app=notes" method="get" target="_self">
	<input type="hidden" name="app" value="notes">
	<input type="submit" name="back" value="<?php echo $l->t('Back to Notes'); ?>">
</form>
</div>



<div id="rightcontent2" class="rightcontent2">
<div id="notes_preview">
<h1><?php echo $l->t('Help'); ?></h1><br>

<h2>About</h2>
Notes is a simple app which you can use to save your notes, shopping lists, links, howtos and much more in your OwnCloud.<br>
You can sort your notes with <b>categories</b>.<br>
The content of a note is under <b>revision control</b>, so you always can restore an earlier version.<br>
To format your notes, you can use the <b>MarkDown syntax</b> and some additional keywords.
<br><br>

<h2>Sync clients</h2>
<b>Notes</b> works with plain text files which are stored in your OwnCloud files under <b>Notes</b>.<br>
This way you can sync them easily with any other device capable of syncing with OwnCloud.<br>
There are currently several notes apps supporting the same file structure:<br>
<ul>
<li>Linux:</b> <a href=http://sourceforge.net/projects/znotes/>zNotes</a></li>
<li>Meego/NemoMobile/Sailfish:</b> <a href=http://khertan.net/khtnotes>khtNotes</a></li>
<li>Android:</b> <a href=https://play.google.com/store/apps/details?id=com.kallisto.papyrusex>Papyrus Ex</a> in conjunction with <a href=https://play.google.com/store/apps/details?id=dk.tacit.android.foldersync.lite>FolderSync</a></li>
</ul>
<br>

<h2>Formating</h2>
<h3>MarkDown Syntax</h3>
MarkDown syntax is a formating style developed by <a href="http://daringfireball.net/projects/markdown/">John Gruber</a>.<br>
You can use it to format your notes in a simple way:<br>
A <strong>title</strong> is created by a leading <strong>#</strong> or a <strong>=</strong> on a new line.<br>
A <strong>subtitle</strong> is created by a leading <strong>##</strong> or a <strong>-</strong> on a new line.<br>
<strong>Bold text</strong> has to be set between a <strong>**</strong><br>
<strong>Italic text</strong> has to be set between a <strong>*</strong><br>
An <strong>unnumbered list</strong> starts with a <strong>-</strong> and a <strong>whitespace</strong><br>
An <strong>numbered list</strong> starts with a <strong>1.</strong> and a <strong>whitespace</strong><br>
<strong>Links</strong> are created this way: [Georges Website](http://www.ruinelli.ch)<br>
<strong>Source code</strong> has to be set beween <strong>`</strong><br><br>
For a complete overview, see <a href="http://sourceforge.net/p/forge/documentation/markdown_syntax/">MarkDown Syntax</a>.<br>
To play arround, you can use this editor: <a href="http://markdown.pioul.fr/">http://markdown.pioul.fr/</a>.
<br><br>

<h3>Additional formating</h3>
The MarkDown Syntax might not support every formating you want.<br>
To work around that, you can use simple HTML key words.<br>
I.e. <b>Striked through text</b> simply needs a leading <b>&lt;del&gt;</b><br>
How ever keep in mind that other Notes vclients might not support this!
<br><br>

<h2>More</h2>
For more information, please have a look at <a href=http://apps.owncloud.com/content/show.php/Notes?content=155599>apps.owncloud.com</a>.<br>
Copyrght 2012 by <a href=http://www.ruinelli.ch>George Ruinelli</a>.
<br><br>

</div>
</div>
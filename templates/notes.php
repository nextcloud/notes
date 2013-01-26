<?php
  $show_Warnings = false;
//   $show_Warnings = true;
?>



<div id="controls">



<?php if($show_Warnings == false){ ?><div style="height: 0px; width: 0px; float:none; display:none;"> <?php } else{ ?><div><?php } ?>
<?php
  include_once dirname(__FILE__)."/../lib/markdown.php";

  // Check if we are a user
  OCP\User::checkLoggedIn();

  $root = "Notes";
  $extension = ".txt"; //default extention
  $arr_filelist = array(); //containing all files in the selected folder

  $user = OCP\USER::getUser();

  OC_Filesystem::init($root, '/' . $user . '/');
  OC_Filesystem::mkdir($root);



  //get subfolder (category)
  if(isset($_GET['category'])){
    $category = $_GET['category'];
  }
  elseif(isset($_POST['category'])){
    $category = $_POST['category'];
  }
  else{
    $category = "";
  }
  
  if($category == "General")$category = ""; //default category/folder



  $arr_filelist = OC_Files::getdirectorycontent($root . "/" . $category);

// print_r($arr_filelist);


  $arr_rootfilelist = array(); //contains all files
  //get file list
  $arr_rootfilelist = OC_Files::getdirectorycontent( $root);
?>
</div>


<div style="float:left;">
<form action="?app=notes" method="get" target="_self">
	<input type="hidden" name="app" value="notes">
	<select name="category" size="1" style="width: 150px" id="notes_cat_select">
<option value='General'><?php echo $l->t('General'); ?></option>
<?php
  foreach( $arr_rootfilelist as $i ) {
    if($i['type']=='dir') {
      if($i['name'] == $category){
	print "<option value='" . $i['name'] ."' selected='selected'>" . $i['name'] . "</option>\n";
      }
      else{
	print "<option value='" . $i['name'] ."'>" . $i['name'] . "</option>\n";
      }
    }
  }
?>
	</select>
</form>
</div>
<div style="float:left;">
<form action="?app=notes" method="get" target="_self">
	<input type="hidden" name="app" value="notes">
	<input type="hidden" name="category" value="<?php echo $category; ?>">
	<button type="submit" name="new" value="true" ><?php echo $l->t('Add Note'); ?></button>
</form>
</div>
<div style="float:left;">
<form action="?app=notes" method="get" target="_self">
	<input type="hidden" name="app" value="notes">
	<button type="submit" name="page" value="Edit Categories" ><?php echo $l->t('Edit Categories'); ?></button>
</form>
</div>
<div>
<form action="?app=notes" method="get" target="_self">
	<input type="hidden" name="app" value="notes">
	<button type="submit" name="page" value="Help" ><?php echo $l->t('Help'); ?></button>
</form>
</div>

</div>


<div id="leftcontent" class="leftcontent">





<?php if($show_Warnings == false){ ?><div style="height: 0px; width: 0px; float:none; display:none;"> <?php } else{ ?><div><?php } ?>
<?php

if($show_Warnings == true){
  print "<pre>";
  echo "POST: ";
  print_r($_POST);
  echo "GET: ";
  print_r($_GET);
  print "</pre>";
  print "--------------------<br>";
}


//   if(isset($_GET['note'])){ //GET data (from a link)
//   if(!isset($_POST['title']) and !isset($_POST['new']) ){ //GET data (from a link)
//   if(!isset($_POST['title'])){ //GET data (from a link)
  if(!isset($_POST['post'])){ //parameter "post" not set, handle as GET call
      $edit = isset($_GET['edit']) ? true : false;

      if(isset($_GET['new'])){ //new note
	  $file = "";
	  $title = "";
	  $content = "";
	  $edit = true; //overwrite
      }
      elseif(isset($_GET['note'])){ //a note is selected
	  $file = $_GET['note']; // note.txt
	  $arr = pathinfo($file);
	  $title = $arr['filename'];
	  $content = OC_Filesystem::file_get_contents($root . "/" . $category . '/' . $file);
      }
      else{ //no note selected, select first one
	  $thereisanote = false;
	  foreach( $arr_filelist as $i ) {
	      if($i['type']=='file') {
		  $thereisanote = true;
		  $file = $i['name'];
		  $arr = pathinfo($file);
		  $title = $arr['filename'];
		  $content = OC_Filesystem::file_get_contents($root . "/" . $category . '/' . $file);
		  break;
	      }
	  }
	  if($thereisanote == false){ //there is no file at all
	      $file = "";
	      $title = "";
	      $content = "";
	  }
      }
  }
  else{ //POST data (from a form)
      $edit = isset($_POST['edit']) ? true : false;
      $delete = isset($_POST['delete']) ? true : false; //"Delete" or ""
      $file = isset($_POST['file']) ? $_POST['file'] : ""; // note.txt
      $title = isset($_POST['title']) ? $_POST['title'] : "";
      $content = isset($_POST['content']) ? $_POST['content'] : "";
      $old_category = isset($_POST['old_category']) ? $_POST['old_category'] : "";


//       if(isset($_POST['new'])){ //new note
// 	  $file = "[unknown]" . $extension; // note.txt
// 	  $title = "";
// 	  $content = "";
//       }
//       else      
      if(isset($_POST['restore'])){ //restore note
	  $file = $_POST['file']; // note.txt
	  $old_category = $_POST['old_category']; 
	  $version = $_POST['version']; 
// 	  print "check if File is versioned: "  . $root . "/" . $old_category . '/' . $file . ", Version: " . $version;

// 	  if( OCA_Versions\Storage::isversioned($root . "/" . $old_category . '/' . $file ) ) {
        $count = 999; //show the newest revisions
        if( ($versions = OCA_Versions\Storage::getVersions( $root . "/" . $old_category . '/' . $file, $count)) ){
// 	      print "File is versioned: "  . $root . "/" . $old_category . '/' . $file . ", Version: " . $version;
	      $versions = new OCA_Versions\Storage();
	      $ret = $versions->rollback("/" . $root . "/" . $old_category . '/' . $file, (int)$version );
// 	      if($ret == true) print "restored";
// 	      else print "restoration failed";
	  }

	  $arr = pathinfo($file);
	  $title = $arr['filename'];
	  $content = OC_Filesystem::file_get_contents($root . "/" . $old_category . '/' . $file);
      }
      elseif($delete == true){ //remove file
	  if(OC_Filesystem::file_exists($root . "/" . $category . '/' . $file)){
	      OC_Filesystem::unlink($root . "/" . $category . '/' . $file);
	      $arr_filelist = OC_Files::getdirectorycontent( $root . "/" . $category); //reload file list
	      //file removed, select first one
	      $thereisanote = false;
	      foreach( $arr_filelist as $i ) {
		  if($i['type']=='file') {
		      $thereisanote = true;
		      $file = $i['name'];
		      $arr = pathinfo($file);
		      $title = $arr['filename'];
		      $content = OC_Filesystem::file_get_contents($root . "/" . $category . '/' . $file);
		      break;
		  }
	      }
	      if($thereisanote == false){ //there is no file at all
		  $file = "";
		  $title = "";
		  $content = "";
	      }
	  }
      }
      else{ //update or create
	  if($file != ""){ //update/rename file
	      $arr = pathinfo($file);
	      $oldtitle = $arr['filename'];
    // 	  if($title =="") $title = "[no name]";
	      if($oldtitle != $title or $old_category != $category){ //title or category changed, rename/move file	      
// 		  if(OC_Filesystem::file_exists($root . "/" . $category . '/' . $file) and $file != ""){
		      $newfile = $title . $extension;
// 		      print "rename $file => $newfile";
		      OC_Filesystem::rename($root . "/" . $old_category . '/' . $file, $root . "/" . $category . '/' . $newfile);
		      $file = $newfile;
// 		  }
	      }
// 	      else{
// 		  $file = "[unknown]" . $extension; // note.txt
// 	      }
	      OC_Filesystem::file_put_contents($root . "/" . $category . '/' . $file, $content);
	      $arr_filelist = OC_Files::getdirectorycontent( $root . "/" . $category); //reload file list	  
	  }
	  else{ //new file
	      $file = $title . $extension;
	      OC_Filesystem::file_put_contents($root . "/" . $category . '/' . $file, $content);
	      $arr_filelist = OC_Files::getdirectorycontent( $root . "/" . $category); //reload file list	  
	  }

      }
  }

?>
</div>






<ul id="entries">


<?php
//output file list
    $notes_available = false;
    foreach( $arr_filelist as $i ) {
	if($i['type']=='file'){
	    $notes_available = true;
	    break;
	}
    }

    if($notes_available == true){
	foreach( $arr_filelist as $i ) {
    // 	print_r($i);
	    $i['date'] = OCP\Util::formatDate($i['mtime'] );
	    if($i['type']=='file') {
		$fileinfo=pathinfo($i['name']);
		$i['basename']=$fileinfo['filename'];
		if (!empty($fileinfo['extension'])) {
			$i['extension']='.' . $fileinfo['extension'];
		}
		else {
			$i['extension']='';
		}

		$t = $i['basename'];

		if($i['basename'] == $title){ //select entry
		    $class = "active";
		}
		else{
		    $class = "";
		}

		echo "<li class=\"$class\"><a href=\"?app=notes&category=$category&note=" . $i['basename'] . $i['extension'] . "\"><b>" . $t . "</b><br><i>" . $i['date'] ."</i></a></li>\n";
	    }
	}
    }
    else{
	print "<b>";
	echo $l->t('No note available in this category');
	print "</b>";
    }
?>
</ul>
</div>






<div id="rightcontent" class="rightcontent">
<?php if($edit == false){ ?>
<div id="notes_preview">
<div style="float:left;">
<?php    
    if($title !="" or $content !=""){ //show data

	$content2 = str_replace(array("\r\n", "\n", "\r"), "\n", $content);
	$content2 = str_replace("\n", "  \n", $content2); //fixing unwanted behaviour of MarkDown Script (every new line should give a new line in preview)
	$content2 = str_replace("  \n  \n  \n", "<br><br>\n", $content2); //if there are more than 1 empty line, actually show an empty line

// print " -----------content2\n$content2\n---------------\n";
	$html = Markdown($content2);

	//shift headers
	$html = str_replace("<h2>", "<h3>", $html);
	$html = str_replace("</h2>", "</h3>", $html);
	$html = str_replace("<h1>", "<h2>", $html);
	$html = str_replace("</h1>", "</h2>", $html);

	$html = str_replace("<p>", "", $html); //we do not want those <p> tags
	$html = str_replace("</p>", "", $html);


	echo "<h1>$title</h1>";
?>
</div>
<div style="float:left;">
	<form action="?app=notes" method="get" target="_self">
	    <input type="hidden" name="app" value="notes">
	    <input type="hidden" name="note" value="<?php echo $file; ?>">
	    <input type="hidden" name="category" value="<?php echo $category; ?>">
	    <button type="submit" name="edit" value="true" ><?php echo $l->t('Edit'); ?></button>
	</form>
</div>
<div style="float:left;">
	<form action="?app=notes" method="post" target="_self">
	    <input type="hidden" name="app" value="notes">
	    <input type="hidden" name="post" value="true">
	    <input type="hidden" name="file" value="<?php echo $file; ?>">
	    <input type="hidden" name="category" value="<?php echo $category; ?>">
	    <input type="hidden" name="title" value="<?php echo $title; ?>">
	    <button type="submit" name="delete" value="true" ><?php echo $l->t('Delete'); ?></button>
	</form>
</div>
<br><br>
<div style="float:none;">
<?php
	echo "$html<br><br>";
    }
?>
</div>


<?php } 
else{ //edit
?>
<div>
<?php if((count($arr_filelist) > 0) or isset($_GET['new'])){ ?>
      <form name="notes_save" action="?app=notes" method="post" target="_self" onSubmit="return checkform()">
	<input type="hidden" name="post" value="true">
	<b><?php echo $l->t('Category'); ?>:</b>
	<select name="category" size="1">
	      <option value="General">General</option>
<?php
  foreach( $arr_rootfilelist as $i ) {
    if($i['type']=='dir') {
      if($i['name'] == $category){
	print "<option value='" . $i['name'] ."' selected='selected'>" . $i['name'] . "</option>\n";
      }
      else{
	print "<option value='" . $i['name'] ."'>" . $i['name'] . "</option>\n";
      }
    }
  }

//   $content = str_replace("\n\n", "\n", $content);
?>
	</select>
	  <p><b><?php echo $l->t('Title'); ?>:</b><br><input style="width:37em;" name="title" value="<?php echo $title; ?>" type="text" size="200" maxlength="200"></p>
	  <p><b><?php echo $l->t('Content'); ?>:</b></p>
	  <textarea id="markdown" style="width:50em;" name="content" cols="200" rows="15"><?php echo $content; ?></textarea><br>
<!--       <b><a href="http://michelf.ca/projects/php-markdown/concepts/" target="_blank">Hint: </b>You can use <b>MarkDown</b> to format your text.</a><br> -->
	  <input type="hidden" name="file" value="<?php echo $file; ?>">
	  <input type="hidden" name="old_category" value="<?php echo $category; ?>">
<?php if(!isset($_GET['new'])){ ?>
<!-- 	  <input type="checkbox" name="delete" value="true"><b><?php echo $l->t('Delete'); ?></b> -->
<?php } ?>
	    <button type="submit" name="save" value="true" ><?php echo $l->t('Save'); ?></button>

<?php
if(!OCP\App::isEnabled('files_versions')){
  echo "Version control is not enabled, please enable version in the apps menu!";
}
else{ //versions enabled
  $source = $root . "/" . $category . "/" . $file;
//   print "Source: $source";

// if( OCA_Versions\Storage::isversioned( $source ) ) {
$count = 999; //show the newest revisions
if( ($versions = OCA_Versions\Storage::getVersions( $source, $count)) ){

	$count=50; //show the newest revisions
	$versions = OCA_Versions\Storage::getVersions( $source, $count);
	$versionsFormatted = array();

	foreach ( $versions AS $version ) {
		$versionsFormatted[] = OCP\Util::formatDate( $version['version'] );
	}

	$versionsSorted = array_reverse( $versions );

?><br>
<b><?php echo $l->t('Restore content'); ?>:</b>
	<select name="version" size="1">
<?php
      foreach( $versionsSorted as $i ) {
	  print "<option value='" . $i['version'] . "'>" . OCP\Util::formatDate( $i['version'] ) . "</option>\n";
      }
?>
	  <input type="hidden" name="" value=""><!--Workaround-->
	  <button type="submit" name="restore" value="true" ><?php echo $l->t('Restore'); ?></button>
<?php
    } 
  }
} 
?>


      </form>
</div>

<?php } ?>

<?php
OCP\Util::addscript('notes', 'notes');


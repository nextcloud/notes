<?php
  $show_Warnings = false;
?>


<div id="controls">
<div style="float:left;">
<form action="?app=notes" method="get" target="_self">
	<input type="hidden" name="app" value="notes">
	<input type="submit" name="back" value="<?php echo $l->t('Back to Notes'); ?>">
</form>
</div>
<div>
<form action="?app=notes" method="get" target="_self">
	<input type="hidden" name="app" value="notes">
	<button type="submit" name="page" value="Help" ><?php echo $l->t('Help'); ?></button>
</form>
</div>
</div>



<div id="rightcontent2" class="rightcontent2">
<!-- <div id="leftcontent" class="leftcontent"> -->
<?php if($show_Warnings == false){ ?><div style="height: 0px; width: 0px; float:none; display:none;"> <?php } else{ ?><div><?php } ?>
<?php

//   print "<pre>";
//   echo "POST: ";
//   print_r($_POST);
//   echo "GET: ";
//   print_r($_GET);
//   print "</pre>";
//   print "--------------------<br>";


  // Check if we are a user
  OCP\User::checkLoggedIn();

  $root = "Notes";
  $arr_rootfilelist = array(); //contains all files
  $arr_filelist = array(); //containing all files in the selected folder

  $user = OCP\USER::getUser();

  OC_Filesystem::init($root, '/' . $user . '/');
  OC_Filesystem::mkdir($root);



  if(isset($_GET['new_category'])){ //new category
      $category = $_GET['new_category'];
      OC_Filesystem::mkdir($root . "/" . $category);
  }
  else{
      $old_category = isset($_GET['old_category']) ? $_GET['old_category'] : "";
      $category = isset($_GET['category']) ? $_GET['category'] : "";
      $delete = isset($_GET['delete']) ? $_GET['delete'] : "";

      if($old_category != $category){ //rename folder/category
	  OC_Filesystem::rename($root . "/" . $old_category, $root . "/" . $category);
      }

      elseif($delete == "Delete"){ //remove folder/category

	  //move each file in this folder
	  $arr_filelist = OC_Files::getdirectorycontent( $root . "/" . $category);

	  foreach( $arr_filelist as $i ) {
  // 	    print_r($i);
	      if($i['type']=='file') {
		    $file = $i['name'];
		    OC_Filesystem::rename($root . "/" . $category . '/' . $file, $root . '/' . $file);
	      }
	  }
	  OC_Filesystem::unlink($root . "/" . $category);
      }    
  }


  //get file list
  $arr_rootfilelist = OC_Files::getdirectorycontent( $root);

?>
</div>











<div id="rightcontent2" class="rightcontent2">
<!-- <div id="notes_list" class="rightcontent" style="margin-top: 5px"> -->
<b><?php echo $l->t('Categories'); ?>:</b><br>

<table>

<?php

  $thereisacategory=false;
  foreach( $arr_rootfilelist as $i ) {
    if($i['type']=='dir') {
	$thereisacategory = true;
?>	
	<tr><td>
	<form action="?app=notes" method="get" target="_self">
	<input style="width:200px;" name="category" value="<?php echo $i['name']; ?>" type="text" size="200" maxlength="200">
	<input type="hidden" name="app" value="notes">
	<input type="hidden" name="page" value="Edit Categories">
	  <input type="hidden" name="old_category" value="<?php echo $i['name']; ?>">
	  <input type="checkbox" name="delete" value="Delete"><b><?php echo $l->t('Delete'); ?></b>
	  <button type="submit" name="save" value="true" ><?php echo $l->t('Save'); ?></button>
</form>
	</td></tr>
<?php
    }
  }
?>
</table>


<?php
if($thereisacategory == false){
    echo $l->t('No categories available yet');
    echo ".<br>";
  }
?>

<br>

	<form action="?app=notes" method="get" target="_self">
	<b><?php echo $l->t('New category'); ?>:</b><br>
	<input style="width:200px;" name="new_category" value="" type="text" size="200" maxlength="200">
	<input type="hidden" name="app" value="notes">
	<input type="hidden" name="page" value="Edit Categories">
	<button type="submit" name="add" value="true" ><?php echo $l->t('Add'); ?></button>
</form>
<br>
<b><?php echo $l->t('Note'); ?>:</b><br>
<?php echo $l->t('If you delete a category, all containing notes get moved to the following category'); echo ": <b>"; echo $l->t('General'); ?></b>.
<br><br>


</div>



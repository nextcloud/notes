<?php
$l=OC_L10N::get('notes');

OCP\App::addNavigationEntry( array(
  'id' => 'notes_index',
  'order' => 11,
  'href' => OCP\Util::linkTo( 'notes', 'index.php' ),
  'icon' => OCP\Util::imagePath( 'notes', 'icon.png' ),
  'name' => $l->t('Notes'))
);
 
<?php
$l=OC_L10N::get('notes');

OC::$CLASSPATH['OCA\Notes\Notes'] = 'notes/lib/notes.php';
OC::$CLASSPATH['OCA\Notes\Categories'] = 'notes/lib/categories.php';

OCP\App::addNavigationEntry( array(
  'id' => 'notes_index',
  'order' => 11,
  'href' => OCP\Util::linkTo( 'notes', 'index.php' ),
  'icon' => OCP\Util::imagePath( 'notes', 'notes.svg' ),
  'name' => $l->t('Notes'))
);

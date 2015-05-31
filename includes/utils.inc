<?php 
/**
 * @file
 * file description
 */

/**
 * Provides progress bar.
 */
function _drush_print_progress($ratio) {
  $percentage = floor($ratio * 100) . '%';
  $columns = drush_get_context('DRUSH_COLUMNS', 80);
  $progress_columns = $columns - 8;
  $arrow = ($ratio < 1) ? '>' : '=';
  $line_ending = ($ratio < 1) ? "\r" : "\n";
  $current_length = floor($ratio * $progress_columns);
  $progress_string = str_pad('', $current_length, '=');

  $output  = '[';
  $output .= $progress_string . $arrow;
  $output .= str_pad('', $progress_columns - $current_length);
  $output .= ']';
  $output .= str_pad('', 5 - strlen($percentage)) . $percentage;
  $output .= $line_ending;

  print $output;
}

/**
 * Deletes unused files from the public://
 */
function cimport_clean_unused_files() {
  $public = drupal_realpath('public://');
  $files = scandir($public);
  $total = count($files);
  $orphans = 0;
  $cur_file = 0;
  foreach($files as $key => $name) {
    $cur_file++;
    if (strlen($name) > 3 && strpos($name, '.jpg')) {
      $fid = db_select('file_managed', 'f')
        ->fields('f', array('fid'))
        ->condition('uri', '%' . $name . '%', 'LIKE')
        ->execute()
        ->fetchField();
      if (!$fid) {
        unlink($public . '/' . $name);
        $orphans++;
      }
    }
    _drush_print_progress($cur_file / $total);
  }
  drush_log('Found ' . $total . 'total items in public://', 'ok');
  drush_log('Found ' . $orphans . ' unused items in public://', 'ok');
}
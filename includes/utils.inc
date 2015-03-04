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

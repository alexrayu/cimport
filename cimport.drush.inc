<?php

/**
 * Implements hook_drush_command().
 */
function cimport_drush_command() {
  $items = array();
  $items['cistats'] = array(
    'description' => dt('Cimport stats.'),
    'arguments'   => array(
      'verbal'    => dt('Whether to have output verbal or not.'),
    ),
    'aliases' => array('cis'),
  );
  $items['cirun'] = array(
    'description' => dt('Cimport run.'),
    'arguments'   => array(),
    'aliases' => array('cir'),
  );
  $items['ciprods'] = array(
    'description' => dt('Cimport re-import products.'),
    'arguments'   => array(),
    'aliases' => array('cip'),
  );
  
  return $items;
}

/**
 * Callback function for drush my-command.
 */
function drush_cimport_cistats($verbal = NULL) {
  $res = cimport_stats();
  drush_log('Available ' . $res['products'] . ' products and ' . $res['nodes'] . ' display nodes.', 'ok');
  $count_missing = count($res['media']['all']) == count($res['media']['missing']) ? 'all' : count($res['media']['missing']);
  drush_log('Listed ' . count($res['media']['all']) . ' files, ' . $count_missing . ' of them missing.', 'ok');
  if ($verbal && count($res['media']['missing'])) {
    print('Missing files: ' . "\n");
    $counter = 0;
    foreach ($res['media']['missing'] as $path) {
      $counter++;
      $path_arr = explode('/', $path);
      print($counter . '. ' . array_pop($path_arr) . "\n");
    }
  }
}

/**
 * Callback function for drush my-command.
 */
function drush_cimport_cirun() {
  drush_log('Running import', 'ok');
  $res = cimport_run();
  drush_log('Imported ' . $res['products'] . ' products, created ' . $res['nodes'] . ' display nodes.', 'ok');
}

/**
 * Callback function for drush my-command.
 */
function drush_cimport_ciprods() {
  drush_log('Running re-import of products', 'ok');
  $res = cimport_run(array('products_only' => TRUE));
  drush_log('Re-imported ' . $res['products'] . ' products.', 'ok');
}
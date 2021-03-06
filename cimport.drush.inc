<?php

/**
 * Implements hook_drush_command().
 */
function cimport_drush_command() {
  $items = array();
  $items['cistats'] = array(
    'description' => dt('Cimport stats.'),
    'aliases' => array('cis'),
  );
  $items['cirun'] = array(
    'description' => dt('Cimport run.'),
    'aliases' => array('cir'),
  );
  $items['ciprods'] = array(
    'description' => dt('Cimport re-import products.'),
    'aliases' => array('cip'),
  );
  $items['fclean'] = array(
    'description' => dt('Files Clean.'),
  );
  $items['cidel'] = array(
    'description' => dt('Cimport delete products.'),
  );

  return $items;
}

/**
 * Callback function for drush cistats.
 */
function drush_cimport_cistats() {
  $res = cimport_stats();
  $new_products = $res['products'] - $res['upd_products'];
  $new_nodes = $res['nodes'] - $res['upd_nodes'];
  drush_log('Nodes: ' . $new_nodes . ' new, ' . $res['upd_nodes'] . ' updates.', 'ok');
  drush_log('Products: ' . $new_products . ' new, ' . $res['upd_products'] . ' updates.', 'ok');
  $count_missing = count($res['media']['all']) == count($res['media']['missing']) ? 'all' : count($res['media']['missing']);
  drush_log('Listed ' . count($res['media']['all']) . ' files, ' . $count_missing . ' of them missing.', 'ok');

  // Save missing:
  if (count($res['media']['missing'])) {
    $file_name = drupal_get_path('module', 'cimport') . '/source/missing_files.csv';
    $fp = fopen($file_name, 'w');
    $counter = 0;
    foreach ($res['media']['missing'] as $path) {
      $counter++;
      $path_arr = explode('/', $path);
      $item = array_pop($path_arr);
      fputcsv($fp, (array) $item);
    }
    print('Missing files list saved to : ' . $file_name . "\n");
    fclose($fp);
  }
}

/**
 * Callback function for drush cirun.
 */
function drush_cimport_cirun() {
  drush_log('Running import', 'ok');
  $res = cimport_run();
  drush_log('Imported ' . $res['products'] . ' products, created ' . $res['nodes'] . ' display nodes.', 'ok');
}

/**
 * Callback function for drush ciprods.
 */
function drush_cimport_ciprods() {
  drush_log('Running re-import of products', 'ok');
  $res = cimport_run(array('products_only' => TRUE));
  drush_log('Re-imported ' . $res['products'] . ' products.', 'ok');
}

/**
 * Clean unused files.
 */
function drush_cimport_fclean() {
  drush_log('Running cleanup', 'ok');
  $res = cimport_clean_unused_files();
  drush_log('Unused files cleaned', 'ok');
}

/**
 * Callback function for drush cidel.
 */
function drush_cimport_cidel() {
  $res = cimport_del();
  if ($res['prods']) {
    drush_log('There are ' . $res['prods'] . ' entries that match SKUs and will be deleted.', 'ok');
    if (drush_confirm('Are you sure you want to proceed?')) {
      $res = cimport_del(TRUE);
      drush_log('Deleted ' . $res['prods'] . ' products and ' . $res['nodes'] . ' nodes.', 'ok');
    }
    else {
      drush_log('Delete aborted.', 'ok');
      return;
    }
  }
  else {
    drush_log('No matching SKUs. Nothing to delete.', 'warning');
  }
}

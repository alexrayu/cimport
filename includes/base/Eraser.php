<?php

/**
 * @file
 *  Handles erasing of products from the CSV file. Generic class.
 */

Abstract class Eraser {

  // Data.
  protected $data = array();
  protected $products = array();
  protected $nodes = array();
  protected $del_nodes_count;
  protected $product_field;

  // Source file name.
  protected $csv_file;

  // Amount of valid products,
  protected $count;

  function __construct($config, $csv_file = NULL) {

    // CSV File name.
    $this->config = $config;
    $this->config['separator'] = !empty($config['separator']) ? $config['separator'] : ',';
    $this->config['csv_file'] = !empty($csv_file) ? $csv_file : drupal_get_path('module', 'cimport') . '/source/delete.csv';
    $this->product_field = !empty($config['dest']['product_field']) ? $config['dest']['product_field'] : 'field_product';

    $this->load();
  }

  /**
   * Imports csv.
   */
  protected function load() {
    if (($handle = fopen($this->config['csv_file'], "r"))) {
      while (($data = fgetcsv($handle, 0, $this->config['separator']))) {
        $this->data[] = $data;
      }
      $this->findExisting($this->data);
    }
  }

  /**
   * Performs deleting.
   */
  public function run() {
    $products = $this->products;
    $nodes = $this->nodes;
    $deleted_nodes = 0;
    if (!count($products)) {
      return;
    }
    // Kill nodes.
    foreach ($nodes as $nid) {
      $node = node_load($nid);
      if (!empty($node->{$this->product_field}['und'])) {
        foreach($node->{$this->product_field}['und'] as $key => $item) {
          if (in_array($item['product_id'], $products)) {
            unset($node->{$this->product_field}['und'][$key]);
          }
        }
      }
      // Kill products.
      foreach ($products as $pid) {
        $res = commerce_product_delete($pid);
      }
      if (empty($node->{$this->product_field}['und'][0])) {
        node_delete($nid);
        $deleted_nodes++;
      }
    }
    $this->del_nodes_count = $deleted_nodes;
  }

  /**
   * Getter for the $count.
   */
  public function count() {
    return $this->count;
  }

  /**
   * Getter for the $del_node_count.
   */
  public function delNodesCount() {
    return !empty($this->del_nodes_count) ? $this->del_nodes_count : 0;
  }

  /**
   * Find existing products to update.
   */
  function findExisting(&$data) {
    foreach ($data as $key => $sku) {
      $product = commerce_product_load_by_sku($sku);
      if (!empty($product->product_id)) {
        $this->products[] = $product->product_id;
        $this->nodes[] = cimport_get_nid_from_pid($product->product_id);
      }
      else {
        unset($data['$key']);
      }
    }
    $this->count = count($this->products);
  }

}
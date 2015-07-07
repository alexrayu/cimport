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
  protected $del_prods_count;
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
        if (is_array($data)) {
          $this->data[] = reset($data);
        }
        else {
          $this->data[] = $data;
        }
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
    $deleted_products = 0;
    if (!count($products)) {
      return;
    }

    // Disable products.
    foreach ($products as $pid) {
      $product = commerce_product_load($pid);
      $product->status = 0;
      commerce_product_save($product);
      $deleted_products++;
    }

    // Disable nodes.
    if (!empty($nodes)) {
      foreach ($nodes as $nid) {
        $node = node_load($nid);
        $node->status = 0;
        node_save($node);
        $deleted_nodes++;
      }
    }

    $this->del_prods_count = $deleted_products;
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
   * Getter for the $del_prods_count.
   */
  public function delProdsCount() {
    return !empty($this->del_prods_count) ? $this->del_prods_count : 0;
  }

  /**
   * Find existing products to update.
   */
  function findExisting(&$data) {
    foreach ($data as $key => $pn) {
      $pid = cimport_find_pid_by_pn($pn);
      $product = commerce_product_load($pid);
      if (!empty($product->product_id) && $product->status) {
        $this->products[] = $product->product_id;
        $nid = cimport_get_nid_from_pid($product->product_id);
        if ($nid) {
          $this->nodes[] = $nid;
        }
      }
      else {
        unset($data['$key']);
      }
    }
    $this->count = count($this->products);
  }

}
<?php

/**
 * @file
 *  Handles assignation to the product display node.
 */
class Display extends Destination {

  // Product objects.
  protected $products;

  // Config.
  protected $config;

  // Pack of entries.
  protected $pack;

  // Name of content type.
  protected $content_type;

  // Name of product reference field..
  protected $product_field;

  // Node object.
  protected $node;

  function __construct($products, $config, $pack) {
    if (!count($pack)) {
      return;
    }

    // The pack.
    $this->pack = $pack;

    $this->products = $products;
    $this->config = $config;
    $this->content_type = !empty($config['dest']['content_type']) ? $config['dest']['content_type'] : 'product_display';
    $this->product_field = !empty($config['dest']['product_field']) ? $config['dest']['product_field'] : 'field_product';

    $this->import();
  }

  /**
   * Import data into display.
   */
  protected function import() {

    // No products, no display.
    if (!count($this->products)) {
      return;
    }

    $product = reset($this->products);
    if (!empty($this->pack['display'])) {
      $node = node_load($this->pack['display']);
      $node->status = 1;
      $node->title = $product->title;
    }
    else {
      $node = $this->newDisplay($product);
    }

    // Added terms from all entries in a pack.
    $tids = array();
    foreach ($this->pack['items'] as $entry) {
      $tids[] = $this->termPath2Tid($entry['term-l1'] . '/' . $entry['term-l2'], 'product_category');
    }
    $tids = array_unique($tids);
    // Reset tids (used in update).
    $node->field_product_category['und'] = array();
    // Import terms.
    foreach ($tids as $tid) {
      if (!empty($tid)) {
        $node->field_product_category['und'][] = array(
          'tid' => $tid,
        );
      }
    }

    // Add products.
    foreach ($this->products as $product) {
      $this->addProduct($node, $product);
    }

    // Description
    $node->body['en'][0]['value'] = $entry['descr'];

    node_save($node);

    $this->node = $node;
  }

  /**
   * Adds product to node.
   */
  protected function addProduct(&$node, &$product) {
    // Check if already there.
    if (!empty($node->{$this->product_field}['und'])) {
      foreach ($node->{$this->product_field}['und'] as $item) {
        if ($item['product_id'] == $product->product_id) {
          return;
        }
      }
    }
    $node->{$this->product_field}['und'][] = array(
      'product_id' => $product->product_id,
    );
  }

  /**
   * Generate an empty product display node.
   */
  protected function newDisplay($product) {
    global $language;

    $node = new stdClass();
    $node->title = $product->title;
    $node->type = $this->content_type;
    node_object_prepare($node);
    $node->language = $language->language;
    $node->uid = 1;
    $node->status = 1;
    $node->promote = 0;
    $node->comment = 0;

    return $node;
  }


}

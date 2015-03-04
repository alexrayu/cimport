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

  // Node object.
  protected $node;

  function __construct($products, $config, $pack) {
    if (!count($pack)) {
      return;
    }

    // The pack.
    $this->pack = $pack['items'];

    $this->products = $products;
    $this->config = $config;
    $this->content_type = !empty($config['content_type']) ? $config['content_type'] : 'product_display';

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
    $node = $this->newDisplay($product);

    // Added terms from all entries in a pack.
    $tids = array();
    foreach ($this->pack as $entry) {
      $tids[] = $this->termPath2Tid($entry['term-l1'] . '/' . $entry['term-l2'], 'event_rentals');
    }
    $tids = array_unique($tids);
    foreach ($tids as $tid) {
      if (!empty($tid)) {
        $node->field_product_category['und'][] = array(
          'tid' => $tid,
        );
      }
    }

    // Add products.
    foreach ($this->products as $product) {
      $node->field_product_reference['und'][] = array(
        'product_id' => $product->product_id,
      );
    }

    node_save($node);
    $this->node = $node;
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
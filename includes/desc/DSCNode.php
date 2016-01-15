<?php

/**
 * @file
 *  HRER Implementation of a product display.
 */

class DSCNode extends Node {

  /**
   * Generate an empty product display node.
   */
  protected function newNode($product) {
    global $language;

    $node = new stdClass();
    $node->title = $product->true_title;
    $node->type = $this->content_type;
    node_object_prepare($node);
    $node->language = $language->language;
    $node->uid = 1;
    $node->status = 1;
    $node->promote = 0;
    $node->comment = 0;

    return $node;
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
      $node = $this->newNode($product);
    }

    // Added terms from all entries in a pack.
    $tids = array();
    foreach ($this->pack['items'] as $entry) {
      $tids[] = $this->termPath2Tid($entry['term-l1'] . '/' . $entry['term-l2'], 'event_rentals');
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

}

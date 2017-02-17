<?php

/**
 * @file
 *  HRER Implementation of a product display.
 */

class DSCNode extends Node {

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

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
    foreach ($this->pack as $row) {
      if (!empty($row['_nid'])) {
        $nid = $row['_nid'];
        break;
      }
    }
    if (!empty($nid)) {
      $node = node_load($nid);
      $node->status = 1;
      $node->title = $product->title;
    }
    else {
      $node = $this->newDisplay($product);
    }

    // Added terms from all entries in a pack.
    $tids = array();
    foreach ($this->pack as $entry) {
      $tids[] = $this->termPath2Tid($entry['term-l1'] . '/' . $entry['term-l2'] . '/' . $entry['term-l3'], 'category');
    }
    $tids = array_unique($tids);
    // Reset tids (used in update).
    $node->field_category[LANGUAGE_NONE] = array();
    // Import terms.
    foreach ($tids as $tid) {
      if (!empty($tid)) {
        $node->field_category[LANGUAGE_NONE][] = array(
          'tid' => $tid,
        );
      }
    }

    // Added collection terms from all entries in a pack.
    $tids = array();
    foreach ($this->pack as $entry) {
      $tids[] = $this->termPath2Tid($entry['collection'], 'collection');
    }
    $tids = array_unique($tids);
    // Reset tids (used in update).
    $node->field_collection[LANGUAGE_NONE] = array();
    // Import terms.
    foreach ($tids as $tid) {
      if (!empty($tid)) {
        $node->field_collection[LANGUAGE_NONE][] = array(
          'tid' => $tid,
        );
      }
    }

    // Add products.
    $this->addProducts($node, $this->products);

    node_save($node);
    $this->node = $node;
  }

}

<?php

/**
 * @file
 *  Handles assignation to the product entity.
 */

class Product extends Destination {

  // Product configuration.
  protected $config;

  // Product object.
  protected $product;

  // The entry.
  protected $entry;

  function __construct($entry, $config) {

    // The entry.
    $this->entry = $entry;

    // Product type. Defaults to 'product'.
    $this->config['type'] = !empty($config['type']) ? $config['type'] : 'product';

    // Term mapping, defaults to 'category'.
    $this->config['term_map'] = !empty($config['term_map']) ? $config['term_map'] : 'category';

    $this->create();
    $this->fill();
    $this->save();
  }

  /**
   * If no product exists, create a new product template.
   */
  protected function create() {
    $this->product = commerce_product_load_by_sku($this->entry['sku']);
    if (empty($this->product->product_id)) {
      $this->product = commerce_product_new($this->config['type']);
      $this->product->uid = 1;
    }
  }

  /**
   * Fill product data.
   */
  protected function fill() {
    $product = &$this->product;

    // Required
    $product->sku = $this->entry['sku'];
    $product->title = $this->entry['title'];

    // Price
    $product->commerce_price['und'][0]['amount'] = floatval($this->entry['price']) * 100;
    $product->commerce_price['und'][0]['currency_code'] = !empty($this->entry['currency']) ? $this->entry['currency'] : 'USD';

    // Files
    $fids_array = $this->filePath2Fid($this->entry['files']);
    if (!empty($fids_array)) {
      $product->field_product_image['und'] = $fids_array;
    }

    // Size Term
    $tid = $this->termPath2Tid($this->entry['size'], 'size');
    if (!empty($tid)) {
      $product->field_size['und'][0]['tid'] = $tid;
    }

    // Color Term
    $tid = $this->termPath2Tid($this->entry['color'], 'color', 'field_hex_value');
    if (!empty($tid)) {
      $product->field_product_color['und'][0]['tid'] = $tid;
    }

    // Physical dimensions
    $product->field_physical_dimensions['und'][0] = array(
      'height' => !empty($this->entry['height']) ? $this->entry['height'] : 0,
      'length' => !empty($this->entry['length']) ? $this->entry['length'] : 0,
      'width' => !empty($this->entry['width']) ? $this->entry['width'] : 0,
      'unit' => 'in',
    );

    // Description
    $product->field_description['und'][0]['value'] = $this->entry['descr'];
    $product->field_description['und'][0]['format'] = 'full_html';
  }

  /**
   * Save product.
   */
  protected function save() {
    commerce_product_save($this->product);
  }

  /**
   * Return product id.
   */
  function getProduct() {
    return $this->product;
  }

}
<?php

/**
 * @file
 *  HRER Implementation of product.
 */

class DSCProduct extends Product {

  /**
   * Fill product data.
   */
  protected function fill() {
    $product = &$this->product;

    // Required
    $product->sku = $this->entry['sku'];
    $product->title = !empty($this->entry['color']) ? $this->entry['title'] . ' - ' . $this->entry['color'] : $this->entry['title'];
    $product->true_title = $this->entry['title'];

    // Price
    $product->commerce_price['und'][0]['amount'] = $this->entry['price1'] * 100;
    $product->commerce_price['und'][0]['currency_code'] = !empty($this->entry['currency']) ? $this->entry['currency'] : 'USD';

    // Files
    $fids_array = $this->filePath2Fid($this->entry['files']);
    if (!empty($fids_array)) {
      $product->field_product_image['und'] = $fids_array;
    }

    // Color Term
    $tid = $this->termPath2Tid($this->entry['color'], 'color', 'field_hex_value');
    if (!empty($tid)) {
      $product->field_product_color['und'][0]['tid'] = $tid;
    }

    // Texture Term
    $tid = $this->termPath2Tid($this->entry['texture'], 'texture');
    if (!empty($tid)) {
      $product->field_product_texture['und'][0]['tid'] = $tid;
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

}
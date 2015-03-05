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

    // File
    $fid = $this->filePath2Fid($this->entry['file']);
    if (!empty($fid)) {
      $product->field_product_image['und'][0]['fid'] = $fid;
    }

    // Color Term
    $tid = $this->termPath2Tid($this->entry['color'], 'color');
    if (!empty($tid)) {
      $product->field_product_color['und'][0]['tid'] = $tid;
    }

    // Texture Term
    $tid = $this->termPath2Tid($this->entry['texture'], 'texture');
    if (!empty($tid)) {
      $product->field_product_texture['und'][0]['tid'] = $tid;
    }

    // Print Style Term
    $tid = $this->termPath2Tid($this->entry['print_style'], 'print_style');
    if (!empty($tid)) {
      $product->field_product_print_style['und'][0]['tid'] = $tid;
    }

    // Size Term
    $tid = $this->termPath2Tid($this->entry['size'], 'size');
    if (!empty($tid)) {
      $product->field_product_size['und'][0]['tid'] = $tid;
    }

    // Description
    $product->field_description['und'][0]['value'] = $this->entry['descr'];
    $product->field_description['und'][0]['format'] = 'full_html';
  }

}
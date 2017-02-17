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

    // Required.
    $product->sku = $this->entry['sku'];
    $product->title = !empty($this->entry['color']) ? $this->entry['title'] . ' - ' . $this->entry['color'] : $this->entry['title'];
    $product->true_title = $this->entry['title'];

    // Files.
    $fids_array = $this->filePath2Fid($this->entry['files']);
    if (!empty($fids_array)) {
      $product->field_product_image[LANGUAGE_NONE] = $fids_array;
    }

    // Price.
    $product->field_product_cost[LANGUAGE_NONE][0]['amount'] = preg_replace("/[^0-9\.\,]/", NULL, $this->entry['cost'])  * 100;
    $product->field_product_cost[LANGUAGE_NONE][0]['currency_code'] = 'USD';

    // Cost.
    $product->commerce_price[LANGUAGE_NONE][0]['amount'] = preg_replace("/[^0-9\.\,]/", NULL, $this->entry['price'])  * 100;
    $product->commerce_price[LANGUAGE_NONE][0]['currency_code'] = 'USD';

    // Physical dimensions.
    $product->field_product_dimensions[LANGUAGE_NONE][0] = array(
      'height' => !empty($this->entry['height']) ? $this->entry['height'] : 0,
      'length' => !empty($this->entry['length']) ? $this->entry['length'] : 0,
      'width' => !empty($this->entry['width']) ? $this->entry['width'] : 0,
      'unit' => 'in',
    );

    // Weight.
    $product->field_product_weight[LANGUAGE_NONE][0]['weight'] = $this->entry['weight'];
    $product->field_product_weight[LANGUAGE_NONE][0]['unit'] = 'lb';

    // Frame.
    $product->field_product_frame[LANGUAGE_NONE][0]['value'] = $this->entry['emb_frame'];

    // UPC.
    $product->field_product_upc[LANGUAGE_NONE][0]['value'] = $this->entry['upc'];

    // Embroidery modes.
    if (!empty($this->entry['emb_initial'])) {
      $emb_mode = 'initial';
    }
    if (!empty($this->entry['emb_initials'])) {
      $emb_mode = 'initials';
    }
    if (!empty($this->entry['emb_text'])) {
      $emb_mode = 'text';
    }
    if (!empty($emb_mode)) {
      $product->field_product_embroidery_modes[LANGUAGE_NONE][0]['value'] = $emb_mode;
    }

    // Embroidery text lines.
    $product->field_product_embroidery_lines[LANGUAGE_NONE][0]['value'] = !empty($this->entry['emb_lines']) ? $this->entry['emb_lines'] : 0;

    // Embroidery height.
    $product->field_product_embroidery_height[LANGUAGE_NONE][0]['value'] = !empty($this->entry['emb_height']) ? $this->entry['emb_height'] : 0;

    // Embroidery width.
    $product->field_product_embroidery_width[LANGUAGE_NONE][0]['value'] = !empty($this->entry['emb_width']) ? $this->entry['emb_width'] : 0;

    // Embroidery circle size.
    $product->field_product_embroidery_circle[LANGUAGE_NONE][0]['value'] = !empty($this->entry['emb_circle']) ? $this->entry['emb_circle'] : 0;

    // Description.
    $product->field_product_description[LANGUAGE_NONE][0]['value'] = $this->entry['description'];
    $product->field_product_description[LANGUAGE_NONE][0]['format'] = 'full_html';
  }

}

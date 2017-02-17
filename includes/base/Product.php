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

  function __construct(&$entry, $config) {

    // The entry.
    $this->entry = &$entry;

    // Copy over the config.
    $this->config = $config;

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
    $product = commerce_product_load_by_sku($this->entry['sku']);
    $pid = $product->product_id;

    if ($pid) {
      $this->product = commerce_product_load($pid);
      $this->product->status = 1;
      $this->entry['_pid'] = $pid;
      $nid = cimport_get_nid_from_pid($pid, $this->config['product_field']);
      if (!empty($nid)) {
        $this->entry['_nid'] = $nid;
      }
    }
    else {
      $this->product = commerce_product_new($this->config['type']);
      $this->product->uid = 1;
    }
  }

  /**
   * Fill product data.
   */
  protected function fill() {
    $product = $this->product;

    // Required.
    $product->sku = $this->entry['sku'];
    $product->title = $this->entry['title'];

    // Files.
    $fids_array = $this->filePath2Fid($this->entry['files']);
    if (!empty($fids_array)) {
      $product->field_product_image[LANGUAGE_NONE] = $fids_array;
    }

    // Price.
    $product->commerce_price[LANGUAGE_NONE][0]['amount'] = preg_replace("/[^0-9\.\,]/", NULL, $this->entry['price']) * 100;
    $product->commerce_price[LANGUAGE_NONE][0]['currency_code'] = !empty($this->entry['currency']) ? $this->entry['currency'] : 'USD';

    // Color Term.
    $tid = $this->termPath2Tid($this->entry['color'], 'color', 'field_hex_value');
    if (!empty($tid)) {
      $product->field_product_color[LANGUAGE_NONE][0]['tid'] = $tid;
    }

    // Physical dimensions.
    $product->field_physical_dimensions[LANGUAGE_NONE][0] = array(
      'height' => !empty($this->entry['height']) ? $this->entry['height'] : 0,
      'length' => !empty($this->entry['length']) ? $this->entry['length'] : 0,
      'width' => !empty($this->entry['width']) ? $this->entry['width'] : 0,
      'unit' => 'in',
    );

    // Description.
    $product->field_description[LANGUAGE_NONE][0]['value'] = $this->entry['descr'];
    $product->field_description[LANGUAGE_NONE][0]['format'] = 'full_html';

    $this->product = $product;
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

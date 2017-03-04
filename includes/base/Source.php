<?php

/**
 * @file
 *  Handles import from the CSV file. Generic class.
 */
abstract class Source {

  // Raw data.
  protected $raw_data = array();

  // Formatted data.
  protected $data = array();

  // Media paths.
  protected $media = array();

  // Config.
  protected $config = array();

  // Amount of valid products,
  protected $count = array(
    'products' => 0,
    'nodes' => 0,
    'upd_products' => 0,
    'upd_nodes' => 0,
  );

  function __construct($config) {

    // Files folder.
    $this->config['files_path'] = drupal_get_path('module', 'cimport') . '/source/files';

    // Field to group by (commerce multi products).
    $this->config['group_field'] = !empty($config['group_field']) ? $config['group_field'] : NULL;

    $this->load();
    $this->prepare();
  }


  /**
   * Imports csv.
   */
  protected function load() {
    $data = [];
    $header = [];
    $group_key = NULL;
    if ($handle = fopen(drupal_get_path('module', 'cimport') . '/source/source.csv', 'r')) {
      $header = fgetcsv($handle);

      // Process row.
      while (($row = fgetcsv($handle)) !== FALSE) {
        $joined = array_combine($header, $row);

        // Check if groups exist.
        if (!empty($this->config['group_field'])) {
          $group_key = $joined[$this->config['group_field']];
        }

        // Check if SKU exists.
        if (empty($joined['sku'])) {
          $joined['sku'] = $this->genSku($group_key);
        }

        // Force generate group key if empty.
        if (empty($group_key)) {
          $group_key = $joined['sku'];
        }

        // Group fields.
        $data[$group_key][] = $joined;

      }
      fclose($handle);
    }

    $this->map = $header;
    $this->raw_data = $data;
  }

  /**
   * Prepare csv, format csv.
   */
  protected function prepare() {
    $data = $this->preprocess($this->raw_data);

    // Unset the raw data to save memory.
    $this->raw_data = array();

    $data = $this->filesToLower($data);
    $data = $this->filterInvalid($data);

    // Count.
    $this->count['nodes'] = count($data);
    foreach ($data as $group => $variants) {
      $this->count['products'] += count($variants);
    }

    $data = $this->processFilePaths($data);
    $data = $this->findExisting($data);

    $this->data = $data;
  }

  /**
   * Find existing products to update.
   */
  private function findExisting($data) {
    $prod_upd = 0;
    $node_upd = 0;
    foreach ($data as $group => $variants) {

      $child_updated = FALSE;
      foreach ($variants as $row) {
        $product = commerce_product_load_by_sku($row['sku']);
        $pid = $product->product_id;
        if ($pid) {
          $prod_upd++;
        }
      }
      if ($child_updated) {
        $node_upd++;
      }
    }

    // Set global count.
    $this->count['upd_products'] = $prod_upd;
    $this->count['upd_nodes'] = $node_upd;

    return $data;
  }

  /**
   * Convert all file names to lower.
   */
  protected function filesToLower($data) {

    // Rename physical files.
    $files = scandir($this->config['files_path']);
    foreach ($files as $key => $name) {
      if ($name == '.' || $name == '..') {
        continue;
      }
      $oldName = $this->config['files_path'] . '/' . $name;
      $newName = $this->config['files_path'] . '/' . $this->cleanFilename($name);
      if ($oldName != $newName) {
        rename($oldName, $newName);
      }
    }

    // Rename file names in record.
    foreach ($data as &$item) {
      if (empty($item['files'])) {
        continue;
      }
      foreach ($item['files'] as $key => $file) {
        $item['files'][$key] = $this->cleanFilename($file);
      }
    }

    return $data;
  }

  /**
   * Some unification of file names.
   *
   * @param string $filename
   *    Initial file name.
   *
   * @return string
   *    Cleaned up file name.
   */
  protected function cleanFilename($filename) {
    $replace_patterns = [' ', '_'];
    $filename = strtolower(trim($filename));
    $filename = str_replace($replace_patterns, '-', $filename);

    return $filename;
  }

  /**
   * Filter invalid data.
   */
  protected function filterInvalid($data) {
    foreach ($data as $group => $variants) {
      foreach ($variants as $variant_key => $row) {

        // Handle Title.
        if (empty($row['title'])) {
          unset($data[$group][$variant_key]);
        }

      }
    }

    return $data;
  }

  /**
   * Generates a new SKU for a product.
   *
   * @param string $group
   *    Group the product belongs to.
   *
   * @return string
   *    SKU.
   */
  protected function genSku($group = NULL) {
    $max_id = db_query('SELECT MAX(product_id) FROM {commerce_product}')->fetchField();
    if (empty($group)) {
      $sku = $max_id + 1;
    }
    else {
      $sku = $group . '-' . ($max_id + 1);
    }

    return $sku;
  }

  /**
   * Process file paths.
   */
  protected function processFilePaths($data) {
    foreach ($data as $group => &$variants) {
      foreach ($variants as $variant_key => &$row) {
        if (empty($row['files'])) {
          continue;
        }
        foreach ($row['files'] as $key => $file) {
          $file = $this->cleanFilename($file);
          $ini_path = explode('/', $file);
          $row['files'][$key] = $this->config['files_path'] . '/' . array_pop($ini_path);

          // Add file path to media variable.
          $this->media['all'][] = $row['files'][$key];
          if (!file_exists($row['files'][$key])) {
            $this->media['missing'][] = $row['files'][$key];
            unset($row['files'][$key]);
          }
        }
      }
    }

    if (!empty($this->media['missing'])) {
      $this->media['missing'] = array_unique($this->media['missing']);
      sort($this->media['missing']);
    }
    if (!empty($this->media['all'])) {
      $this->media['all'] = array_unique($this->media['all']);
      sort($this->media['all']);
    }

    return $data;
  }

  /**
   * Map data.
   */
  protected function preprocess($data) {
    foreach ($data as $group => $variants) {
      foreach ($variants as $variant_key => $row) {
        foreach ($row as $key => $value) {

          // Files.
          if ($key == 'files') {
            $data[$group][$variant_key][$key] = explode(',', $value);
          }

        }
      }
    }

    return $data;
  }

  /**
   * Returns data.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Count all valid products.
   */
  public function count() {
    return $this->count;
  }

  /**
   * Count all valid products.
   */
  public function getMedia() {
    return $this->media;
  }

}

<?php

/**
 * @file
 * Contains the Source abstract class.
 */

/**
 * Class Source.
 */
abstract class Source {

  /**
   * Source raw data.
   *
   * @var array
   */
  protected $raw_data = array();

  /**
   * Formatted and preprocessed source data.
   *
   * @var array
   */
  protected $data = array();

  /**
   * Declared and missing files paths.
   *
   * @var array
   */
  protected $media = array();

  /**
   * Stored configuration.
   *
   * @var array
   */
  protected $config = array();

  /**
   * Valid products and nodes count.
   *
   * @var array
   */
  protected $count = array(
    'products' => 0,
    'nodes' => 0,
    'upd_products' => 0,
    'upd_nodes' => 0,
  );

  /**
   * Source constructor.
   *
   * @param array $config
   *    Configuration.
   */
  public function __construct($config) {

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
    if ($handle = fopen(drupal_get_path('module', 'cimport') . '/source/source.csv', 'r')) {
      $header = fgetcsv($handle);
      while (($fragment = fgetcsv($handle)) !== FALSE) {
        $joined = array_combine($header, $fragment);
        if (!empty($this->config['group_field'])) {
          $data[$joined[$this->config['group_field']]][] = $joined;
        }
        else {
          $data[] = array($joined);
        }
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
      $newName = $this->config['files_path'] . '/' . strtolower($name);
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
        $item['files'][$key] = strtolower($file);
      }
    }

    return $data;
  }

  /**
   * Filter invalid data.
   */
  protected function filterInvalid($data) {
    foreach ($data as $group => $variants) {
      foreach ($variants as $variant_key => $row) {

        // Generate SKU if missing.
        if (empty($row['sku'])) {
          $max_id = db_query('SELECT MAX(product_id) FROM {commerce_product}')->fetchField();
          $data[$group][$variant_key]['sku'] = $group . '-' . ($max_id + 1);
        }

        // Handle Title.
        if (empty($row['title'])) {
          unset($data[$group][$variant_key]);
        }

      }
    }

    return $data;
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
          $file = strtolower(trim($file));
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

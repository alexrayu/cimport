<?php

/**
 * @file
 *  Handles import from the CSV file. Generic class.
 */

Abstract class Source {

  // Raw data.
  protected $raw_data = array();

  // Formatted data.
  protected $data = array();

  // Media paths.
  protected $media = array();

  // Config.
  protected $config = array();

  // Amount of valid products,
  protected $count;

  function __construct($config) {

    // Source folder.
    $this->config['source_path'] = !empty($config['source_path']) ? $config['source_path'] : drupal_get_path('module', 'cimport') . '/source';

    // Files folder.
    $this->config['files_path'] = !empty($config['files_path']) ? $config['files_path'] : drupal_get_path('module', 'cimport') . '/source/files';

    // CSV File name.
    $this->config['csv_file'] = !empty($config['csv_file']) ? $config['csv_file'] : drupal_get_path('module', 'cimport') . '/source/source.csv';

    // How many first entries to skip (is used for meta data).
    $this->config['skip'] = !empty($config['skip']) ? $config['skip'] : 0;

    // SCV separator.
    $this->config['separator'] = !empty($config['separator']) ? $config['separator'] : ',';

    // Field to group by (commerce multi products).
    $this->config['group_field'] = !empty($config['group_field']) ? $config['group_field'] : NULL;

    // Map array.
    $this->config['map'] = !empty($config['map']) ? $config['map'] : array();

    // File path prefix.
    $this->config['file_path_prefix'] = !empty($config['file_path_prefix']) ? $config['file_path_prefix'] : NULL;

    // Generate sku. Defaults to FALSE.
    $this->config['generate_sku'] = !empty($config['generate_sku']) ? $config['generate_sku'] : FALSE;

    // Whether convert files to lowercase. Defaults to FALSE..
    $this->config['files_tolower'] = !empty($config['files_tolower']) ? $config['files_tolower'] : FALSE;

    $this->load();
    $this->prepare();
  }

  /**
   * Imports csv.
   */
  protected function load() {
    if (($handle = fopen($this->config['csv_file'], "r"))) {
      while (($data = fgetcsv($handle, 0, $this->config['separator']))) {
        $this->raw_data[] = $data;
      }
    }
  }

  /**
   * Prepare csv, format csv.
   */
  protected function prepare() {
    $data = $this->map($this->raw_data);

    // Unset the raw data to save memory.
    $this->raw_data = array();

    if ($this->config['files_tolower']) {
      $data = $this->filesToLower($data);
    }
    $data = $this->filterInvalid($data);
    $this->count = count($data);
    $data = $this->processFilePaths($data);
    $data = $this->group($data);

    $this->data = $data;
  }

  /**
   * Convert all file names to lower.
   */
  protected function filesToLower($data) {

    // Rename physical files.
    $files = scandir($this->config['files_path']);
    foreach($files as $key=>$name){
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
      if (empty($item['file'])) {
        continue;
      }
      $item['file'] = strtolower($item['file']);
    }

    return $data;
  }

  /**
   * Sift invalid data.
   */
  protected function filterInvalid($data) {
    foreach ($data as $key => $item) {
      if (empty($item['sku'])) {
        if (!$this->config['generate_sku']) {
          unset($data[$key]);
          continue;
        }
        else {
          $max_id = db_query('SELECT MAX(product_id) FROM {commerce_product}')->fetchField();
          $data[$key]['sku'] = 'P-' . ($max_id + $key + 1);
        }
      }
      if (empty($item['title'])) {
        unset($data[$key]);
        continue;
      }
    }

    return $data;
  }

  /**
   * Process file paths.
   */
  protected function processFilePaths($data) {
    foreach ($data as &$item) {
      if (empty($item['file'])) {
        continue;
      }
      $ini_path = explode('/' , $item['file']);

      // Clear out the prefix.
      if (!empty($this->config['file_path_prefix'])) {
        if ($this->config['file_path_prefix'] == '*') {
          $item['file'] = $this->config['files_path'] . '/' . array_pop($ini_path);
        }
        else {
          $item['file'] = $this->config['files_path'] . '/' . str_replace($this->config['file_path_prefix'], '', $item['file']);
        }
      }

      // Add file path to media variable.
      $this->media['all'][] = $item['file'];
      if (!file_exists($item['file'])) {
        $this->media['missing'][] = $item['file'];
        $item['file'] = '';
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
  protected function map($data) {
    $new_data = array();
    $counter = -1;
    foreach ($data as $key => $entry) {
      $counter++;
      if ($counter < $this->config['skip']) {
        // Skip the number of top rows indicated in config.
        continue;
      }
      foreach ($entry as $sub_key => $item) {
        $new_data[$key][$this->config['map'][$sub_key]] = $item;
      }
    }

    return $new_data;
  }

  /**
   * Group data.
   */
  protected function group($data) {
    $new_data = array();
    $group_field = $this->config['group_field'];
    if (!empty($group_field)) {
      // Grouped into multi-products,
      foreach ($data as $entry) {
        if (!empty($entry[$group_field])) {
          $field_val = $entry[$group_field];
          $new_data[$field_val]['items'][] = $entry;
        }
      }
    }
    else {
      // No grouping, 1 to 1,
      foreach ($data as $entry) {
        $new_data[] = array(
          'items' => array(
            0 => $entry,
          ),
        );
      }
    }

    return $new_data;
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
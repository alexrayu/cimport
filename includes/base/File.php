<?php

/**
 * @file
 *  Handles Files and Images.
 */

class File {

  // File full and ready path.
  protected $file_path;

  // Drupal dest dir inside the public:// file system..
  protected $dest_dir;

  function __construct($file_path, $dest_dir = 'products') {
    $this->file_path = $file_path;
    $this->dest_dir =  'public://' . $dest_dir;

    $this->import();
  }

  /**
   * Initial fill in of data.
   */
  protected function import() {

    // No entry for image.
    if (empty($this->file_path)) {
      return;
    }

    $path_arr = explode('/', $this->file_path);
    $this->name = array_pop($path_arr);
    $ini_file = $this->newFile($this->file_path);
    $mime_arr = explode('/', $ini_file->filemime);
    $type = !empty($ini_file->filemime) ? reset($mime_arr) : 'generic';
    $new_path = $this->dest_dir . '/' . $type . '/' . $this->name;

    // Check if file exists, create if not.
    $file = $this->loadFile($new_path);
    if (empty($file->fid)) {
      $this->checkDir($this->dest_dir . '/' . $type);
      $file = file_copy($ini_file, $new_path, FILE_EXISTS_REPLACE);
    }

    if (!$file) {
      drupal_set_message('Could not copy file ' . $this->file_path . ' to ' . $new_path . '.', 'warning');
    }

    $this->file = $file;
  }

  /**
   * Check if file exists in the files table.
   */
  protected function loadFile($uri) {
    $files = file_load_multiple(array(), array('uri' => $uri));
    $file = reset($files);

    return !empty($file->fid) ? $file : FALSE;
  }

  /**
   * Make sure dest dir exists and is writable.
   */
  protected function checkDir($dir) {
    if (!drupal_valid_path($dir)) {
      file_prepare_directory($dir, FILE_CREATE_DIRECTORY);
    }
  }


  /**
   * Get term template.
   */
  protected function newFile($initial_path) {
    $file = new stdClass();
    $file->uri = $initial_path;
    $file->filemime = file_get_mimetype($initial_path);
    $file->status = 1;

    return $file;
  }



  /**
   * Returns the file object.
   */
  function getFile() {
    return $this->file;
  }

  /**
   * Returns the term tid.
   */
  function getFid() {
    return !empty($this->file->fid) ? $this->file->fid : NULL;
  }

}
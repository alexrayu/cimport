<?php

/**
 * @file
 *  Template for destination classes.
 */
Abstract class Destination {

  // Product configuration.
  protected $config;

  // The entry.
  protected $entry;

  // Associated tids.
  protected $tids = array();

  /**
   * Gets term from hierarchy path and returns a tid.
   * @param $term_path
   *  The hierarchy of term, like a/b/c.
   * @param $vocab_name
   *  The vocabulary name to use.
   * @param null $color_field
   *  The name of the color field value attached to term (if exists), where the
   *  color value is mapped.
   * @return term id.
   */
  function termPath2Tid($term_path, $vocab_name, $color_field = NULL) {
    $Term = new Term(strtolower($term_path), $vocab_name, $color_field);
    $tid = $Term->getTid();
    $this->tids[] = $tid;
    unset($Term);

    return $tid;
  }

  /**
   * Gets file from source path to import.
   */
  function filePath2Fid($paths, $dest_dir = 'products') {
    $fids_array = array();
    if (!empty($paths)) {
      foreach ($paths as $key => $path) {
        $File = new File($path, $dest_dir);
        $fids_array[] = array(
          'fid' => $File->getFid(),
        );
        unset($File);
      }
    }

    return $fids_array;
  }

  /**
   * Return associated
   */
  function getTids() {
    return $this->tids;
  }


}
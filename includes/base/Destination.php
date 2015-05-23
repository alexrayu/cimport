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
  function filePath2Fid($path, $dest_dir = 'products') {
    $File = new File($path, $dest_dir);
    $fid = $File->getFid();
    unset($File);

    return $fid;
  }

  /**
   * Return associated
   */
  function getTids() {
    return $this->tids;
  }


}
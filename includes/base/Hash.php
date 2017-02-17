<?php

/**
 * @file
 *  Hash handling class.
 */

class Hash {

  /**
   * Get node's import data hash.
   * @param $nid
   *  Node nid.
   * @return string
   *  Data hash.
   */
  public function getHash($nid) {
    return db_query('SELECT hash FROM {import_hash} WHERE nid = :nid', array(':nid' => $nid))->fetchField();
  }

  /**
   * Set node hash.
   *
   * @param $nid
   *  Node id.
   * @param $hash
   *  Data hash.
   */
  public function setHash($nid, $hash) {
    $saved_hash = $this->getHash($nid);
    if (!empty($saved_hash)) {
      db_query('UPDATE {import_hash} SET hash = :hash WHERE nid = :nid', array(':nid' => $nid, ':hash' => $hash));
    }
    else {
      db_query('INSERT INTO {import_hash} (nid, hash) VALUES (:nid, :hash)', array(':nid' => $nid, ':hash' => $hash));
    }
  }

  /**
   * Delete hash.
   * @param $nid
   *  Node id.
   */
  public function delHash($nid) {
    db_query('DELETE FROM {import_hash} WHERE nid = :nid', array(':nid' => $nid));
  }

}

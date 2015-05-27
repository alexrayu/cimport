<?php

/**
 * @file
 *  Handles Terms.
 *
 * Class receives a path, like 'linens/table cloths/red', makes sure all
 *  sub terms exist and hierarchy is kept, and returns the end term in the queue
 *  to associate with.
 *
 *  $vocab is the vocabulary machine name in which the term will be ascertained.
 *
 */

class Term {

  // Term path parts.
  protected $path = array();

  // Term's vocab name and vid.
  protected $vocab = array();

  // Term's object when applicable.
  protected $term;

  // Color value field name.
  protected $color_field;

  function __construct($path, $vocab, $color) {
    $path = trim($path);
    $path = trim($path, '/');
    if (empty($path) || empty($vocab)) {
      return;
    }
    $this->path = explode('/', $path);
    $this->vocab['name'] = $vocab;
    $this->color_field = $color;

    $this->fill();
  }

  /**
   * Initial fill in of data.
   */
  protected function fill() {
    $vocab = taxonomy_vocabulary_machine_name_load($this->vocab['name']);
    if (!empty($vocab->vid)) {
      $this->vocab['vid'] = $vocab->vid;
    }
    else {
      drupal_set_message('Taxonomy ' . $this->vocab['name'] . ' not found for path ' . $this->path, 'error');
    }
    $tid = $this->ascertainTerms();
    $this->term = taxonomy_term_load($tid);
  }

  /**
   * Check if terms exist, create if needed.
   */
  protected function ascertainTerms() {
    $path_names = $this->path;
    $hierarchy = array();

    foreach ($path_names as $key => $name) {
      $parent = $key > 0 ? $hierarchy[$key - 1] : NULL;
      $name = trim($name);

      // No parent tree for the first one.
      if ($key == 0) {
        $term = taxonomy_get_term_by_name($name, $this->vocab['name']);
        $term = reset($term);
        if (empty($term->tid)) {
          $term = $this->newTerm($this->vocab['vid'], $name, $parent);
          taxonomy_term_save($term);
          $this->postProcessTerm($term);
        }
        $hierarchy[$key] = $term->tid;
      }

      // Parent tree for children.
      else {
        $tree = taxonomy_get_tree($this->vocab['vid'], $parent);
        foreach ($tree as $term) {
          if ($term->name == $name) {

            // Found child existing.
            $hierarchy[$key] = $term->tid;
            break;
          }
        }
        // Not found, create.
        if (empty($hierarchy[$key])) {
          $term = $this->newTerm($this->vocab['vid'], $name, $parent);
          taxonomy_term_save($term);
          $this->postProcessTerm($term);
          $hierarchy[$key] = $term->tid;
        }
      }
    }

    return array_pop($hierarchy);
  }

  /**
   * Post Process Term.
   */
  protected function postProcessTerm($term) {
    $changed = FALSE;

    // Apply color to field when applicable.
    if (!empty($this->color_field)) {
      $color = self::getColor($term->name);
      if (!empty($color)) {
        $term->{$this->color_field}['und'][0]['value'] = $color;
        $changed = TRUE;
      }
    }

    // Save term.
    if ($changed) {
      taxonomy_term_save($term);
    }
  }

  /**
   * Erase all terms in vocabulary.
   *
   * Caution! This will erase all terms in a vocab!
   * Do not use unless really need to!
   *
   */
  protected function emptyVocabulary() {
    foreach (taxonomy_get_tree($this->vocab['vid']) as $term) {
      taxonomy_term_delete($term->tid);
    }
  }

  /**
   * Get term template.
   */
  protected function newTerm($vid, $name, $parent) {
    $term = new stdClass();
    $term->name = $name;
    $term->vid = $vid;
    $term->parent = $parent;

    return $term;
  }

  /**
   * Check if term exists.
   */
  protected function loadTerm() {
    $term = taxonomy_get_term_by_name($this->name, $this->vocab['vid']);
  }

  /**
   * Returns the term object.
   */
  function getTerm() {
    return $this->term;
  }

  /**
   * Returns the term object.
   */
  function getName() {
    return $this->term->name;
  }

  /**
   * Returns the term tid.
   */
  function getTid() {
    return !empty($this->term->tid) ? $this->term->tid : NULL;
  }

  /**
   * Returns color value by name if finds one.
   */
  public static function getColor($colorname) {
    $colors  =  array(
      "black"=>'000000',
      "maroon"=>'800000',
      "green"=>'008000',
      "olive"=>'808000',
      "navy"=>'000080',
      "purple"=>'800080',
      "teal"=>'008080',
      "gray"=>'808080',
      "silver"=>'c0c0c0',
      "red"=>'ff0000',
      "lime"=>'00ff00',
      "yellow"=>'ffff00',
      "blue"=>'0000ff',
      "fuchsia"=>'ff00ff',
      "aqua"=>'00ffff',
      "white"=>'ffffff',
      "aliceblue"=>'f0f8ff',
      "antiquewhite"=>'faebd7',
      "aquamarine"=>'7fffd4',
      "azure"=>'f0ffff',
      "beige"=>'f5f5dc',
      "blueviolet"=>'8a2be2',
      "brown"=>'a52a2a',
      "burlywood"=>'deb887',
      "cadetblue"=>'5f9ea0',
      "chartreuse"=>'7fff00',
      "chocolate"=>'d2691e',
      "coral"=>'ff7f50',
      "cornflowerblue"=>'6495ed',
      "cornsilk"=>'fff8dc',
      "crimson"=>'dc143c',
      "darkblue"=>'00008b',
      "darkcyan"=>'008b8b',
      "darkgoldenrod"=>'b8860b',
      "darkgray"=>'a9a9a9',
      "darkgreen"=>'006400',
      "darkkhaki"=>'bdb76b',
      "darkmagenta"=>'8b008b',
      "darkolivegreen"=>'556b2f',
      "darkorange"=>'ff8c00',
      "darkorchid"=>'9932cc',
      "darkred"=>'8b0000',
      "darksalmon"=>'e9967a',
      "darkseagreen"=>'8fbc8f',
      "darkslateblue"=>'483d8b',
      "darkslategray"=>'2f4f4f',
      "darkturquoise"=>'00ced1',
      "darkviolet"=>'9400d3',
      "deeppink"=>'ff1493',
      "deepskyblue"=>'00bfff',
      "dimgray"=>'696969',
      "dodgerblue"=>'1e90ff',
      "firebrick"=>'b22222',
      "floralwhite"=>'fffaf0',
      "forestgreen"=>'228b22',
      "gainsboro"=>'dcdcdc',
      "ghostwhite"=>'f8f8ff',
      "gold"=>'ffd700',
      "goldenrod"=>'daa520',
      "greenyellow"=>'adff2f',
      "honeydew"=>'f0fff0',
      "hotpink"=>'ff69b4',
      "indianred"=>'cd5c5c',
      "indigo"=>'4b0082',
      "ivory"=>'fffff0',
      "khaki"=>'f0e68c',
      "lavender"=>'e6e6fa',
      "lavenderblush"=>'fff0f5',
      "lawngreen"=>'7cfc00',
      "lemonchiffon"=>'fffacd',
      "lightblue"=>'add8e6',
      "lightcoral"=>'f08080',
      "lightcyan"=>'e0ffff',
      "lightgoldenrodyellow"=>'fafad2',
      "lightgreen"=>'90ee90',
      "lightgrey"=>'d3d3d3',
      "lightpink"=>'ffb6c1',
      "lightsalmon"=>'ffa07a',
      "lightseagreen"=>'20b2aa',
      "lightskyblue"=>'87cefa',
      "lightslategray"=>'778899',
      "lightsteelblue"=>'b0c4de',
      "lightyellow"=>'ffffe0',
      "limegreen"=>'32cd32',
      "linen"=>'faf0e6',
      "mediumaquamarine"=>'66cdaa',
      "mediumblue"=>'0000cd',
      "mediumorchid"=>'ba55d3',
      "mediumpurple"=>'9370d0',
      "mediumseagreen"=>'3cb371',
      "mediumslateblue"=>'7b68ee',
      "mediumspringgreen"=>'00fa9a',
      "mediumturquoise"=>'48d1cc',
      "mediumvioletred"=>'c71585',
      "midnightblue"=>'191970',
      "mintcream"=>'f5fffa',
      "mistyrose"=>'ffe4e1',
      "moccasin"=>'ffe4b5',
      "navajowhite"=>'ffdead',
      "oldlace"=>'fdf5e6',
      "olivedrab"=>'6b8e23',
      "orange"=>'ffa500',
      "orangered"=>'ff4500',
      "orchid"=>'da70d6',
      "palegoldenrod"=>'eee8aa',
      "palegreen"=>'98fb98',
      "paleturquoise"=>'afeeee',
      "palevioletred"=>'db7093',
      "papayawhip"=>'ffefd5',
      "peachpuff"=>'ffdab9',
      "peru"=>'cd853f',
      "pink"=>'ffc0cb',
      "plum"=>'dda0dd',
      "powderblue"=>'b0e0e6',
      "rosybrown"=>'bc8f8f',
      "royalblue"=>'4169e1',
      "saddlebrown"=>'8b4513',
      "salmon"=>'fa8072',
      "sandybrown"=>'f4a460',
      "seagreen"=>'2e8b57',
      "seashell"=>'fff5ee',
      "sienna"=>'a0522d',
      "skyblue"=>'87ceeb',
      "slateblue"=>'6a5acd',
      "slategray"=>'708090',
      "snow"=>'fffafa',
      "springgreen"=>'00ff7f',
      "steelblue"=>'4682b4',
      "tan"=>'d2b48c',
      "thistle"=>'d8bfd8',
      "tomato"=>'ff6347',
      "turquoise"=>'40e0d0',
      "violet"=>'ee82ee',
      "wheat"=>'f5deb3',
      "whitesmoke"=>'f5f5f5',
      "yellowgreen"=>'9acd32',
    );

    $name = strtolower($colorname);
    $val = isset($colors[$name]) ? $colors[$name] : NULL;

    return $val;
  }

}
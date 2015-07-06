# Commerce Import

Import CSV to Drupal Commerce.

Had some very very eccentric CSV to import to Drupal Commerce. Started to buid around Migrate, but then moved out as a self-standing module. Now, it is not related to Migrate module at all.

You will need to do some coding to import using this module.

NOTE: This module is NOT the best way to import data into Commerce, so use the [Migrate module] (https://www.drupal.org/project/migrate) whenever you can!

## Usage

1. Set up config in cimport.module.


2. The module first verifies the sources and files to import, and then creates the data object out of it.
  * Subclass the Source class. See the DSCSource class as a sample.
  * Products with the SKUs that are already in the database will update the existing products.
  * Write the mapping in the cimport.module config function.
  * Run a `drush cis` command to see if the source is taken up correctly and files are found and recognized.

  *Example:* See classes starting with DSC for example in the `includes/desc` folder.


3. Map the fields in sublasses for Product and Display. Use DSCProduct and DSCDisplay as a sample.
  * When mapping, present the hierarchical taxonomy terms like parent/chaild/child_1, the Term class should create hierarchy where missing and assign the end child term (see the HRERProduct as a sample).
  * When multiple products are added to a Display node, the categories must all be mapped to that display node so it can be found in catalogs. See for an example the HRERDisplay class subclassing the Display class.

  *Example:* See the classes DSCProduct and DSCDisplay for an example in the `includes/desc` folder.


4. *The files*. The `files` mapping key must not be renamed. More then one file can be imported into a field. Do do that, list the files through comma, like `'file1.jpg,file2.jpg,file3.jpg'`, and so on.

 *Example (In the Product subclass):

 ```php
 // Files
 $fids_array = $this->filePath2Fid($this->entry['files']);
 if (!empty($fids_array)) {
   $product->field_product_image['und'] = $fids_array;
 }
 ```


5. *Taxonomy terms*. Each term is mapped with the `termPath2Tid($term_path, $vocab_name, $color_field)` function, where `$term_path` stands for the hierarchical path of the term, such as `term1/term2`, `$vocab_name` stands for the name of the vocabulary, and `$color_field` is reserved to designate the color value field name for those terms, that depict color. If a term is missing from the vocabulary, it will be created. When source terms have hierarchy, like `term1/term2/term3`, a hierarchy of terms shall be created.

 *Example* (In the Product subclass):

 ```php
 // Size Term
 $tid = $this->termPath2Tid($this->entry['size'], 'size');
 if (!empty($tid)) {
   $product->field_size['und'][0]['tid'] = $tid;
 }
 ```


6. *Color terms*. Some terms denote color. CImport will automatically try to match the color value from the name of the color. If it does so sucessfully, and the name of the color field is specified for the `termPath2Tid()` function, then the value of that color will be added to that text field. 

 *Example* (In the Product subclass):

 ```php
 // Color Term
 $tid = $this->termPath2Tid($this->entry['color'], 'color', 'field_hex_value');
 if (!empty($tid)) {
   $product->field_product_color['und'][0]['tid'] = $tid;
 }
 ```


7. *Physical dimensions* are to be inserted as follows:

 ```php
 // Physical dimensions
 $product->field_physical_dimensions['und'][0] = array(
   'height' => !empty($this->entry['height']) ? $this->entry['height'] : 0,
   'length' => !empty($this->entry['length']) ? $this->entry['length'] : 0,
   'width' => !empty($this->entry['width']) ? $this->entry['width'] : 0,
   'unit' => 'in',
 );
 ```


If you find this module unclear or not working in your specific case, remember, that I asked you to not use it and use the Migrate module instead! ;)


# Commerce Import

Import CSV to Drupal Commerce.

Had some very very eccentric CSV to import to Drupal Commerce. Started to buid around Migrate, but then moved out as a self-standing module. Now, it is not related to Migrate module at all.

You will need to do some coding to import using this module.

NOTE: This module is NOT the best way to import data into Commerce, so use the [Migrate module] (https://www.drupal.org/project/migrate) whenever you can!

## Usage

1. Set up config in cimport.module.

2. The module first verifies the sources and files to import, and then created the data object out of it.
  * Subclass the Source class. See the HRERSource class as a sample.
  * Write the mapping in the cimport.module config function.
  * Run a `drush cis` command to see if the source is taken up correctly and files are found and recognized.

3. Map the fields in sublasses for Product and Display. Use HRER product and HRERDisplay as a sample.
  * When mapping, present the hierarchical taxonomy terms like parent/chaild/child_1, the Term class should create hierarchy where missing and assign the end child term (see the HRERProduct as a sample).
  * When multiple products are added to a Display node, the categories must all be mapped to that display node so it can be found in catalogs. See for an example the HRERDisplay class subclassing the Display class.

If you find this module unclear or not working in your specific case, remember, that I asked you to not use it and use the Migrate module instead! ;)


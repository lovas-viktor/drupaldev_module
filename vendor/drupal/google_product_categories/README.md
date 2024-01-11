<pre>
  ┌───────┐
  │       │
  │  a:o  │  acolono.com
  │       │
  └───────┘
</pre>

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Installation
 * Configuration


INTRODUCTION
------------

This module imports all Google Product Categories into a hierarchical Drupal taxonomy, so that it can be used for Google Merchant Product lists:
https://support.google.com/merchants/answer/6324436?hl=en

FEATURES
------------
 
 * batch processing is supported
 * import of translations is supported
 * available option to delete all existing `google_product_taxonomy` terms

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------
 * Import page is located here: `admin/config/google-product-categories`
 * `google_product_taxonomy` vocabulary and two fields `field_google_product_taxonomy_id` and `field_google_product_taxonomy_pa` should be created before import
 * If vocabulary is translatable - an additional dropdown list with supported languages will be displayed on the Import page

by acolono GmbH
---------------

~~we build your websites~~
we build your business

hello@acolono.com

www.acolono.com
www.twitter.com/acolono
www.drupal.org/acolono-gmbh
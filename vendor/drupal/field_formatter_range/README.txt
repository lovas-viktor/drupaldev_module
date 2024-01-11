CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Similar modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Field Formatter Range module provides the option to display only selected
range of values for multivalued entity fields.

For example if you have an image field attached to an entity and the entity has
15 images attached to it but you want to display only the first 5 of them,
this module is exactly what you are looking for.

Beside setting the range (offset and number of items to show), you can also
reverse the order so you can display just the last 5 images and by setting
proper values you can display them in order or in reverse order too.


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


SIMILAR MODULES
---------------

 * Field Multiple Limit (https://www.drupal.org/project/field_multiple_limit):
   D7 only.
 * Field Limiter (https://www.drupal.org/project/field_limiter): provides a
   dedicated formatter whereas this module extends all formatters.


INSTALLATION
------------

 * Install and enable this module like any other drupal 8 module.


CONFIGURATION
-------------

The module add options on each field formatter of multivalued fields. You can
access these options like you normally would when configuring a field display.

Those options are:
 * offset: where to start,
 * limit: how many items to show,
 * order: will display the items in the selected order, default, reverse or
   random.


MAINTAINERS
-----------

Current maintainers:
 * Florent Torregrosa (Grimreaper) - https://www.drupal.org/user/2388214
 * Pierre Dureau (pdureau) - https://www.drupal.org/user/1903334

Previous maintainers:
 * Ivan Jaros (ivanjaros) - https://www.drupal.org/user/135190

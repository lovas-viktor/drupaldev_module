/**
 * @file
 * Attaches behaviors for the Route condition module.
 */
(function ($) {

  "use strict";

  /**
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blockSettingsSummaries = {
    attach: function () {
      // The drupalSetSummary method required for this behavior is not available
      // on the Blocks administration page, so we need to make sure this
      // behavior is processed only if drupalSetSummary is defined.
      if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
        return;
      }

      $('[data-drupal-selector="edit-visibility-route"]').drupalSetSummary(function (context) {
        var $routes = $(context).find('textarea[name="visibility[route][routes]"]');
        if (!$routes.val()) {
          return Drupal.t('Not restricted');
        }
        return Drupal.t('Restricted to certain routes');
      });
    }
  };

})(jQuery);

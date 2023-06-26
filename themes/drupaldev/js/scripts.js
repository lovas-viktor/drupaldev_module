(function ($, Drupal, once) {
    Drupal.behaviors.drupaldev = {
        attach: function (context, settings) {
            once('drupaldev', 'body', context).forEach(
                function (element) {
                    element.classList.add('scripts-loaded');
                }
            );
        }
    };
})(jQuery, Drupal, once);
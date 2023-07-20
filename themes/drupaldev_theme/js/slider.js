(function ($, Drupal) {
  Drupal.behaviors.initSlier = {
    attach: function (context, settings) {

      $('.flexslider').flexslider({
        animation: "slide"
      });

    }
  };
})(jQuery, Drupal);

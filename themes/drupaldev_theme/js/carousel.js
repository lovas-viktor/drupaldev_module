(function ($, Drupal) {
  Drupal.behaviors.initSlier = {
    attach: function (context, settings) {

      $('.owl-carousel').each(function(){
        $(this).owlCarousel({
          items: 1,
          loop: true,
          dotsSpeed: 1000
        });
      });

    }
  };
})(jQuery, Drupal);

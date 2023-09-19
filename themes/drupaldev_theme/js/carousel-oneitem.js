(function ($, Drupal) {
  Drupal.behaviors.initSlier = {
    attach: function (context, settings) {

      $('.owl-carousel.owl-oneitem').each(function(){
        $(this).owlCarousel({
          items: 1,
          loop: true,
          dotsSpeed: 500,
          margin:30,
        });
      });
    }
  };
})(jQuery, Drupal);

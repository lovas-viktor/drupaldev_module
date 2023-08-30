(function ($, Drupal) {
  Drupal.behaviors.initSlier = {
    attach: function (context, settings) {



      $('.view-related-products .views-field-related-products .field-content > ul ').each(function(){
        $(this).addClass(['owl-carousel','owl-theme']);
        $(this).owlCarousel({
          loop: true,
          margin:30,
          dotsSpeed: 1000,
          responsive:{
            0:{
              items: 1
            },
            767:{
              items:3
            },
            991:{
              items:4
            }
          }
        });
      });
    }
  };
})(jQuery, Drupal);

(function ($, Drupal) {
  Drupal.behaviors.initSlier = {
    attach: function (context, settings) {

      $('.view-related-products .views-field-related-products .field-content > ul ').each(function(){
        let owl = $(this);
        owl.addClass(['owl-carousel','owl-theme']);
        owl.owlCarousel({
          //loop: true,
          margin:30,
          dotsSpeed: 1000,
          responsive:{
            0:{
              items: 2,
              mouseDrag: false,
              touchDrag: true
            },
            767:{
              items:3,
              mouseDrag: false,
              touchDrag: true,
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

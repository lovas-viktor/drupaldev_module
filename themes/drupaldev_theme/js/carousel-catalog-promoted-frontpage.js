(function ($, Drupal) {
  Drupal.behaviors.initFrontpagePromotedCatalogCarousel = {
    attach: function (context, settings) {

      $('.owl-catalog-promoted-frontpage').each(function(){
        $(this).owlCarousel({
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
              items:4,
              mouseDrag: false,
              touchDrag: true,
            },
            991:{
              items:6
            }
          }
        });
      });

    }
  };
})(jQuery, Drupal);

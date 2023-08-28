(function ($, Drupal) {
  Drupal.behaviors.initSlier = {
    attach: function (context, settings) {

      $('.flexslider').each(function(){
        $(this).flexslider({
          animation: "slide",
          mousewheel: true,
          direction: "horizontal",
          slideshow: false,
          animationSpeed: 2000,
        });
      });

    }
  };
})(jQuery, Drupal);

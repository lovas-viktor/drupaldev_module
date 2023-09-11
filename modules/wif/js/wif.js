var light_gallery_images = [];
var active_image = [];

(function ($) {
  Drupal.behaviors.wif = {
    attach: function (context, settings) {

      settings.wif = settings.wif || drupalSettings.wif;

      // Flexslider
      $('.wif-container .wif-gallery-carousel', context).flexslider({
        animation: "slide",
        itemWidth: 82,
        minItems: 2,
        maxItems: 7,
        itemMargin: 10,
        slideshow: false,
        controlNav: false,
        prevText: "",
        nextText: ""
      });

      // Light gallery
      $('.wif-container', context).each(function(){
        // Set the id as a group for images
        var $id = $(this).find('.ligh-gallery-main-image').attr('id');
        light_gallery_images[$id] = [];
        active_image[$id] = 0;

        // Collect images
        var i = 0;
        $(this).find('.image-additional .slides li a').each(function(){
          light_gallery_images[$id][i] = {
            src: $(this).attr('href'),
            thumb: $(this).find('img').attr('src'),
          };
          i++;
        });

        // Click on thumbnail
        $(this).find('.wif-gallery').click(function(e){
          e.preventDefault();

          // Set active image number
          active_image[$id] = $(this).data('image-number');
          var slideImage = $(this).data('slide-image');
          var zoomedImage = $(this).attr('href');

          // Handle webp differently.
          if ($(this).hasClass('wif-webp')) {
            slideImage = JSON.parse(slideImage);
          }

          // Destroy the lightgallery if needed
          var $lg = $(this).parents('.wif-container').find('.image').data('lightGallery');
          if ( typeof($lg) !== 'undefined' ) {
            $lg.destroy(true);
          }

          if ($(this).hasClass('wif-webp')) {
            $(this).parents('.wif-container').find('.elevateZoom').html(slideImage);
          } else {
            $(this).parents('.wif-container').find('.elevateZoom').attr("href", zoomedImage).find("img").attr("src", slideImage);
          }

          return false;
        });

        // Click on the main image
        $(this).find('.image').click(function(){

          // Show lightgallery
          $(this).lightGallery({
            dynamic: true,
            dynamicEl: light_gallery_images[$id],
            download: false,
            index: active_image[$id],
          });

          return false;
        });

      });
    }
  };

})(jQuery);

(function ($, Drupal) {
  Drupal.behaviors.lightgalleryGallerySimple = {
    attach: function (context, settings) {
      console.log("lightgaller simple js running");

      $('.lightgallery-gallery-simple').each(function(){
        $(this).lightGallery({
        });
      });

    }
  };
})(jQuery, Drupal);

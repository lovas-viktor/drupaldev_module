(function ($) {
  Drupal.behaviors.dismissMesssage = {
    attach: function (context, settings) {
      $('.alert-dismissible .close').on('click', function () {
        $(this).closest('.alert-dismissible').fadeOut(300, function() {
          $(this).remove();
        });
      });
    }
  };
})(jQuery);

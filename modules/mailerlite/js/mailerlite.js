(function ($, Drupal) {
  Drupal.behaviors.mailerlite = {
    attach: function (context, settings) {
      if ($.fn.datepicker) {
        $('#datepicker').datepicker({
          dateFormat: 'M d',
          changeMonth: true,
          changeYear: false,
          minDate: new Date(2024, 0, 0),
          maxDate: new Date(2024, 11, 31),
          yearRange: "-0:+0",
        });
      } else {
        console.error('No datepicker library found');
      }
    }
  };
})(jQuery, Drupal);

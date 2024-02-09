(function ($, Drupal) {
  Drupal.behaviors.mailerlite = {
    attach: function (context, settings) {

      $("#datepicker").datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: false,
        minDate: new Date(2024, 0, 0),
        maxDate: new Date(2024, 11, 31),
        yearRange: "-0:+0"
      });
    }
  };
})(jQuery, Drupal);

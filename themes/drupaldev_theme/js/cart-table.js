(function ($, Drupal, once) {
  Drupal.behaviors.iniCartDataTable = {
    attach: function (context, settings) {

      $(once('datatable', '#commerce-cart-form--table')).dataTable({
       searching: false,
       paging: false,
       info: false,
       responsive: true,
       ordering:false,
      });

    },
  };
})(jQuery, Drupal, once);

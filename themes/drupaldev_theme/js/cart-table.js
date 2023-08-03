(function ($, Drupal) {
  Drupal.behaviors.iniCartDataTable = {
    attach: function (context, settings) {

     $('#commerce-cart-form--table').once('datatable').dataTable({
       searching: false,
       paging: false,
       info: false,
       responsive: true,
       ordering:false,
      });

    },
  };
})(jQuery, Drupal);

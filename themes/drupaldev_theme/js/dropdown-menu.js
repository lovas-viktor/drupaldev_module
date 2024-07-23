(function ($, Drupal, once) {
  Drupal.behaviors.drupaldevDropdownMenu = {
    attach: function (context, settings) {

      function dropdownInit() {

        $(once('clickToItem', '.dropdown-menu .menu-item--expanded > div .dropdown-arrow')).on('click', function (event) {
          var parent = $(this).parent().parent();

          if (parent.hasClass('menu-item--opened')) {
            $('.dropdown-menu .menu-item--expanded').removeClass('menu-item--opened');
            parent.addClass('menu-item--closed');
            parent.removeClass('menu-item--opened');
          } else if (parent.hasClass('menu-item--active-trail') && !parent.hasClass('menu-item--closed')) {
            parent.addClass('menu-item--closed');
          } else {
            parent.addClass('menu-item--opened');
            parent.removeClass('menu-item--closed');
          }
        });
      }

      dropdownInit();
    },
  };
})(jQuery, Drupal, once);

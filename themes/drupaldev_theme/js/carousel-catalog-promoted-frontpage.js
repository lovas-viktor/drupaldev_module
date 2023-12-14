(function ($, Drupal) {
  Drupal.behaviors.initFrontpageCatalogCarousel = {
    attach: function (context, settings) {

      var $owl = $('.owl-carousel--frontpage-catalog');

      $('.frontpage-owl-wrapper').each(function(){
        var owlItems = $(this).find('.owl-carousel--frontpage-catalog').length;

        for (var i = 0; i < owlItems; i++) {
          var actualItem = i + 1;
          $(this).find('.owl-carousel--frontpage-catalog').eq(i).addClass('owl-carousel-' + actualItem);
        }

        $owl.owlCarousel({
          items: 1,
          loop: true,
          mouseDrag: false,
          touchDrag: false,
          pullDrag: false,
          dots: false,
          autoplay: true,
          animateOut: 'fadeOut',
          animateIn: 'fadeIn',
          autoplayTimeout: owlItems * 2000,
        });

      });

      $('.owl-carousel--frontpage-catalog').trigger('stop.owl.autoplay');

      var items = {
        itemindex: [],
        itemname: [],
        itemdelay: [],
      };
      $owl.each(function (index) {
        var delay = (index + 1) *2000;
        items.itemindex.push(index);
        items.itemname.push('.owl-carousel-' + (index + 1));
        items.itemdelay.push(delay);

        setTimeout(function() {
          $(items.itemname[index]).trigger('play.owl.autoplay');
        }, items.itemdelay[index]);
      });

    }
  };
})(jQuery, Drupal);

(function ($, Drupal) {

    "use strict";

    function googleReviewsCarousel() {
        $('.carousel-only-mobile').each(function(){
            let owl = $(this);
            owl.addClass(['owl-carousel','owl-theme']);
            owl.owlCarousel({
                //loop: true,

            });
        });
    }


    Drupal.behaviors.initSliderGooglereviews = {
    attach: function (context, settings) {
        var windowWidth = $(window).width();
        var owl = $('.carousel-only-mobile');

        // set the owl-carousel otions
        var carousel_Settings = {
            margin:30,
            dotsSpeed: 1000,
            responsive:{
                0:{
                    items: 1,
                    mouseDrag: false,
                    touchDrag: true
                },
                420:{
                    items:2,
                    mouseDrag: false,
                    touchDrag: true,
                }
            }
        };

        if(windowWidth <= 991){
             owl.addClass(['owl-carousel','owl-theme']);
             owl.owlCarousel( carousel_Settings );
        } else{
            owl.trigger('destroy.owl.carousel').removeClass('owl-carousel owl-theme owl-loaded');
            owl.find('.owl-stage-outer').children().unwrap();
        }
        //googleReviewsCarousel();

        $(window).resize(function() {
            var windowWidth = $(window).width();
            console.log(windowWidth);
            if(windowWidth <= 991){
                owl.addClass(['owl-carousel','owl-theme']);
                owl.owlCarousel( carousel_Settings );
            } else{
                owl.trigger('destroy.owl.carousel').removeClass('owl-carousel owl-theme owl-loaded');
                owl.find('.owl-stage-outer').children().unwrap();
            }
            //googleReviewsCarousel();
        });
    }
  };
})(jQuery, Drupal);

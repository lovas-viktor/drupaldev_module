(function ($) {
  Drupal.behaviors.drupaldevCollapse = {
    attach: function (context, settings) {

      let windowsize = $(window).width();
      let last_target;

      $(window).resize(function() {
        windowsize = $(window).width();
      });

      function menuInit(){

        $(".collapse-toggler").once('clickToToggler').on('click',function(event){

          const target = $(this).attr('data-target');
          last_target = '#' + $('.collapse-content.opened').attr('id');

          if(target && target!== last_target){
            closeCollapse(last_target);
            openCollapse(target);
          }

          if (target && target == last_target){
            closeCollapse(last_target);
          }
        });

        $('.collapse-overlay').once('collapseOverlay').on('click',function(){
          closeCollapse();
        });
      }

      function openCollapse(target){
        $('body').addClass('collapse-opened');
        $( target ).toggleClass('opened');
        if($( target ).find('.slinky-menu--mobile')){
          console.log('slinky');
          if(windowsize < 768){
            $(".slinky-menu--mobile").once('slinkyOnce').each(function(){
              $(this).once('slinkyInit').slinky({
                title: true
              });
            });
          }
        }
      }

      function closeCollapse(target){
        $('body').removeClass('collapse-opened');
        $('.collapse-content').removeClass('opened');
      }

      // Execute on load
      menuInit();
      // Bind event listener
      $(window).resize(menuInit);
    }
  };
})(jQuery);

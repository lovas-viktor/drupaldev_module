(function ($, Drupal) {
  Drupal.behaviors.initNumberInput = {
    attach: function (context, settings) {

      $('.number-input').once('quantityPlusMInus').each(function(){
        var step = Number.parseFloat( $(this).find('input').attr('step'));

        $(this).find('.minus').once('minusClicked').on('click', function () {
          var $input = $(this).parent().find('input');
          var inputVal = Number.parseFloat($input.val());
          var count = ((inputVal * 100) - (step *100)) / 100 ;
          count = count < 1 ? 0 : count;
          $input.val(parseFloat((count*100/100).toFixed(2)));
          $input.change();
          return false;
        });

        $(this).find('.plus').once('plusClicked').on('click', function () {
          var $input = $(this).parent().find('input');
          var inputVal = Number.parseFloat($input.val());
          var count = ((inputVal * 100) + (step *100)) / 100 ;
          var count2 = inputVal + step;

          $input.val(parseFloat((count*100/100).toFixed(2)));
          $input.change();
          return false;
        });
      });

      $('input[id*="edit-quantity"]').once('quantityeach').each(function(){
        var inputStep = Number.parseFloat($(this).attr('step'));

        $(this).once('inputFocusout').focusout(function() {
          var inputRemainder = ($(this).val() * 100) % (inputStep * 100) / 100;
          var divisibleValue = Math.ceil(((($(this).val() * 100) - inputRemainder ) / inputStep) / 100);
          if( inputRemainder !== 0 ){
            var newValue = (divisibleValue) * inputStep;
            $(this).val(newValue.toFixed(2));
          }
        });
      });

    }
  };
})(jQuery, Drupal);

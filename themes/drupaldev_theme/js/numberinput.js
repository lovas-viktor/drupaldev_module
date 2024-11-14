(function ($, Drupal, once) {
  Drupal.behaviors.initNumberInput = {
    attach: function (context, settings) {

      $(once('quantityPlusMInus', '.number-input')).each(function(){
        var step = Number.parseFloat( $(this).find('input').attr('step'));

        $(once('minusClicked', $(this).find('.minus'))).on('click', function () {
          var $input = $(this).parent().find('input');
          var inputVal = Number.parseFloat($input.val());
          var count = ((inputVal * 100) - (step *100)) / 100 ;
          count = count < 1 ? 0 : count;
          $input.val(parseFloat((count*100/100).toFixed(2)));
          $input.change();
          return false;
        });

        $(once('plusClicked', $(this).find('.plus'))).on('click', function () {
          var $input = $(this).parent().find('input');
          var inputVal = Number.parseFloat($input.val());
          var count = ((inputVal * 100) + (step *100)) / 100 ;
          var count2 = inputVal + step;

          $input.val(parseFloat((count*100/100).toFixed(2)));
          $input.change();
          return false;
        });
      });

      $(once('quantityeach', 'input[id*="edit-quantity"]')).each(function(){
        var inputStep = Number.parseFloat($(this).attr('step'));

        $(once('inputFocusout', $(this))).focusout(function() {
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
})(jQuery, Drupal, once);

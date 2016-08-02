(function ($) {
  Drupal.behaviors.smt_profile = {
    attach: function(context, settings) {

      $('#edit-smtprofile-gender').change(function() {
        var val = String($(this).val());
        if (val.match('Trans') || val.match('Another')) {
          $('.form-item-smtprofile-gender-explain').removeClass('hidden');
        } else {
          $('.form-item-smtprofile-gender-explain').addClass('hidden');
        }
      });

      $('.form-item-smtprofile-gender-explain').removeClass('form-item');
      var val = String($('#edit-smtprofile-gender').val());
      if (!(val.match('Trans') || val.match('Another'))) {
        $('.form-item-smtprofile-gender-explain').addClass('hidden');
      }

    }
  };
})(jQuery);

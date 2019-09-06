(function ($, Drupal) {
  Drupal.behaviors.equizTimer = {
    attach: function (context, settings) {
      document.addEventListener('contextmenu', event => event.preventDefault());
      const endTime = $('.equiz-timer .field-timer-jquery-countdown').attr('data-timestamp');
      if (endTime) {
        var timerInterval = setInterval(runFunction,1000);
      }

      function runFunction() {
        let currentTime = Math.floor(Date.now() / 1000);
        if (currentTime >= endTime) {
          clearInterval(timerInterval);
          $('form#equiz-question-form').submit();
          alert('Time Over! Your quiz has been auto submitted.');
        }
      }
    }
  };

  $(document).ready(function() {
    if (equizPageLoadTimer !== undefined) {
      let timeWasted = Date.now() - equizPageLoadTimer;
      $('input[name=time_wasted]').val(timeWasted);
    }
  });
})(jQuery, Drupal);

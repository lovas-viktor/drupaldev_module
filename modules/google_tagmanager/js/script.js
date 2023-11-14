(function ($) {
  Drupal.behaviors.drupaldev_google_tagmanager = {
    attach: function (context) {
      if (context === document) {
        var gtm_id = drupalSettings.drupaldev_google_tagmanager.gtm_id;
        document.addEventListener('DOMContentLoaded', () => {
          /** init gtm after 3500 seconds - this could be adjusted */
          setTimeout(initGTM, 3500);
        });
        document.addEventListener('scroll', initGTMOnEvent);
        document.addEventListener('mousemove', initGTMOnEvent);
        document.addEventListener('touchstart', initGTMOnEvent);

        function initGTMOnEvent(event) {
          initGTM();
          event.currentTarget.removeEventListener(event.type, initGTMOnEvent); // remove the event listener that got triggered
        }

        function initGTM() {
          if (window.gtmDidInit) {
            return false;
          }

          window.gtmDidInit = true; // flag to ensure script does not get added to DOM more than once.
          const script = document.createElement('script');
          script.type = 'text/javascript';
          script.async = true;
          // ensure PageViews is always tracked (on script load)
          script.onload = () => {
            dataLayer.push({
              event: 'gtm.js',
              'gtm.start': new Date().getTime(),
              'gtm.uniqueEventId': 0,
            });
          };
          script.src = 'https://www.googletagmanager.com/gtm.js?id=' + gtm_id;
          document.head.appendChild(script);
        }
      }
    },
  };
})(jQuery);


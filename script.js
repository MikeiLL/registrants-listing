(function ($) {
    $(document).ready(function ($) {
      // Shortcode atts for current page from parent plugin.
      const atts = mz_mindbody_schedule.atts;
      console.log("script loaded", atts);

      if (user_tools.missing_oauth_settings + "" === "true") {
        console.error("Missing OAuth settings. Please check your Mindbody API settings.");
      }

      /**
       * State will store and track status
       */
      const mz_mbo_state = {};


      // TODO dedupe from main plugin
      async function fetch_registrants(classID) {

        return $.ajax({
          type: "GET",
          dataType: 'json',
          url: mz_mindbody_schedule.ajaxurl,
          data: {action: 'mz_mbo_get_registrants', nonce: mz_mindbody_schedule.display_schedule_nonce, classID: classID},
          beforeSend: function() {
            mz_mbo_state.action = 'processing';
            mz_mbo_state.fetching = 'ClassRegistrants';
          },
          success: function (json) {
            if (json.type == "success") {
              return {'registrants': json.message};
            } else {
              return {'error': json.message};
            }
          } // ./ Ajax Success
        }) // End Ajax
        .fail(function (json) {
          console.error("Error fetching registrants", json);
          return {'error': json.message};
        }); // End Fail
     }

      $('#mz_registrants_listing').on('click', '.show_registrants', async (e) => {
        // get the data-classid attribute from the clicked element
        let classID = $(e.currentTarget).data('classid');
        console.log("ClassID", classID);
        const registrants = await fetch_registrants(classID);
        console.log("Registrants", registrants);
        // append list of registrants to the clicked element
        $(e.currentTarget).append('<ul class="registrants"></ul>');
        if (registrants.error) {
          $(e.currentTarget).find('.registrants').append(`<li>${registrants.message}</li>`);
        } else if (typeof registrants.message === 'string' || registrants.message.length === 0) {
          $(e.currentTarget).find('.registrants').append(`<li>${registrants.message}</li>`);
        } else {
          registrants.message.forEach((registrant) => {
            $(e.currentTarget).find('.registrants').append(`<li>${registrant}</li>`);
          });
        }
      });

    }); // End document ready
})(jQuery);

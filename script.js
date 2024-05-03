console.log("script loaded");
(function ($) {
    $(document).ready(function ($) {
      // Shortcode atts for current page from parent plugin.

      /**
       * State will store and track status
       */
      const mz_mbo_state = {};


      // TODO dedupe from main plugin
      async function fetch_registrants(classID) {

        return $.ajax({
          type: "GET",
          dataType: 'json',
          url: mz_registrants_list.ajaxurl,
          data: {action: 'mz_mbo_get_registrants', nonce: mz_registrants_list.display_schedule_nonce, classID: classID},
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
        $(e.currentTarget).append('<div class="loading">Fetching registrants...</div>');
        const registrants = await fetch_registrants(classID);
        $(e.currentTarget).find('.loading').remove();
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

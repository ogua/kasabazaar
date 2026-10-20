$(function() {

    // Get the form.
    var form = $('#ajax-contact');

    // Get the messages div.
    var formMessages = $('#form-messages');

    // Pages without a form (most of the site) load this file too.
    if (!form.length) {
        return;
    }

    // The submit button's label differs per form — "Send Message", "Request a Quote",
    // "Send Feedback" — so stash whatever markup is actually there and restore it
    // afterwards, rather than hardcoding a label that would be wrong on two of three.
    var submitBtn  = form.find('[type="submit"]');
    var submitHtml = submitBtn.html();

    // True while a request is in flight, so a second submit can be ignored.
    var sending = false;

    // Announce the status changes to screen readers. Done here rather than in each
    // page's markup so any future form using this pattern is covered automatically.
    formMessages.attr({ role: 'status', 'aria-live': 'polite' });

    function startSending() {
        sending = true;
        submitBtn
            .prop('disabled', true)
            .attr('aria-busy', 'true')
            .html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Sending&hellip;');
        formMessages
            .removeClass('success error')
            .addClass('sending')
            .text('Sending your message, please wait\u2026');
    }

    function stopSending() {
        sending = false;
        submitBtn
            .prop('disabled', false)
            .removeAttr('aria-busy')
            .html(submitHtml);
        formMessages.removeClass('sending');
    }

    // Set up an event listener for the contact form.
    form.submit(function(e) {
        // Stop the browser from submitting the form.
        e.preventDefault();

        // Disabling the button stops a second click, but pressing Enter in a text
        // field still fires submit — so guard the handler itself, not just the button.
        if (sending) {
            return;
        }

        // Serialize the form data.
        var formData = form.serialize();

        startSending();

        // Submit the form using AJAX.
        $.ajax({
            type: 'POST',
            url: form.attr('action'),
            data: formData
        })
        .done(function(response) {
            // Make sure that the formMessages div has the 'success' class.
            formMessages.removeClass('error').addClass('success');

            // Set the message text.
            formMessages.text(response);

            // Clear every field, whichever form this was. Resetting the form element
            // beats listing field ids: a new form (or a new field on an existing one)
            // is covered automatically instead of silently keeping its values.
            form[0].reset();
        })
        .fail(function(data) {
            // Make sure that the formMessages div has the 'error' class.
            formMessages.removeClass('success').addClass('error');

            // Set the message text.
            if (data.responseText !== '') {
                formMessages.text(data.responseText);
            } else {
                formMessages.text('Oops! An error occurred and your message could not be sent.');
            }
        })
        // Runs after done/fail, so the button comes back whatever the outcome —
        // including a network drop, where neither branch sets a useful message.
        .always(stopSending);

    });

});

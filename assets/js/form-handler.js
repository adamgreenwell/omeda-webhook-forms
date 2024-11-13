// js/form-handler.js
jQuery(document).ready(function($) {
    $('.omeda-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var formId = $form.data('form-id');
        var formData = $form.serializeArray();

        // Add form ID to the data
        formData.push({
            name: 'form_id',
            value: formId
        });

        // Add action and nonce
        formData.push({
            name: 'action',
            value: 'submit_omeda_form'
        });
        formData.push({
            name: 'nonce',
            value: omedaAjax.nonce
        });

        $.ajax({
            url: omedaAjax.ajaxurl,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $form.find('button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $form.html('<p class="success-message">Thank you! Your form has been submitted successfully.</p>');
                } else {
                    $form.prepend('<p class="error-message">' + response.data + '</p>');
                    $form.find('button[type="submit"]').prop('disabled', false);
                }
            },
            error: function() {
                $form.prepend('<p class="error-message">An error occurred. Please try again later.</p>');
                $form.find('button[type="submit"]').prop('disabled', false);
            }
        });
    });
});
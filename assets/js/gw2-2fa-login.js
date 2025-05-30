/**
 * GW2 Guild Login - 2FA Login JavaScript
 */
(function($) {
    'use strict';

    // Toggle between verification code and backup code
    $(document).on('click', '#gw2-use-backup-code', function(e) {
        e.preventDefault();
        
        var $field = $('#gw2-2fa-code');
        var $label = $('label[for="gw2-2fa-code"]');
        var $button = $('#wp-submit');
        
        // Toggle to backup code
        if ($field.attr('data-type') !== 'backup') {
            $field.attr('data-type', 'backup')
                .attr('placeholder', gw22fa.i18n.enter_backup_code)
                .val('')
                .attr('maxlength', 12);
                
            $label.text(gw22fa.i18n.backup_code);
            $button.val(gw22fa.i18n.verify_backup_code);
            
            // Update the link to switch back
            $(this).text(gw22fa.i18n.use_verification_code);
        } 
        // Toggle back to verification code
        else {
            $field.removeAttr('data-type')
                .attr('placeholder', '')
                .val('')
                .attr('maxlength', 6);
                
            $label.text(gw22fa.i18n.verification_code);
            $button.val(gw22fa.i18n.verify);
            
            // Update the link to switch to backup code
            $(this).text(gw22fa.i18n.use_backup_code);
        }
        
        $field.focus();
    });

    // Auto-submit form when code is entered
    $(document).on('input', '#gw2-2fa-code', function() {
        var $code = $(this);
        var codeLength = $code.attr('data-type') === 'backup' ? 10 : 6;
        
        if ($code.val().length === codeLength) {
            $code.closest('form').submit();
        }
    });

    // Handle form submission
    $(document).on('submit', '#2faform', function() {
        var $form = $(this);
        var $button = $form.find('#wp-submit');
        var $code = $('#gw2-2fa-code');
        
        // Basic validation
        if (!$code.val().trim()) {
            showError(gw22fa.i18n.enter_code);
            $code.focus();
            return false;
        }
        
        // Show loading state
        $button.prop('disabled', true).addClass('button-disabled');
        $form.addClass('gw2-2fa-loading');
        
        // If we're on the login page, we need to handle the response manually
        if ($('body').hasClass('login')) {
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                dataType: 'html'
            })
            .done(function(response) {
                // If we get a redirect, follow it
                var redirectMatch = response.match(/<input[^>]*name="redirect_to"[^>]*value="([^"]*)"[^>]*>/i);
                if (redirectMatch && redirectMatch[1]) {
                    window.location.href = redirectMatch[1];
                    return;
                }
                
                // Otherwise, replace the form with the response
                var $newForm = $(response).find('#2faform');
                if ($newForm.length) {
                    $form.replaceWith($newForm);
                    showError(gw22fa.i18n.invalid_code);
                } else {
                    // If no form in response, assume success and reload
                    window.location.reload();
                }
            })
            .fail(function() {
                showError(gw22fa.i18n.network_error);
            })
            .always(function() {
                $button.prop('disabled', false).removeClass('button-disabled');
                $form.removeClass('gw2-2fa-loading');
            });
            
            return false;
        }
    });
    
    // Show error message
    function showError(message) {
        var $notice = $('<div>').addClass('gw2-2fa-error')
            .append($('<p>').text(message));
            
        // Remove any existing notices
        $('.gw2-2fa-error, .gw2-2fa-success').remove();
        
        // Add the new notice
        $('h1').after($notice.hide().fadeIn());
        
        // Scroll to the top of the form
        $('html, body').animate({
            scrollTop: $notice.offset().top - 100
        }, 200);
    }
    
    // Initialize on document ready
    $(function() {
        // Focus the code field when the page loads
        $('#gw2-2fa-code').focus();
        
        // If there's an error message, scroll to it
        if ($('.gw2-2fa-error, .gw2-2fa-success').length) {
            $('html, body').animate({
                scrollTop: $('.gw2-2fa-error, .gw2-2fa-success').offset().top - 100
            }, 200);
        }
    });

})(jQuery);

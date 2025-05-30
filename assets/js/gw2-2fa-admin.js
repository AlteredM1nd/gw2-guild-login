/**
 * GW2 Guild Login - 2FA Admin JavaScript
 */
(function($) {
    'use strict';

    // Toggle backup codes visibility
    $(document).on('click', '#gw2-show-backup-codes', function(e) {
        e.preventDefault();
        $('#gw2-backup-codes').slideToggle();
    });

    // Regenerate backup codes
    $(document).on('click', '#gw2-regenerate-codes', function(e) {
        e.preventDefault();
        
        if (!confirm(gw22fa.i18n.confirm_regenerate)) {
            return;
        }
        
        var $button = $(this);
        var $container = $('#gw2-backup-codes');
        
        $button.prop('disabled', true).addClass('updating-message');
        
        $.ajax({
            url: gw22fa.ajax_url,
            type: 'POST',
            data: {
                action: 'gw2_regenerate_backup_codes',
                nonce: gw22fa.nonce,
                user_id: gw22fa.user_id
            },
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success) {
                var $codesList = $container.find('ul');
                $codesList.empty();
                
                $.each(response.data.codes, function(i, code) {
                    $('<li>').text(code).appendTo($codesList);
                });
                
                // Show success message
                var $notice = $('<div>').addClass('notice notice-success')
                    .append($('<p>').text(gw22fa.i18n.codes_regenerated));
                
                $container.before($notice.hide().fadeIn());
                
                // Remove notice after 5 seconds
                setTimeout(function() {
                    $notice.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            } else {
                alert(response.data.message || gw22fa.i18n.error);
            }
        })
        .fail(function() {
            alert(gw22fa.i18n.error);
        })
        .always(function() {
            $button.prop('disabled', false).removeClass('updating-message');
        });
    });

    // Auto-submit 2FA setup form when code is entered
    $(document).on('input', '#gw2-verification-code', function() {
        var $code = $(this);
        if ($code.val().length === 6) {
            $code.closest('form').submit();
        }
    });

    // Initialize tooltips
    $(document).ready(function() {
        $('.gw2-tooltip').tooltip({
            position: {
                my: 'center bottom-10',
                at: 'center top',
                using: function(position, feedback) {
                    $(this).css(position);
                    $('<div>')
                        .addClass('arrow')
                        .addClass(feedback.vertical)
                        .addClass(feedback.horizontal)
                        .appendTo(this);
                }
            },
            tooltipClass: 'gw2-tooltip-content',
            show: { effect: 'fadeIn', duration: 200 },
            hide: { effect: 'fadeOut', duration: 200 }
        });
    });

})(jQuery);

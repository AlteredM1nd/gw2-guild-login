/**
 * GW2 Guild Login - Dashboard JavaScript
 *
 * @package GW2_Guild_Login
 * @since 2.4.0
 */

(function($) {
    'use strict';

    // Document ready
    $(document).ready(function() {
        // Toggle API key visibility
        $('#toggle-api-key').on('click', function(e) {
            e.preventDefault();
            var $input = $('#gw2_api_key');
            var $button = $(this);
            
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $button.text(gw2Dashboard.i18n.hide || 'Hide');
            } else {
                $input.attr('type', 'password');
                $button.text(gw2Dashboard.i18n.show || 'Show');
            }
        });

        // Copy API key to clipboard
        $('#copy-api-key').on('click', function(e) {
            e.preventDefault();
            var $input = $('#gw2_api_key');
            $input.attr('type', 'text').select();
            document.execCommand('copy');
            $input.attr('type', 'password');
            
            // Show copied tooltip
            var $tooltip = $('<span class="gw2-tooltip">' + (gw2Dashboard.i18n.copied || 'Copied!') + '</span>');
            $(this).after($tooltip);
            
            // Remove tooltip after delay
            setTimeout(function() {
                $tooltip.fadeOut(200, function() {
                    $(this).remove();
                });
            }, 1000);
        });

        // Refresh account data
        $('#refresh-account-data').on('click', function() {
            var $button = $(this);
            var $spinner = $('<span class="spinner is-active"></span>');
            
            $button.prop('disabled', true).prepend($spinner);
            
            $.ajax({
                url: gw2Dashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gw2_dashboard_action',
                    action_type: 'refresh_data',
                    nonce: gw2Dashboard.nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || gw2Dashboard.i18n.error);
                    }
                },
                error: function() {
                    alert(gw2Dashboard.i18n.error);
                },
                complete: function() {
                    $button.prop('disabled', false).find('.spinner').remove();
                }
            });
        });

        // Revoke session
        $('.gw2-revoke-session').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm(gw2Dashboard.i18n.confirmLogout)) {
                return;
            }
            
            var $row = $(this).closest('tr');
            var sessionId = $(this).data('session');
            
            $row.addClass('gw2-loading');
            
            $.ajax({
                url: gw2Dashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gw2_dashboard_action',
                    action_type: 'revoke_session',
                    session_id: sessionId,
                    nonce: gw2Dashboard.nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(200, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data || gw2Dashboard.i18n.error);
                        $row.removeClass('gw2-loading');
                    }
                },
                error: function() {
                    alert(gw2Dashboard.i18n.error);
                    $row.removeClass('gw2-loading');
                }
            });
        });

        // Revoke all other sessions
        $('#revoke-other-sessions').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm(gw2Dashboard.i18n.confirmLogoutAll)) {
                return;
            }
            
            var $button = $(this);
            var $spinner = $('<span class="spinner is-active"></span>');
            
            $button.prop('disabled', true).prepend($spinner);
            
            $.ajax({
                url: gw2Dashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'gw2_dashboard_action',
                    action_type: 'revoke_sessions',
                    nonce: gw2Dashboard.nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || gw2Dashboard.i18n.error);
                    }
                },
                error: function() {
                    alert(gw2Dashboard.i18n.error);
                },
                complete: function() {
                    $button.prop('disabled', false).find('.spinner').remove();
                }
            });
        });

        // Refresh page
        $('#refresh-page').on('click', function(e) {
            e.preventDefault();
            location.reload();
        });
    });

})(jQuery);

/**
 * GW2 Guild Login - Admin JavaScript
 */
(function($) {
    'use strict';

    // Document ready
    $(function() {
        // Toggle settings sections
        $('.gw2gl-toggle-section').on('click', function(e) {
            e.preventDefault();
            var $this = $(this);
            var $section = $($this.attr('href'));
            
            $section.slideToggle(200, function() {
                $this.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
                // Save the state in localStorage
                var isVisible = $section.is(':visible');
                localStorage.setItem('gw2gl_section_' + $section.attr('id'), isVisible);
            });
        });

        // Restore section visibility states
        $('.gw2gl-settings-section').each(function() {
            var sectionId = $(this).attr('id');
            var isVisible = localStorage.getItem('gw2gl_section_' + sectionId);
            
            if (isVisible === 'false') {
                $(this).hide();
                $('a[href="#' + sectionId + '"]').addClass('dashicons-arrow-up-alt2').removeClass('dashicons-arrow-down-alt2');
            }
        });

        // Confirm before resetting settings
        $('.gw2gl-reset-settings').on('click', function(e) {
            if (!confirm(gw2gl_admin.i18n.confirm_reset)) {
                e.preventDefault();
                return false;
            }
            return true;
        });

        // Test API connection
        $('.gw2gl-test-api').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $spinner = $button.next('.spinner');
            var originalText = $button.text();
            
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            
            // Get the API key from the form
            var apiKey = $('input[name="gw2gl_settings[api_key]"]').val();
            
            $.ajax({
                url: gw2gl_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'gw2gl_test_api_connection',
                    nonce: gw2gl_admin.nonce,
                    api_key: apiKey
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('API connection successful! ' + response.data.message);
                    } else {
                        alert('API connection failed: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred while testing the API connection: ' + error);
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                    $spinner.removeClass('is-active');
                }
            });
        });

        // Toggle API key visibility
        $('.gw2gl-toggle-api-key').on('click', function(e) {
            e.preventDefault();
            var $input = $(this).siblings('input');
            var type = $input.attr('type') === 'password' ? 'text' : 'password';
            $input.attr('type', type);
            $(this).find('span')
                .toggleClass('dashicons-visibility')
                .toggleClass('dashicons-hidden');
        });

        // Handle form submission feedback
        $('form').on('submit', function() {
            var $submit = $(this).find('input[type="submit"]');
            var originalText = $submit.val();
            
            $submit.prop('disabled', true).val(gw2gl_admin.i18n.saving);
            
            // Re-enable after a short delay in case of validation errors
            setTimeout(function() {
                $submit.prop('disabled', false).val(originalText);
            }, 3000);
        });

        // Show success message after settings save
        if (window.location.search.indexOf('settings-updated') > -1) {
            $('.wrap h1').after(
                '<div class="notice notice-success is-dismissible"><p>' + 
                gw2gl_admin.i18n.saved + '</p></div>'
            );
        }
    });

})(jQuery);

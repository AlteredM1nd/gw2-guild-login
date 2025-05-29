/**
 * GW2 Guild Login - Frontend Scripts
 */
( function( $ ) {
    'use strict';

    // Document ready
    $( document ).ready( function() {
        // Initialize form validation
        initFormValidation();
        
        // Initialize tooltips
        initTooltips();
        
        // Handle form submission
        handleFormSubmission();
        
        // Initialize password toggle
        initPasswordToggle();
    } );

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        const $form = $( '#gw2-login-form' );
        
        if ( ! $form.length ) {
            return;
        }

        // Add validation for API key format
        $.validator.addMethod( 'gw2ApiKey', function( value, element ) {
            // Format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxxxxxxxxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
            const apiKeyRegex = /^[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{20}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}$/i;
            return this.optional( element ) || apiKeyRegex.test( value );
        }, gw2LoginVars.i18n.invalid_api_key );

        // Initialize validation
        $form.validate( {
            rules: {
                gw2_api_key: {
                    required: true,
                    gw2ApiKey: true
                }
            },
            messages: {
                gw2_api_key: {
                    required: gw2LoginVars.i18n.required_field
                }
            },
            errorElement: 'span',
            errorClass: 'invalid-feedback',
            highlight: function( element ) {
                $( element ).addClass( 'is-invalid' );
            },
            unhighlight: function( element ) {
                $( element ).removeClass( 'is-invalid' );
            },
            errorPlacement: function( error, element ) {
                error.addClass( 'd-block' );
                error.insertAfter( element );
            }
        } );
    }


    /**
     * Initialize tooltips
     */
    function initTooltips() {
        $( '.gw2-tooltip' ).tooltip( {
            trigger: 'hover',
            placement: 'top',
            container: 'body'
        } );
    }

    /**
     * Handle form submission
     */
    function handleFormSubmission() {
        const $form = $( '#gw2-login-form' );
        
        if ( ! $form.length ) {
            return;
        }

        $form.on( 'submit', function( e ) {
            // Prevent default form submission
            e.preventDefault();
            
            // Validate form
            if ( ! $form.valid() ) {
                return;
            }

            // Disable submit button and show loading state
            const $submitButton = $form.find( '[type="submit"]' );
            const originalButtonText = $submitButton.html();
            
            $submitButton.prop( 'disabled', true );
            $submitButton.html( '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + gw2LoginVars.i18n.processing );
            
            // Get form data
            const formData = $form.serialize();
            
            // Submit form via AJAX
            $.ajax( {
                type: 'POST',
                url: gw2LoginVars.ajaxurl,
                data: formData + '&action=gw2_login',
                dataType: 'json',
                success: function( response ) {
                    if ( response.success ) {
                        // Redirect on success
                        if ( response.data.redirect ) {
                            window.location.href = response.data.redirect;
                        } else {
                            window.location.reload();
                        }
                    } else {
                        // Show error message
                        showAlert( response.data.message || gw2LoginVars.i18n.generic_error, 'danger' );
                        
                        // Re-enable submit button
                        $submitButton.prop( 'disabled', false ).html( originalButtonText );
                    }
                },
                error: function( xhr, status, error ) {
                    // Show error message
                    showAlert( gw2LoginVars.i18n.connection_error, 'danger' );
                    
                    // Re-enable submit button
                    $submitButton.prop( 'disabled', false ).html( originalButtonText );
                    
                    // Log error to console
                    console.error( 'AJAX Error:', status, error );
                }
            } );
        } );
    }

    /**
     * Show alert message
     * 
     * @param {string} message The message to display
     * @param {string} type    The alert type (success, danger, warning, info)
     */
    function showAlert( message, type = 'info' ) {
        // Remove any existing alerts
        $( '.gw2-alert' ).remove();
        
        // Create alert element
        const $alert = $( '<div>', {
            'class': 'alert alert-' + type + ' alert-dismissible fade show gw2-alert',
            'role': 'alert',
            'html': message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="' + gw2LoginVars.i18n.close + '"></button>'
        } );
        
        // Add alert to form
        $( '#gw2-login-form' ).prepend( $alert );
        
        // Auto-dismiss after 5 seconds
        setTimeout( function() {
            $alert.alert( 'close' );
        }, 5000 );
    }

    /**
     * Initialize password toggle
     */
    function initPasswordToggle() {
        $( document ).on( 'click', '.toggle-password', function() {
            const $input = $( this ).siblings( 'input' );
            const type = $input.attr( 'type' ) === 'password' ? 'text' : 'password';
            $input.attr( 'type', type );
            
            // Toggle icon
            $( this ).find( 'i' ).toggleClass( 'fa-eye fa-eye-slash' );
        } );
    }

} )( jQuery );

// Polyfill for Element.matches()
if ( ! Element.prototype.matches ) {
    Element.prototype.matches = 
        Element.prototype.matchesSelector || 
        Element.prototype.mozMatchesSelector ||
        Element.prototype.msMatchesSelector || 
        Element.prototype.oMatchesSelector || 
        Element.prototype.webkitMatchesSelector ||
        function( s ) {
            const matches = ( this.document || this.ownerDocument ).querySelectorAll( s );
            let i = matches.length;
            while ( --i >= 0 && matches.item( i ) !== this ) {}
            return i > -1;            
        };
}

# Two-Factor Authentication (2FA)

_Last audited: 2025-05-31_

## Overview

GW2 Guild Login 2.4.1 provides robust Two-Factor Authentication (2FA) for user accounts, with a modern UI, backup codes, AJAX admin controls, and comprehensive I18n and output escaping for all user-facing strings. All 2FA features follow the latest WordPress security best practices.

#### 2.4.1 Update
- All 2FA logic and AJAX is now handled by dedicated classes (OOP).
- Main plugin file is now a clean bootstrap with no procedural code.
- Security and extensibility improved.

For a summary of recent security and I18n improvements, see [SECURITY.md](../SECURITY.md).

## Table of Contents

1. [Current Features](#current-features)
2. [Planned Features](#planned-features)
3. [Enabling 2FA](#enabling-2fa)
4. [Using 2FA](#using-2fa)
5. [Managing Backup Codes](#managing-backup-codes)
6. [Trusted Devices](#trusted-devices)
7. [Disabling 2FA](#disabling-2fa)
8. [Troubleshooting](#troubleshooting)
9. [For Developers](#for-developers)

## Current Features (Phase 1)

- **TOTP Support**: Implements the Time-based One-Time Password algorithm (RFC 6238)
  - Fixed 6-digit codes, 30-second time step
  - Compatible with any TOTP-compatible authenticator app
- **Backup Codes**: Secure, one-time use backup codes (hashed in storage)
- **Trusted Devices**: Cookie-based device remembering (30 days, configurable)
- **Admin Controls**: Enable/disable 2FA for users, regenerate backup codes via AJAX
- **Modern UI/UX**: User-friendly profile integration and dashboard
- **I18n & Output Escaping**: All strings are translation-ready and properly escaped

## Planned Features

### Phase 2 (Next Update)
- Enhanced trusted device management interface
- More detailed session information
- Configurable code length and time step
- Improved setup wizard

### Phase 3 (Future)
- Additional authentication methods (SMS, Email, etc.)
- Advanced admin controls
- Bulk user management
- Detailed activity logs

## Enabling 2FA

### For Users

1. Log in to your WordPress account
2. Go to your Profile page
3. Scroll down to the "Two-Factor Authentication" section
4. Click "Enable Two-Factor Authentication"
5. Scan the QR code with your authenticator app
6. Enter the 6-digit verification code from your app
7. Save your backup codes in a secure location
8. Click "Confirm and Enable"

### For Administrators

Administrators can enable or disable 2FA for user accounts:

1. Go to Users > All Users
2. Click "Edit" next to the user
3. Scroll down to the "Two-Factor Authentication" section
4. Toggle 2FA status as needed

*Note: In this initial release, administrators cannot view or manage a user's 2FA settings beyond enabling/disabling the feature.*

## Using 2FA

After enabling 2FA, you'll be prompted to enter a verification code when logging in from an untrusted device. The process is simple:

1. Enter your username and password as usual
2. When prompted, enter the 6-digit code from your authenticator app
3. Optionally, check "Remember this device for 30 days" to skip 2FA on this device
4. Click "Verify" to complete the login

## Managing Backup Codes

Backup codes are automatically generated when you enable 2FA. These one-time use codes can be used if you don't have access to your authenticator app.

### Viewing Backup Codes

1. Go to your Profile page
2. Scroll to the "Two-Factor Authentication" section
3. Click "View Backup Codes"
4. Copy or print the codes and store them securely

### Regenerating Backup Codes

If you've used most of your backup codes or suspect they've been compromised:

- Users: Go to your Profile or Dashboard, scroll to the "Two-Factor Authentication" section, and click "Generate New Backup Codes". Codes are securely regenerated via AJAX and old codes are invalidated.
- Admins: You can trigger backup code regeneration for users via the admin dashboard (requires proper capability).

## Trusted Devices (Basic Implementation)

When logging in with 2FA, you can choose to trust the device for 30 days. This will remember your device and not require 2FA for subsequent logins during this period.

*Note: In this initial release, the trusted devices feature is basic and does not include a management interface. To remove all trusted devices, you can clear your browser cookies or wait for the 30-day period to expire.*

## Disabling 2FA

If you need to disable 2FA for your account:

1. Go to your Profile page
2. Scroll to the "Two-Factor Authentication" section
3. Click "Disable Two-Factor Authentication"
4. Confirm your decision

## Troubleshooting

### I Lost My Phone/2FA Device

If you've lost access to your authenticator app but have your backup codes:

1. Log in using one of your backup codes
2. Go to your Profile or Dashboard
3. Set up 2FA again with a new device

### I Lost My Backup Codes

If you've lost your backup codes but still have access to your authenticator app:

1. Log in using your authenticator app
2. Go to your Profile or Dashboard
3. Generate new backup codes (regeneration is secure and old codes are invalidated)
4. Save the new codes in a secure location

### All 2FA/Backup Access Lost

If you've lost both your authenticator and backup codes, contact your site administrator for recovery (admins can reset 2FA for users with proper capability).

### QR Code Won't Scan

If you're having trouble scanning the QR code:

1. Make sure your camera is clean and has good lighting
2. Try moving closer or further from the screen
3. If scanning still fails, click "Can't scan the QR code?"
4. Manually enter the setup key into your authenticator app

## For Developers

### Hooks and Filters

All 2FA actions and filters are fully documented and follow WordPress coding standards. All hook parameters are sanitized and escaped as needed.

#### Actions

- `gw2_2fa_before_enable` - Fires before 2FA is enabled for a user
- `gw2_2fa_after_enable` - Fires after 2FA is enabled for a user
- `gw2_2fa_before_disable` - Fires before 2FA is disabled for a user
- `gw2_2fa_after_disable` - Fires after 2FA is disabled for a user

#### Filters

- `gw2_2fa_issuer_name` - Filter the issuer name shown in authenticator apps (default: blog name)
- `gw2_2fa_code_length` - Filter the length of 2FA codes (default: 6)
- `gw2_2fa_time_step` - Filter the time step for TOTP codes (default: 30)
- `gw2_2fa_trust_period` - Filter trust duration for devices (default: 30 days)

### Extending 2FA

You can extend the 2FA system to add new authentication methods (e.g., SMS, email) using documented hooks. See the planned API in this file for future releases.

For secure coding guidelines, see [CONTRIBUTING.md](../CONTRIBUTING.md).

#### Planned Customization Options

1. **Change the Issuer Name** (Coming in Phase 2)
   ```php
   // Planned for Phase 2
   add_filter('gw2_2fa_issuer_name', function($issuer_name) {
       return 'My Custom Site Name';
   });
   ```

2. **Change Code Length** (Coming in Phase 2)
   ```php
   // Planned for Phase 2
   add_filter('gw2_2fa_code_length', function($length) {
       return 8; // Will support 8-digit codes in the future
   });
   ```

3. **Change Time Step** (Coming in Phase 2)
   ```php
   // Planned for Phase 2
   add_filter('gw2_2fa_time_step', function($time_step) {
       return 60; // Will support 60-second time steps in the future
   });
   ```

4. **Change Trust Period** (Coming in Phase 2)
   ```php
   // Planned for Phase 2
   add_filter('gw2_2fa_trust_period', function($days) {
       return 7; // Will support custom trust periods in the future
   });
   ```

### Adding Custom 2FA Methods (Planned for Phase 3)

In future updates, you'll be able to extend the plugin to support additional 2FA methods. Here's a preview of the planned API for adding SMS-based 2FA (subject to change):

```php
/**
 * Add SMS as a 2FA method
 */
function my_custom_2fa_method($methods) {
    $methods['sms'] = [
        'label' => __('SMS', 'my-text-domain'),
        'setup_callback' => 'my_sms_setup_callback',
        'verify_callback' => 'my_sms_verify_callback',
        'admin_setting' => true,
    ];
    return $methods;
}
add_filter('gw2_2fa_methods', 'my_custom_2fa_method');

/**
 * Setup callback for SMS 2FA
 */
function my_sms_setup_callback($user_id) {
    // Generate and send verification code via SMS
    $code = wp_generate_password(6, false, false);
    
    // Store the code in user meta (in a real implementation, you'd want to hash this)
    update_user_meta($user_id, '_gw2_2fa_sms_code', $code);
    
    // In a real implementation, you would send the code via SMS here
    // This is just an example
    $phone_number = get_user_meta($user_id, 'phone_number', true);
    // send_sms($phone_number, "Your verification code is: $code");
    
    // Return the fields to display to the user
    return [
        [
            'id' => 'sms_code',
            'label' => __('Verification Code', 'my-text-domain'),
            'type' => 'text',
            'description' => __('Enter the 6-digit code sent to your phone', 'my-text-domain'),
            'required' => true,
        ],
    ];
}

/**
 * Verification callback for SMS 2FA
 */
function my_sms_verify_callback($user_id, $data) {
    // Get the stored code
    $stored_code = get_user_meta($user_id, '_gw2_2fa_sms_code', true);
    
    // Verify the code
    if (isset($data['sms_code']) && $data['sms_code'] === $stored_code) {
        // Code is valid, clean up
        delete_user_meta($user_id, '_gw2_2fa_sms_code');
        return true;
    }
    
    return false;
}
```

<?php
namespace GW2GuildLogin;

use PragmaRX\Google2FA\Google2FA;

/**
 * Handles Two-Factor Authentication functionality
 */
class GW2_2FA_Handler {
    /** @var self|null Singleton instance */
    private static $instance = null;
    
    /** @var string TOTP secret */
    private $secret = '';
    
    /** @var string Encryption key */
    private $encryption_key = '';
    
    /** @var string Secrets table name */
    private $table_secrets;
    
    /** @var string Trusted devices table name */
    private $table_devices;

    /**
     * Get the singleton instance
     * 
     * @return self
     */
    /**
     * Get the singleton instance
     * @param bool $skip_wpdb For unit testing only; skips $wpdb setup
     * @return self
     */
    public static function instance($skip_wpdb = false) {
        if (null === self::$instance) {
            self::$instance = new self($skip_wpdb);
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct($skip_wpdb = false) {
        if (!$skip_wpdb) {
            global $wpdb;
            $this->table_secrets = $wpdb ? $wpdb->prefix . 'gw2_2fa_secrets' : '';
            $this->table_devices = $wpdb ? $wpdb->prefix . 'gw2_2fa_trusted_devices' : '';
        }
        // Set up encryption key
        $this->encryption_key = $this->get_encryption_key();
    }

    /**
     * Check if 2FA is enabled for a user
     * 
     * @param int $user_id
     * @return bool
     */
    public function is_2fa_enabled($user_id) {
        global $wpdb;
        $enabled = $wpdb->get_var($wpdb->prepare(
            "SELECT is_enabled FROM {$this->table_secrets} WHERE user_id = %d",
            $user_id
        ));
        return (bool) $enabled;
    }

    /**
     * Generate a new TOTP secret
     * 
     * @return string
     */
    public function generate_secret() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= substr($chars, wp_rand(0, strlen($chars) - 1), 1);
        }
        return $secret;
    }

    /**
     * Generate backup codes for 2FA
     * 
     * @param int $count Number of codes to generate (default: 10)
     * @param int $length Length of each code (default: 8)
     * @return array Array of generated backup codes
     */
    public function generate_backup_codes($count = 10, $length = 8) {
        $codes = [];
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $chars_length = strlen($chars);
        
        for ($i = 0; $i < $count; $i++) {
            $code = '';
            for ($j = 0; $j < $length; $j++) {
                $code .= $chars[wp_rand(0, $chars_length - 1)];
            }
            // Format the code with hyphens for better readability (e.g., XXXX-XXXX)
            $formatted_code = substr($code, 0, 4) . '-' . substr($code, 4);
            $codes[] = $formatted_code;
        }
        
        return $codes;
    }

    /**
     * Verify a TOTP code
     * 
     * @param string $secret The TOTP secret
     * @param string $code The code to verify
     * @param int $window Time window in 30-second steps (default: 1)
     * @return bool|\WP_Error True if valid, false if invalid, WP_Error on failure
     */
    public function verify_totp($secret, $code, $window = 1) {
        $google2fa = $this->get_google2fa_instance();
        // Handle WP_Error
        if (is_wp_error($google2fa)) {
            return $google2fa;
        }
        try {
            // Google2FA::verifyKey returns boolean
            return (bool) $google2fa->verifyKey($secret, $code, $window);
        } catch (\Exception $e) {
            return false; // Invalid code
        }
    }
    
    /**
     * Get Google2FA instance
     *
     * @return Google2FA|\WP_Error Google2FA instance or WP_Error on failure
     */
    private function get_google2fa_instance() {
        if (!class_exists('PragmaRX\\Google2FA\\Google2FA')) {
            return new \WP_Error(
                '2fa_error',
                __('Two-factor authentication library not found. Please install the required dependencies.', 'gw2-guild-login')
            );
        }
        try {
            return new Google2FA();
        } catch (\Exception $e) {
            return new \WP_Error(
                '2fa_error',
                sprintf(__('Failed to initialize two-factor authentication: %s', 'gw2-guild-login'), $e->getMessage())
            );
        }
    }

    /**
     * Get the QR code URL for setting up an authenticator app
     * 
     * @param string $secret The TOTP secret
     * @param string $username The username
     * @param string $issuer The issuer name (default: 'GW2 Guild Login')
     * @return string QR code URL or empty string on failure
     */
    public function get_qr_code_url($secret, $username, $issuer = 'GW2 Guild Login') {
    // Build the otpauth URI for Google Authenticator
    $otpauth = sprintf(
        'otpauth://totp/%s:%s?secret=%s&issuer=%s',
        rawurlencode($issuer),
        rawurlencode($username),
        rawurlencode($secret),
        rawurlencode($issuer)
    );
    // Use Google Charts API to generate a QR code
    $qr_url = sprintf(
        'https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=%s',
        rawurlencode($otpauth)
    );
    return $qr_url;
}

    /**
     * Enable 2FA for a user
     * 
     * @param int $user_id
     * @param string $secret
     * @param array $backup_codes
     * @return bool|\WP_Error
     */
    public function enable_2fa($user_id, $secret, $backup_codes) {
        global $wpdb;

        // Encrypt the secret before storing
        $encrypted_secret = $this->encrypt_secret($secret);
        if (is_wp_error($encrypted_secret)) {
            return $encrypted_secret;
        }
        $encrypted_codes = $this->encrypt_backup_codes($backup_codes);
        if (is_wp_error($encrypted_codes)) {
            return $encrypted_codes;
        }

        // Check if 2FA is already enabled
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$this->table_secrets} WHERE user_id = %d",
            $user_id
        ));

        $data = [
            'user_id' => $user_id,
            'secret' => $encrypted_secret,
            'backup_codes' => $encrypted_codes,
            'is_enabled' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        if ($existing) {
            $wpdb->update($this->table_secrets, $data, ['id' => $existing->id]);
        } else {
            $wpdb->insert($this->table_secrets, $data);
        }

        // Store backup codes in user meta (encrypted)
        $set_result = $this->set_backup_codes_for_user($user_id, $backup_codes);
        if (is_wp_error($set_result)) {
            return $set_result;
        }

        return true;
    }

    /**
     * Disable 2FA for a user
     * 
     * @param int $user_id
     * @return bool|\WP_Error
     */
    public function disable_2fa($user_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_secrets,
            ['user_id' => $user_id],
            ['%d']
        );

        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to disable 2FA');
        }

        // Clean up related data
        delete_user_meta($user_id, 'gw2_2fa_backup_codes');
        $wpdb->delete(
            $this->table_devices,
            ['user_id' => $user_id],
            ['%d']
        );

        return true;
    }

    /**
     * Encrypt a secret before storing it in the database
     * 
     * @param string $secret
     * @return string
     */
    /**
     * Encrypt a secret before storing it in the database
     *
     * @param string $secret
     * @return string|\WP_Error
     */
    public function encrypt_secret($secret) {
        if (!extension_loaded('openssl')) {
            return new \WP_Error(
                '2fa_encryption_error',
                __('OpenSSL PHP extension is required for 2FA encryption. Please contact the site administrator.', 'gw2-guild-login')
            );
        }
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($secret, 'aes-256-cbc', $this->encryption_key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt a secret from the database
     * 
     * @param string $encrypted_secret
     * @return string|false The decrypted secret or false on failure
     */
    /**
     * Decrypt a secret from the database
     *
     * @param string $encrypted_secret
     * @return string|false|\WP_Error The decrypted secret, false on failure, or WP_Error if OpenSSL is missing
     */
    public function decrypt_secret($encrypted_secret) {
        if (!extension_loaded('openssl')) {
            return new \WP_Error(
                '2fa_encryption_error',
                __('OpenSSL PHP extension is required for 2FA decryption. Please contact the site administrator.', 'gw2-guild-login')
            );
        }
        $data = base64_decode($encrypted_secret);
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        return openssl_decrypt($encrypted, 'aes-256-cbc', $this->encryption_key, 0, $iv);
    }

    /**
     * Encrypt backup codes before storage
     * 
     * @param array $codes
     * @return string
     */
    /**
     * Encrypt backup codes before storage
     *
     * @param array $codes
     * @return string|\WP_Error
     */
    private function encrypt_backup_codes($codes) {
        $result = $this->encrypt_secret(implode(',', $codes));
        if (is_wp_error($result)) {
            return $result;
        }
        return $result;
    }

    /**
     * Store encrypted backup codes in user meta
     *
     * @param int $user_id
     * @param array $codes
     */
    public function set_backup_codes_for_user($user_id, $codes) {
        $encrypted = $this->encrypt_backup_codes($codes);
        if (is_wp_error($encrypted)) {
            // Optionally: log error or notify admin
            return $encrypted;
        }
        update_user_meta($user_id, 'gw2_2fa_backup_codes', $encrypted);
        return true;
    }

    /**
     * Retrieve and decrypt backup codes from user meta
     *
     * @param int $user_id
     * @return array
     */
    public function get_backup_codes_for_user($user_id) {
        $encrypted = get_user_meta($user_id, 'gw2_2fa_backup_codes', true);
        if (empty($encrypted)) {
            return [];
        }
        $decrypted = $this->decrypt_secret($encrypted);
        if (is_wp_error($decrypted) || !is_string($decrypted) || $decrypted === false) {
            return [];
        }
        // Backup codes are stored as comma-separated string
        $codes = array_map('trim', explode(',', $decrypted));
        // Remove empty entries (in case of trailing commas)
        return array_filter($codes, 'strlen');
    }

    /**
     * Get the encryption key
     * 
     * @return string
     */
    private function get_encryption_key() {
        $key = defined('LOGGED_IN_KEY') ? LOGGED_IN_KEY : 'default-key';
        $key = substr(hash('sha256', $key), 0, 32);
        return $key;
    }
}



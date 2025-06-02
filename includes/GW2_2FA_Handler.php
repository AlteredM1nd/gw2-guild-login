<?php
declare(strict_types=1);
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
    public static function instance(bool $skip_wpdb = false): self {
        if (null === self::$instance) {
            self::$instance = new self($skip_wpdb);
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct(bool $skip_wpdb = false) {
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
    public function is_2fa_enabled(int $user_id): bool {
        global $wpdb;
        $enabled = is_object($wpdb) && method_exists($wpdb, 'get_var') && method_exists($wpdb, 'prepare')
            ? $wpdb->get_var($wpdb->prepare(
                "SELECT is_enabled FROM {$this->table_secrets} WHERE user_id = %d",
                $user_id
            ))
            : null;
        return (bool) $enabled;
    }

    /**
     * Generate a new TOTP secret
     * 
     * @return string
     */
    public function generate_secret(): string {
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
    public function generate_backup_codes(int $count = 10, int $length = 8): array {
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
    public function verify_totp(string $secret, string $code, int $window = 1): bool|\WP_Error {
        $google2fa = $this->get_google2fa_instance();
        if (is_wp_error($google2fa)) {
            return $google2fa;
        }
        if (!is_string($secret) || !is_string($code) || !is_int($window)) {
            return false;
        }
        try {
            return (bool) $google2fa->verifyKey($secret, $code, $window);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get Google2FA instance
     *
     * @return Google2FA|\WP_Error Google2FA instance or WP_Error on failure
     */
    private function get_google2fa_instance(): Google2FA|\WP_Error {
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
    public function get_qr_code_url(string $secret, string $username, string $issuer = 'GW2 Guild Login'): string {
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
    public function enable_2fa(int $user_id, string $secret, array $backup_codes): bool|\WP_Error {
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
     * @return string|\WP_Error
     */
    public function encrypt_secret(string $secret): string|\WP_Error {
        if (!extension_loaded('openssl')) {
            return new \WP_Error(
                '2fa_encryption_error',
                __('OpenSSL PHP extension is required for 2FA encryption. Please contact the site administrator.', 'gw2-guild-login')
            );
        }
        if (!is_string($secret) || $secret === '') {
            return new \WP_Error('2fa_encryption_error', __('Secret must be a non-empty string.', 'gw2-guild-login'));
        }
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted = openssl_encrypt($secret, 'aes-256-cbc', $this->encryption_key, 0, $iv);
        if ($encrypted === false) {
            return new \WP_Error('2fa_encryption_error', __('Encryption failed.', 'gw2-guild-login'));
        }
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt a secret from the database
     *
     * @param string $encrypted_secret
     * @return string|false|\WP_Error The decrypted secret, false on failure, or WP_Error if OpenSSL is missing
     */
    public function decrypt_secret(string $encrypted_secret): string|false|\WP_Error {
        if (!extension_loaded('openssl')) {
            return new \WP_Error(
                '2fa_encryption_error',
                __('OpenSSL PHP extension is required for 2FA decryption. Please contact the site administrator.', 'gw2-guild-login')
            );
        }
        if (!is_string($encrypted_secret) || $encrypted_secret === '') {
            return false;
        }
        $data = base64_decode($encrypted_secret, true);
        if ($data === false) {
            return false;
        }
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        if (!is_string($iv) || !is_string($encrypted) || $iv === '' || $encrypted === '') {
            return false;
        }
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $this->encryption_key, 0, $iv);
        return is_string($decrypted) ? $decrypted : false;
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
    private function encrypt_backup_codes(array $backup_codes): string|\WP_Error {
        $result = $this->encrypt_secret(implode(',', $backup_codes));
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
            return $encrypted;
        }
        if (!is_int($user_id) || $user_id <= 0 || !is_array($codes)) {
            return new \WP_Error('2fa_backup_codes_error', __('Invalid user ID or codes.', 'gw2-guild-login'));
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
        $encrypted_str = is_string($encrypted) ? $encrypted : '';
        if ($encrypted_str === '') {
            return [];
        }
        $decrypted = $this->decrypt_secret($encrypted_str);
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



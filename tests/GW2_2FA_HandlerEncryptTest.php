<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PHPUnit\Framework\TestCase;
use GW2GuildLogin\GW2_2FA_Handler;

class GW2_2FA_HandlerEncryptTest extends TestCase {

	public function test_encrypt_and_decrypt_secret_roundtrip(): void {
		$handler   = GW2_2FA_Handler::instance( true );
		$secret    = 'TESTSECRET123456';
		$encrypted = $handler->encrypt_secret( $secret );
		$this->assertIsString( $encrypted, 'Encrypted secret should be a string' );
		$decrypted = $handler->decrypt_secret( $encrypted );
		$this->assertEquals( $secret, $decrypted, 'Decrypted secret should match original' );
	}

	public function test_encrypt_secret_requires_openssl(): void {
		if ( ! extension_loaded( 'openssl' ) ) {
			$this->markTestSkipped( 'OpenSSL extension not available' );
		}
		$handler   = GW2_2FA_Handler::instance( true );
		$secret    = 'SOMESECRET';
		$encrypted = $handler->encrypt_secret( $secret );
		$this->assertIsString( $encrypted );
	}
}

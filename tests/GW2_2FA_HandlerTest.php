<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PHPUnit\Framework\TestCase;
use GW2GuildLogin\GW2_2FA_Handler;

class GW2_2FA_HandlerTest extends TestCase
{
    public function test_generate_secret_returns_16_char_base32()
    {
        $handler = GW2_2FA_Handler::instance(true);
        $secret = $handler->generate_secret();
        $this->assertEquals(16, strlen($secret), 'Secret should be 16 characters');
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{16}$/', $secret, 'Secret should be base32 (A-Z, 2-7)');
    }

    public function test_generate_backup_codes_returns_array_of_codes()
    {
        $handler = GW2_2FA_Handler::instance(true);
        $codes = $handler->generate_backup_codes(5, 8);
        $this->assertCount(5, $codes, 'Should generate 5 codes');
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code, 'Each code should be in XXXX-XXXX format');
        }
    }
}

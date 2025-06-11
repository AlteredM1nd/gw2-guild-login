<?php
/**
 * GW2 2FA Handler (Legacy).
 *
 * @package GW2_Guild_Login
 * @since 1.0.0
 */

declare(strict_types=1);

/**
 * Legacy 2FA Handler class - now obsolete.
 */
class GW2_2FA_Handler {
	/**
	 * Magic method to handle calls to undefined methods.
	 *
	 * @param string       $name      The method name.
	 * @param array<mixed> $arguments The method arguments.
	 * @return void
	 * @throws \Exception Always throws exception.
	 */
	public function __call( string $name, array $arguments ): void {
		throw new \Exception( 'GW2_2FA_Handler (legacy) is obsolete. Use GW2GuildLogin\\GW2_2FA_Handler.' );
	}
}

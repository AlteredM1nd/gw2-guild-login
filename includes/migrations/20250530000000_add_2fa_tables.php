<?php
/**
 * Migration for adding 2FA tables
 *
 * This migration is designed to be used with Phinx for database migrations.
 * The actual table creation is handled by the plugin's activation hook.
 */

// Only define the class if it hasn't been defined by Phinx
if ( ! class_exists( 'Add2FATables', false ) ) {
	/**
	 * Class Add2FATables
	 *
	 * This is a placeholder class that will be extended by Phinx if available.
	 * The actual table creation is handled by the plugin's activation hook.
	 */
	class Add2FATables {
		/**
		 * Change method for Phinx migrations
		 *
		 * @return void
		 */
		public function change() {
			// This is a no-op as the tables are created by the plugin's activation hook
		}
	}
}

<?php
/**
 * Plugin activation.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Activation tasks.
 */
final class ABIWIG_Activator {

	/**
	 * Activate the plugin.
	 *
	 * @return void
	 */
	public static function activate() {
		ABIWIG_Settings::install_defaults();
		ABIWIG_Invoice_Repository::register_post_type();
		update_option( 'abiwig_version', ABIWIG_VERSION, false );
		delete_option( 'abiwig_invoice_number_lock' );

		/** Fires after ABill Invoice Generator activation. */
		do_action( 'abiwig_activated' );
	}
}

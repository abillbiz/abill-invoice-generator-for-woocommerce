<?php
/**
 * Plugin deactivation.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Deactivation tasks.
 */
final class ABIWIG_Deactivator {

	/**
	 * Deactivate the plugin without deleting business records.
	 *
	 * @return void
	 */
	public static function deactivate() {
		delete_option( 'abiwig_invoice_number_lock' );
		delete_transient( 'abiwig_activation_notice' );

		/** Fires after ABill Invoice Generator deactivation. */
		do_action( 'abiwig_deactivated' );
	}
}

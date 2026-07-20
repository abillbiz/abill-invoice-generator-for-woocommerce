<?php
/**
 * Uninstall ABill Invoice Generator for WooCommerce.
 *
 * Business invoice records are preserved by default. Data is deleted only
 * when the administrator has explicitly enabled the delete-on-uninstall option.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Delete plugin data for the current site.
 *
 * @return void
 */
function abiwig_delete_site_data() {
	$abiwig_delete_data = get_option( 'abiwig_delete_data_on_uninstall', 'no' );

	if ( 'yes' !== $abiwig_delete_data ) {
		return;
	}

	$abiwig_invoice_ids = get_posts(
		array(
			'post_type'              => 'abiwig_invoice',
			'post_status'            => 'any',
			'numberposts'            => -1,
			'fields'                 => 'ids',
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'cache_results'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	foreach ( $abiwig_invoice_ids as $abiwig_invoice_id ) {
		wp_delete_post( (int) $abiwig_invoice_id, true );
	}

	delete_option( 'abiwig_version' );
	delete_option( 'abiwig_settings' );
	delete_option( 'abiwig_next_invoice_number' );
	delete_option( 'abiwig_delete_data_on_uninstall' );
}

if ( is_multisite() ) {
	$abiwig_site_ids = get_sites( array( 'fields' => 'ids' ) );

	foreach ( $abiwig_site_ids as $abiwig_site_id ) {
		switch_to_blog( (int) $abiwig_site_id );
		abiwig_delete_site_data();
		restore_current_blog();
	}
} else {
	abiwig_delete_site_data();
}

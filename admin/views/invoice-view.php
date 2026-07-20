<?php
/**
 * Invoice view screen.
 *
 * Expected variables: $abiwig_invoice, $abiwig_notice.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

$abiwig_customer = $abiwig_invoice->get_customer();
$abiwig_edit_url = wp_nonce_url(
	add_query_arg( array( 'page' => ABIWIG_Admin::PAGE_EDIT, 'invoice_id' => $abiwig_invoice->get_id() ), admin_url( 'admin.php' ) ),
	'abiwig_edit_invoice_' . $abiwig_invoice->get_id()
);
$abiwig_print_url = wp_nonce_url(
	add_query_arg( array( 'action' => 'abiwig_print_invoice', 'invoice_id' => $abiwig_invoice->get_id() ), admin_url( 'admin-post.php' ) ),
	'abiwig_print_invoice_' . $abiwig_invoice->get_id()
);
$abiwig_pdf_url = wp_nonce_url(
	add_query_arg( array( 'action' => 'abiwig_download_invoice', 'invoice_id' => $abiwig_invoice->get_id() ), admin_url( 'admin-post.php' ) ),
	'abiwig_download_invoice_' . $abiwig_invoice->get_id()
);
$abiwig_order_url = '';
if ( $abiwig_invoice->get_order_id() && function_exists( 'wc_get_order' ) ) {
	$abiwig_order = wc_get_order( $abiwig_invoice->get_order_id() );
	if ( $abiwig_order && method_exists( $abiwig_order, 'get_edit_order_url' ) ) {
		$abiwig_order_url = $abiwig_order->get_edit_order_url();
	}
}
?>
<div class="wrap abiwig-admin-wrap">
	<h1 class="wp-heading-inline">
		<?php
		/* translators: %s: invoice number. */
		echo esc_html( sprintf( __( 'Invoice %s', 'abill-invoice-generator-for-woocommerce' ), $abiwig_invoice->get_number() ) );
		?>
	</h1>
	<a class="page-title-action" href="<?php echo esc_url( $abiwig_edit_url ); ?>"><?php esc_html_e( 'Edit', 'abill-invoice-generator-for-woocommerce' ); ?></a>
	<a class="page-title-action" target="_blank" rel="noopener" href="<?php echo esc_url( $abiwig_print_url ); ?>"><?php esc_html_e( 'Print', 'abill-invoice-generator-for-woocommerce' ); ?></a>
	<a class="page-title-action" href="<?php echo esc_url( $abiwig_pdf_url ); ?>"><?php esc_html_e( 'Download PDF', 'abill-invoice-generator-for-woocommerce' ); ?></a>
	<a class="page-title-action" href="<?php echo esc_url( admin_url( 'admin.php?page=' . ABIWIG_Admin::PAGE_INVOICES ) ); ?>"><?php esc_html_e( 'All invoices', 'abill-invoice-generator-for-woocommerce' ); ?></a>
	<hr class="wp-header-end">

	<?php if ( $abiwig_notice ) : ?>
		<div class="notice notice-<?php echo esc_attr( $abiwig_notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $abiwig_notice['message'] ); ?></p></div>
	<?php endif; ?>

	<?php
	abiwig_get_template(
		'invoice-default.php',
		array(
			'invoice'     => $abiwig_invoice,
			'context'     => 'admin',
			'order_url'   => $abiwig_order_url,
			'show_status' => true,
		)
	);
	?>

	<div class="abiwig-grid abiwig-grid-2 abiwig-view-actions">
		<div class="abiwig-panel">
			<h2><?php esc_html_e( 'Send invoice', 'abill-invoice-generator-for-woocommerce' ); ?></h2>
			<p class="description"><?php esc_html_e( 'The invoice PDF will be attached using the normal WordPress mail system.', 'abill-invoice-generator-for-woocommerce' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="abiwig_email_invoice">
				<input type="hidden" name="invoice_id" value="<?php echo esc_attr( $abiwig_invoice->get_id() ); ?>">
				<?php wp_nonce_field( 'abiwig_email_invoice_' . $abiwig_invoice->get_id() ); ?>
				<p><label><?php esc_html_e( 'Recipient', 'abill-invoice-generator-for-woocommerce' ); ?><br><input class="regular-text" type="email" name="recipient" value="<?php echo esc_attr( (string) ( $abiwig_customer['email'] ?? '' ) ); ?>" required></label></p>
				<p><label><?php esc_html_e( 'Subject', 'abill-invoice-generator-for-woocommerce' ); ?><br><input class="large-text" type="text" name="subject" value="<?php echo esc_attr( (string) abiwig_get_setting( 'email_subject', '' ) ); ?>"></label></p>
				<p><label><?php esc_html_e( 'Message', 'abill-invoice-generator-for-woocommerce' ); ?><br><textarea class="large-text" rows="6" name="message"><?php echo esc_textarea( (string) abiwig_get_setting( 'email_message', '' ) ); ?></textarea></label></p>
				<?php submit_button( __( 'Send invoice', 'abill-invoice-generator-for-woocommerce' ), 'primary abiwig-send-button', 'submit', false ); ?>
			</form>
		</div>
		<div class="abiwig-panel">
			<h2><?php esc_html_e( 'Invoice actions', 'abill-invoice-generator-for-woocommerce' ); ?></h2>
			<p><a class="button button-primary" href="<?php echo esc_url( $abiwig_edit_url ); ?>"><?php esc_html_e( 'Edit invoice', 'abill-invoice-generator-for-woocommerce' ); ?></a></p>
			<p><a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( $abiwig_print_url ); ?>"><?php esc_html_e( 'Print invoice', 'abill-invoice-generator-for-woocommerce' ); ?></a></p>
			<p><a class="button" href="<?php echo esc_url( $abiwig_pdf_url ); ?>"><?php esc_html_e( 'Download PDF', 'abill-invoice-generator-for-woocommerce' ); ?></a></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="abiwig_trash_invoice">
				<input type="hidden" name="invoice_id" value="<?php echo esc_attr( $abiwig_invoice->get_id() ); ?>">
				<?php wp_nonce_field( 'abiwig_trash_invoice_' . $abiwig_invoice->get_id() ); ?>
				<?php submit_button( __( 'Move to Trash', 'abill-invoice-generator-for-woocommerce' ), 'delete', 'submit', false, array( 'class' => 'abiwig-delete-button' ) ); ?>
			</form>
		</div>
	</div>
</div>

<?php
/**
 * Invoice edit screen.
 *
 * Expected variables: $abiwig_invoice, $abiwig_notice.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

$abiwig_data     = $abiwig_invoice->get_data();
$abiwig_business = $abiwig_invoice->get_business();
$abiwig_customer = $abiwig_invoice->get_customer();
$abiwig_items    = $abiwig_invoice->get_items();
$abiwig_totals   = $abiwig_invoice->get_totals();
$abiwig_view_url = wp_nonce_url(
	add_query_arg( array( 'page' => ABIWIG_Admin::PAGE_VIEW, 'invoice_id' => $abiwig_invoice->get_id() ), admin_url( 'admin.php' ) ),
	'abiwig_view_invoice_' . $abiwig_invoice->get_id()
);

for ( $abiwig_i = 0; $abiwig_i < 3; $abiwig_i++ ) {
	$abiwig_items[] = array( 'name' => '', 'sku' => '', 'quantity' => 1, 'subtotal' => 0, 'tax' => 0, 'total' => 0 );
}
?>
<div class="wrap abiwig-admin-wrap">
	<h1 class="wp-heading-inline">
		<?php
		/* translators: %s: invoice number. */
		echo esc_html( sprintf( __( 'Edit invoice %s', 'abill-invoice-generator-for-woocommerce' ), $abiwig_invoice->get_number() ) );
		?>
	</h1>
	<a class="page-title-action" href="<?php echo esc_url( $abiwig_view_url ); ?>"><?php esc_html_e( 'View invoice', 'abill-invoice-generator-for-woocommerce' ); ?></a>
	<hr class="wp-header-end">

	<?php if ( $abiwig_notice ) : ?>
		<div class="notice notice-<?php echo esc_attr( $abiwig_notice['type'] ); ?>"><p><?php echo esc_html( $abiwig_notice['message'] ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="abiwig-invoice-form">
		<input type="hidden" name="action" value="abiwig_save_invoice">
		<input type="hidden" name="invoice_id" value="<?php echo esc_attr( $abiwig_invoice->get_id() ); ?>">
		<?php wp_nonce_field( 'abiwig_save_invoice_' . $abiwig_invoice->get_id() ); ?>

		<div class="abiwig-grid abiwig-grid-3">
			<div class="abiwig-panel">
				<h2><?php esc_html_e( 'Invoice details', 'abill-invoice-generator-for-woocommerce' ); ?></h2>
				<p><label><?php esc_html_e( 'Invoice number', 'abill-invoice-generator-for-woocommerce' ); ?><br><input class="regular-text" type="text" name="invoice[invoice_number]" value="<?php echo esc_attr( $abiwig_invoice->get_number() ); ?>" required></label></p>
				<p><label><?php esc_html_e( 'Invoice date', 'abill-invoice-generator-for-woocommerce' ); ?><br><input type="date" name="invoice[invoice_date]" value="<?php echo esc_attr( (string) $abiwig_data['invoice_date'] ); ?>" required></label></p>
				<p><label><?php esc_html_e( 'Due date', 'abill-invoice-generator-for-woocommerce' ); ?><br><input type="date" name="invoice[due_date]" value="<?php echo esc_attr( (string) $abiwig_data['due_date'] ); ?>"></label></p>
				<p><label><?php esc_html_e( 'Status', 'abill-invoice-generator-for-woocommerce' ); ?><br>
					<select name="invoice[status]">
						<option value="draft" <?php selected( $abiwig_invoice->get_status(), 'draft' ); ?>><?php esc_html_e( 'Draft', 'abill-invoice-generator-for-woocommerce' ); ?></option>
						<option value="sent" <?php selected( $abiwig_invoice->get_status(), 'sent' ); ?>><?php esc_html_e( 'Sent', 'abill-invoice-generator-for-woocommerce' ); ?></option>
						<option value="paid" <?php selected( $abiwig_invoice->get_status(), 'paid' ); ?>><?php esc_html_e( 'Paid', 'abill-invoice-generator-for-woocommerce' ); ?></option>
						<option value="cancelled" <?php selected( $abiwig_invoice->get_status(), 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'abill-invoice-generator-for-woocommerce' ); ?></option>
					</select>
				</label></p>
				<p><label><?php esc_html_e( 'Currency', 'abill-invoice-generator-for-woocommerce' ); ?><br><input type="text" maxlength="3" name="invoice[currency]" value="<?php echo esc_attr( $abiwig_invoice->get_currency() ); ?>" required></label></p>
				<p><label><?php esc_html_e( 'Payment method', 'abill-invoice-generator-for-woocommerce' ); ?><br><input class="regular-text" type="text" name="invoice[payment_method]" value="<?php echo esc_attr( (string) $abiwig_data['payment_method'] ); ?>"></label></p>
				<p><label><?php esc_html_e( 'Shipping method', 'abill-invoice-generator-for-woocommerce' ); ?><br><input class="regular-text" type="text" name="invoice[shipping_method]" value="<?php echo esc_attr( (string) $abiwig_data['shipping_method'] ); ?>"></label></p>
			</div>

			<div class="abiwig-panel">
				<h2><?php esc_html_e( 'Business', 'abill-invoice-generator-for-woocommerce' ); ?></h2>
				<?php foreach ( array( 'name' => __( 'Business name', 'abill-invoice-generator-for-woocommerce' ), 'address_1' => __( 'Address line 1', 'abill-invoice-generator-for-woocommerce' ), 'address_2' => __( 'Address line 2', 'abill-invoice-generator-for-woocommerce' ), 'city' => __( 'City', 'abill-invoice-generator-for-woocommerce' ), 'state' => __( 'State', 'abill-invoice-generator-for-woocommerce' ), 'postcode' => __( 'Postcode', 'abill-invoice-generator-for-woocommerce' ), 'country' => __( 'Country', 'abill-invoice-generator-for-woocommerce' ), 'tax_id' => __( 'Tax ID / GSTIN', 'abill-invoice-generator-for-woocommerce' ), 'email' => __( 'Email', 'abill-invoice-generator-for-woocommerce' ), 'phone' => __( 'Phone', 'abill-invoice-generator-for-woocommerce' ) ) as $abiwig_key => $abiwig_label ) : ?>
					<p><label><?php echo esc_html( $abiwig_label ); ?><br><input class="regular-text" type="<?php echo 'email' === $abiwig_key ? 'email' : 'text'; ?>" name="invoice[business][<?php echo esc_attr( $abiwig_key ); ?>]" value="<?php echo esc_attr( (string) ( $abiwig_business[ $abiwig_key ] ?? '' ) ); ?>"></label></p>
				<?php endforeach; ?>
				<input type="hidden" name="invoice[business][logo_id]" value="<?php echo esc_attr( absint( $abiwig_business['logo_id'] ?? 0 ) ); ?>">
			</div>

			<div class="abiwig-panel">
				<h2><?php esc_html_e( 'Customer', 'abill-invoice-generator-for-woocommerce' ); ?></h2>
				<?php foreach ( array( 'name' => __( 'Customer name', 'abill-invoice-generator-for-woocommerce' ), 'company' => __( 'Company', 'abill-invoice-generator-for-woocommerce' ), 'address_1' => __( 'Address line 1', 'abill-invoice-generator-for-woocommerce' ), 'address_2' => __( 'Address line 2', 'abill-invoice-generator-for-woocommerce' ), 'city' => __( 'City', 'abill-invoice-generator-for-woocommerce' ), 'state' => __( 'State', 'abill-invoice-generator-for-woocommerce' ), 'postcode' => __( 'Postcode', 'abill-invoice-generator-for-woocommerce' ), 'country' => __( 'Country', 'abill-invoice-generator-for-woocommerce' ), 'tax_id' => __( 'Tax ID / GSTIN', 'abill-invoice-generator-for-woocommerce' ), 'email' => __( 'Email', 'abill-invoice-generator-for-woocommerce' ), 'phone' => __( 'Phone', 'abill-invoice-generator-for-woocommerce' ) ) as $abiwig_key => $abiwig_label ) : ?>
					<p><label><?php echo esc_html( $abiwig_label ); ?><br><input class="regular-text" type="<?php echo 'email' === $abiwig_key ? 'email' : 'text'; ?>" name="invoice[customer][<?php echo esc_attr( $abiwig_key ); ?>]" value="<?php echo esc_attr( (string) ( $abiwig_customer[ $abiwig_key ] ?? '' ) ); ?>"></label></p>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="abiwig-panel">
			<h2><?php esc_html_e( 'Line items', 'abill-invoice-generator-for-woocommerce' ); ?></h2>
			<p class="description"><?php esc_html_e( 'The blank rows can be used to add new items. Select Remove to delete an existing row.', 'abill-invoice-generator-for-woocommerce' ); ?></p>
			<table class="widefat striped abiwig-items-table">
				<thead><tr>
					<th><?php esc_html_e( 'Item', 'abill-invoice-generator-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'SKU', 'abill-invoice-generator-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Qty', 'abill-invoice-generator-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Subtotal', 'abill-invoice-generator-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Tax', 'abill-invoice-generator-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Total', 'abill-invoice-generator-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Remove', 'abill-invoice-generator-for-woocommerce' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $abiwig_items as $abiwig_index => $abiwig_item ) : ?>
					<tr>
						<td><input type="text" name="invoice[items][<?php echo esc_attr( $abiwig_index ); ?>][name]" value="<?php echo esc_attr( (string) ( $abiwig_item['name'] ?? '' ) ); ?>"></td>
						<td><input type="text" name="invoice[items][<?php echo esc_attr( $abiwig_index ); ?>][sku]" value="<?php echo esc_attr( (string) ( $abiwig_item['sku'] ?? '' ) ); ?>"></td>
						<td><input type="number" min="0" step="0.001" name="invoice[items][<?php echo esc_attr( $abiwig_index ); ?>][quantity]" value="<?php echo esc_attr( (string) ( $abiwig_item['quantity'] ?? 1 ) ); ?>"></td>
						<td><input type="number" step="0.01" name="invoice[items][<?php echo esc_attr( $abiwig_index ); ?>][subtotal]" value="<?php echo esc_attr( (string) ( $abiwig_item['subtotal'] ?? 0 ) ); ?>"></td>
						<td><input type="number" step="0.01" name="invoice[items][<?php echo esc_attr( $abiwig_index ); ?>][tax]" value="<?php echo esc_attr( (string) ( $abiwig_item['tax'] ?? 0 ) ); ?>"></td>
						<td><input type="number" step="0.01" name="invoice[items][<?php echo esc_attr( $abiwig_index ); ?>][total]" value="<?php echo esc_attr( (string) ( $abiwig_item['total'] ?? 0 ) ); ?>"></td>
						<td><input type="checkbox" name="invoice[items][<?php echo esc_attr( $abiwig_index ); ?>][remove]" value="1"></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div class="abiwig-grid abiwig-grid-2">
			<div class="abiwig-panel">
				<h2><?php esc_html_e( 'Notes and terms', 'abill-invoice-generator-for-woocommerce' ); ?></h2>
				<p><label><?php esc_html_e( 'Notes', 'abill-invoice-generator-for-woocommerce' ); ?><br><textarea class="large-text" rows="5" name="invoice[notes]"><?php echo esc_textarea( (string) $abiwig_data['notes'] ); ?></textarea></label></p>
				<p><label><?php esc_html_e( 'Terms', 'abill-invoice-generator-for-woocommerce' ); ?><br><textarea class="large-text" rows="5" name="invoice[terms]"><?php echo esc_textarea( (string) $abiwig_data['terms'] ); ?></textarea></label></p>
			</div>
			<div class="abiwig-panel">
				<h2><?php esc_html_e( 'Totals', 'abill-invoice-generator-for-woocommerce' ); ?></h2>
				<?php foreach ( array( 'subtotal' => __( 'Subtotal', 'abill-invoice-generator-for-woocommerce' ), 'discount' => __( 'Discount', 'abill-invoice-generator-for-woocommerce' ), 'shipping' => __( 'Shipping', 'abill-invoice-generator-for-woocommerce' ), 'tax' => __( 'Tax', 'abill-invoice-generator-for-woocommerce' ), 'total' => __( 'Grand total', 'abill-invoice-generator-for-woocommerce' ) ) as $abiwig_key => $abiwig_label ) : ?>
					<p><label><?php echo esc_html( $abiwig_label ); ?><br><input type="number" step="0.01" name="invoice[totals][<?php echo esc_attr( $abiwig_key ); ?>]" value="<?php echo esc_attr( (string) ( $abiwig_totals[ $abiwig_key ] ?? 0 ) ); ?>"></label></p>
				<?php endforeach; ?>
			</div>
		</div>

		<?php submit_button( __( 'Save invoice', 'abill-invoice-generator-for-woocommerce' ) ); ?>
	</form>
</div>

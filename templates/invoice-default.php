<?php
/**
 * Default invoice document template.
 *
 * The template receives its data in the $args array so it can be safely loaded
 * through abiwig_get_template(). Themes may override this file by copying it to:
 * your-theme/abill-invoices/invoice-default.php
 *
 * Expected arguments:
 * - invoice: ABIWIG_Invoice object.
 * - context: admin or print.
 * - order_url: Optional WooCommerce order edit URL.
 * - show_status: Whether to display the invoice status.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

$abiwig_invoice = isset( $args['invoice'] ) && $args['invoice'] instanceof ABIWIG_Invoice ? $args['invoice'] : null;
if ( ! $abiwig_invoice ) {
	return;
}

$abiwig_context     = isset( $args['context'] ) ? sanitize_key( (string) $args['context'] ) : 'admin';
$abiwig_is_print    = 'print' === $abiwig_context;
$abiwig_order_url   = isset( $args['order_url'] ) ? esc_url_raw( (string) $args['order_url'] ) : '';
$abiwig_show_status = isset( $args['show_status'] ) ? (bool) $args['show_status'] : ! $abiwig_is_print;
$abiwig_data        = $abiwig_invoice->get_data();
$abiwig_business    = $abiwig_invoice->get_business();
$abiwig_customer    = $abiwig_invoice->get_customer();
$abiwig_totals      = $abiwig_invoice->get_totals();
$abiwig_currency    = $abiwig_invoice->get_currency();
$abiwig_logo_id     = absint( $abiwig_business['logo_id'] ?? 0 );
$abiwig_logo_url    = $abiwig_logo_id ? wp_get_attachment_image_url( $abiwig_logo_id, 'medium' ) : '';

$abiwig_classes = $abiwig_is_print
	? array(
		'document' => 'abiwig-print-invoice',
		'header'   => 'abiwig-print-header',
		'logo'     => 'abiwig-print-logo',
		'meta'     => 'abiwig-print-meta',
		'customer' => 'abiwig-print-customer',
		'items'    => 'abiwig-print-items',
		'totals'   => 'abiwig-print-totals',
		'grand'    => 'abiwig-print-grand-total',
		'copy'     => 'abiwig-print-copy',
	)
	: array(
		'document' => 'abiwig-invoice-document',
		'header'   => 'abiwig-invoice-header',
		'logo'     => 'abiwig-invoice-logo',
		'meta'     => 'abiwig-invoice-meta',
		'customer' => 'abiwig-bill-to',
		'items'    => 'widefat striped abiwig-view-items',
		'totals'   => 'abiwig-totals',
		'grand'    => 'abiwig-grand-total',
		'copy'     => 'abiwig-copy-section',
	);

$abiwig_business_lines = abiwig_address_lines( $abiwig_business );
$abiwig_customer_lines = abiwig_address_lines( $abiwig_customer );
$abiwig_invoice_date   = abiwig_display_date( (string) ( $abiwig_data['invoice_date'] ?? '' ) );
$abiwig_due_date       = abiwig_display_date( (string) ( $abiwig_data['due_date'] ?? '' ) );
?>
<article class="<?php echo esc_attr( $abiwig_classes['document'] ); ?>" data-abiwig-template="invoice-default">
	<header class="<?php echo esc_attr( $abiwig_classes['header'] ); ?>">
		<section>
			<?php if ( $abiwig_logo_url ) : ?>
				<img class="<?php echo esc_attr( $abiwig_classes['logo'] ); ?>" src="<?php echo esc_url( $abiwig_logo_url ); ?>" alt="<?php echo esc_attr( (string) ( $abiwig_business['name'] ?? '' ) ); ?>">
			<?php endif; ?>
			<?php if ( $abiwig_is_print ) : ?>
				<h1><?php echo esc_html( (string) ( $abiwig_business['name'] ?? '' ) ); ?></h1>
			<?php else : ?>
				<h2><?php echo esc_html( (string) ( $abiwig_business['name'] ?? '' ) ); ?></h2>
			<?php endif; ?>
			<?php foreach ( $abiwig_business_lines as $abiwig_line ) : ?>
				<div><?php echo esc_html( $abiwig_line ); ?></div>
			<?php endforeach; ?>
			<?php if ( ! empty( $abiwig_business['tax_id'] ) ) : ?>
				<div>
					<?php
					/* translators: %s: business tax identification number. */
					echo esc_html( sprintf( __( 'Tax ID: %s', 'abill-invoice-generator-for-woocommerce' ), $abiwig_business['tax_id'] ) );
					?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $abiwig_business['email'] ) ) : ?>
				<div><?php echo esc_html( $abiwig_business['email'] ); ?></div>
			<?php endif; ?>
			<?php if ( ! empty( $abiwig_business['phone'] ) ) : ?>
				<div><?php echo esc_html( $abiwig_business['phone'] ); ?></div>
			<?php endif; ?>
		</section>

		<section class="<?php echo esc_attr( $abiwig_classes['meta'] ); ?>">
			<?php if ( $abiwig_is_print ) : ?>
				<h2><?php esc_html_e( 'INVOICE', 'abill-invoice-generator-for-woocommerce' ); ?></h2>
			<?php else : ?>
				<strong><?php esc_html_e( 'INVOICE', 'abill-invoice-generator-for-woocommerce' ); ?></strong>
			<?php endif; ?>
			<div>
				<?php
				/* translators: %s: invoice number. */
				echo esc_html( sprintf( __( 'Number: %s', 'abill-invoice-generator-for-woocommerce' ), $abiwig_invoice->get_number() ) );
				?>
			</div>
			<div>
				<?php
				/* translators: %s: formatted invoice date. */
				echo esc_html( sprintf( __( 'Date: %s', 'abill-invoice-generator-for-woocommerce' ), $abiwig_invoice_date ) );
				?>
			</div>
			<?php if ( $abiwig_due_date ) : ?>
				<div>
					<?php
					/* translators: %s: formatted invoice due date. */
					echo esc_html( sprintf( __( 'Due: %s', 'abill-invoice-generator-for-woocommerce' ), $abiwig_due_date ) );
					?>
				</div>
			<?php endif; ?>
			<?php if ( $abiwig_show_status ) : ?>
				<div>
					<?php
					/* translators: %s: invoice status label. */
					echo esc_html( sprintf( __( 'Status: %s', 'abill-invoice-generator-for-woocommerce' ), ucfirst( $abiwig_invoice->get_status() ) ) );
					?>
				</div>
			<?php endif; ?>
			<?php if ( $abiwig_invoice->get_order_number() ) : ?>
				<div>
					<?php if ( $abiwig_order_url && ! $abiwig_is_print ) : ?>
						<a href="<?php echo esc_url( $abiwig_order_url ); ?>">
							<?php
							/* translators: %s: WooCommerce order number. */
							echo esc_html( sprintf( __( 'Order #%s', 'abill-invoice-generator-for-woocommerce' ), $abiwig_invoice->get_order_number() ) );
							?>
						</a>
					<?php else : ?>
						<?php
						/* translators: %s: WooCommerce order number. */
						echo esc_html( sprintf( __( 'Order #%s', 'abill-invoice-generator-for-woocommerce' ), $abiwig_invoice->get_order_number() ) );
						?>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</section>
	</header>

	<section class="<?php echo esc_attr( $abiwig_classes['customer'] ); ?>">
		<h3><?php esc_html_e( 'Bill to', 'abill-invoice-generator-for-woocommerce' ); ?></h3>
		<?php foreach ( $abiwig_customer_lines as $abiwig_line ) : ?>
			<div><?php echo esc_html( $abiwig_line ); ?></div>
		<?php endforeach; ?>
		<?php if ( ! empty( $abiwig_customer['email'] ) ) : ?>
			<div><?php echo esc_html( $abiwig_customer['email'] ); ?></div>
		<?php endif; ?>
		<?php if ( ! empty( $abiwig_customer['phone'] ) ) : ?>
			<div><?php echo esc_html( $abiwig_customer['phone'] ); ?></div>
		<?php endif; ?>
		<?php if ( ! empty( $abiwig_customer['tax_id'] ) ) : ?>
			<div>
				<?php
				/* translators: %s: customer tax identification number. */
				echo esc_html( sprintf( __( 'Tax ID: %s', 'abill-invoice-generator-for-woocommerce' ), $abiwig_customer['tax_id'] ) );
				?>
			</div>
		<?php endif; ?>
	</section>

	<table class="<?php echo esc_attr( $abiwig_classes['items'] ); ?>">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Item', 'abill-invoice-generator-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'SKU', 'abill-invoice-generator-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Qty', 'abill-invoice-generator-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Tax', 'abill-invoice-generator-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Total', 'abill-invoice-generator-for-woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $abiwig_invoice->get_items() as $abiwig_item ) : ?>
			<tr>
				<td><?php echo esc_html( (string) $abiwig_item['name'] ); ?></td>
				<td><?php echo esc_html( (string) $abiwig_item['sku'] ); ?></td>
				<td><?php echo esc_html( abiwig_format_quantity( $abiwig_item['quantity'] ) ); ?></td>
				<td><?php echo wp_kses_post( wc_price( (float) $abiwig_item['tax'], array( 'currency' => $abiwig_currency ) ) ); ?></td>
				<td><?php echo wp_kses_post( wc_price( (float) $abiwig_item['total'], array( 'currency' => $abiwig_currency ) ) ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<section class="<?php echo esc_attr( $abiwig_classes['totals'] ); ?>">
		<table>
			<tr><th><?php esc_html_e( 'Subtotal', 'abill-invoice-generator-for-woocommerce' ); ?></th><td><?php echo wp_kses_post( wc_price( (float) ( $abiwig_totals['subtotal'] ?? 0 ), array( 'currency' => $abiwig_currency ) ) ); ?></td></tr>
			<?php if ( (float) ( $abiwig_totals['discount'] ?? 0 ) > 0 ) : ?><tr><th><?php esc_html_e( 'Discount', 'abill-invoice-generator-for-woocommerce' ); ?></th><td><?php echo wp_kses_post( wc_price( (float) $abiwig_totals['discount'], array( 'currency' => $abiwig_currency ) ) ); ?></td></tr><?php endif; ?>
			<?php if ( (float) ( $abiwig_totals['shipping'] ?? 0 ) > 0 ) : ?><tr><th><?php esc_html_e( 'Shipping', 'abill-invoice-generator-for-woocommerce' ); ?></th><td><?php echo wp_kses_post( wc_price( (float) $abiwig_totals['shipping'], array( 'currency' => $abiwig_currency ) ) ); ?></td></tr><?php endif; ?>
			<?php if ( (float) ( $abiwig_totals['tax'] ?? 0 ) > 0 ) : ?><tr><th><?php esc_html_e( 'Tax', 'abill-invoice-generator-for-woocommerce' ); ?></th><td><?php echo wp_kses_post( wc_price( (float) $abiwig_totals['tax'], array( 'currency' => $abiwig_currency ) ) ); ?></td></tr><?php endif; ?>
			<tr class="<?php echo esc_attr( $abiwig_classes['grand'] ); ?>"><th><?php esc_html_e( 'Total', 'abill-invoice-generator-for-woocommerce' ); ?></th><td><?php echo wp_kses_post( wc_price( (float) ( $abiwig_totals['total'] ?? 0 ), array( 'currency' => $abiwig_currency ) ) ); ?></td></tr>
		</table>
	</section>

	<?php if ( ! empty( $abiwig_data['payment_method'] ) || ! empty( $abiwig_data['shipping_method'] ) ) : ?>
		<section class="<?php echo esc_attr( $abiwig_classes['copy'] ); ?>">
			<h3><?php esc_html_e( 'Order details', 'abill-invoice-generator-for-woocommerce' ); ?></h3>
			<?php if ( ! empty( $abiwig_data['payment_method'] ) ) : ?>
				<div>
					<?php
					/* translators: %s: payment method name. */
					echo esc_html( sprintf( __( 'Payment: %s', 'abill-invoice-generator-for-woocommerce' ), $abiwig_data['payment_method'] ) );
					?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $abiwig_data['shipping_method'] ) ) : ?>
				<div>
					<?php
					/* translators: %s: shipping method name. */
					echo esc_html( sprintf( __( 'Shipping: %s', 'abill-invoice-generator-for-woocommerce' ), $abiwig_data['shipping_method'] ) );
					?>
				</div>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $abiwig_data['notes'] ) ) : ?>
		<section class="<?php echo esc_attr( $abiwig_classes['copy'] ); ?>">
			<h3><?php esc_html_e( 'Notes', 'abill-invoice-generator-for-woocommerce' ); ?></h3>
			<div class="abiwig-preline"><?php echo nl2br( esc_html( (string) $abiwig_data['notes'] ) ); ?></div>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $abiwig_data['terms'] ) ) : ?>
		<section class="<?php echo esc_attr( $abiwig_classes['copy'] ); ?>">
			<h3><?php esc_html_e( 'Terms', 'abill-invoice-generator-for-woocommerce' ); ?></h3>
			<div class="abiwig-preline"><?php echo nl2br( esc_html( (string) $abiwig_data['terms'] ) ); ?></div>
		</section>
	<?php endif; ?>
</article>

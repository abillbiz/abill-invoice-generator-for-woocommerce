<?php
/**
 * Invoice list screen.
 *
 * Expected variables: $abiwig_query, $abiwig_repository, $abiwig_notice, $abiwig_base_url, $abiwig_status, $abiwig_search.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap abiwig-admin-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'ABill Invoices', 'abill-invoice-generator-for-woocommerce' ); ?></h1>
	<a class="page-title-action" href="<?php echo esc_url( admin_url( 'admin.php?page=' . ABIWIG_Admin::PAGE_SETTINGS ) ); ?>">
		<?php esc_html_e( 'Settings', 'abill-invoice-generator-for-woocommerce' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( $abiwig_notice ) : ?>
		<div class="notice notice-<?php echo esc_attr( $abiwig_notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( $abiwig_notice['message'] ); ?></p></div>
	<?php endif; ?>

	<div class="abiwig-create-card">
		<h2><?php esc_html_e( 'Create invoice from an order', 'abill-invoice-generator-for-woocommerce' ); ?></h2>
		<p><?php esc_html_e( 'Enter a WooCommerce order ID. Existing invoices are opened instead of duplicated.', 'abill-invoice-generator-for-woocommerce' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="abiwig_create_invoice">
			<?php wp_nonce_field( 'abiwig_create_invoice' ); ?>
			<label class="screen-reader-text" for="abiwig-order-id"><?php esc_html_e( 'WooCommerce order ID', 'abill-invoice-generator-for-woocommerce' ); ?></label>
			<input id="abiwig-order-id" type="number" min="1" step="1" name="order_id" required placeholder="<?php esc_attr_e( 'Order ID', 'abill-invoice-generator-for-woocommerce' ); ?>">
			<?php submit_button( __( 'Create invoice', 'abill-invoice-generator-for-woocommerce' ), 'primary', 'submit', false ); ?>
		</form>
	</div>

	<form method="get" class="abiwig-filters">
		<input type="hidden" name="page" value="<?php echo esc_attr( ABIWIG_Admin::PAGE_INVOICES ); ?>">
		<label for="abiwig-status-filter" class="screen-reader-text"><?php esc_html_e( 'Filter by invoice status', 'abill-invoice-generator-for-woocommerce' ); ?></label>
		<select id="abiwig-status-filter" name="invoice_status">
			<option value=""><?php esc_html_e( 'All statuses', 'abill-invoice-generator-for-woocommerce' ); ?></option>
			<option value="draft" <?php selected( $abiwig_status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'abill-invoice-generator-for-woocommerce' ); ?></option>
			<option value="sent" <?php selected( $abiwig_status, 'sent' ); ?>><?php esc_html_e( 'Sent', 'abill-invoice-generator-for-woocommerce' ); ?></option>
			<option value="paid" <?php selected( $abiwig_status, 'paid' ); ?>><?php esc_html_e( 'Paid', 'abill-invoice-generator-for-woocommerce' ); ?></option>
			<option value="cancelled" <?php selected( $abiwig_status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'abill-invoice-generator-for-woocommerce' ); ?></option>
		</select>
		<label for="abiwig-search" class="screen-reader-text"><?php esc_html_e( 'Search invoices', 'abill-invoice-generator-for-woocommerce' ); ?></label>
		<input id="abiwig-search" type="search" name="s" value="<?php echo esc_attr( $abiwig_search ); ?>" placeholder="<?php esc_attr_e( 'Invoice or customer', 'abill-invoice-generator-for-woocommerce' ); ?>">
		<?php submit_button( __( 'Filter', 'abill-invoice-generator-for-woocommerce' ), 'secondary', 'submit', false ); ?>
		<?php if ( $abiwig_status || $abiwig_search ) : ?>
			<a class="button" href="<?php echo esc_url( $abiwig_base_url ); ?>"><?php esc_html_e( 'Reset', 'abill-invoice-generator-for-woocommerce' ); ?></a>
		<?php endif; ?>
	</form>

	<table class="wp-list-table widefat fixed striped table-view-list abiwig-invoice-table">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Invoice', 'abill-invoice-generator-for-woocommerce' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Order', 'abill-invoice-generator-for-woocommerce' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Customer', 'abill-invoice-generator-for-woocommerce' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Date', 'abill-invoice-generator-for-woocommerce' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Total', 'abill-invoice-generator-for-woocommerce' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Status', 'abill-invoice-generator-for-woocommerce' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Actions', 'abill-invoice-generator-for-woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( $abiwig_query->have_posts() ) : ?>
			<?php while ( $abiwig_query->have_posts() ) : $abiwig_query->the_post(); ?>
				<?php
				$abiwig_invoice = $abiwig_repository->get( get_the_ID() );
				if ( ! $abiwig_invoice ) {
					continue;
				}
				$abiwig_customer = $abiwig_invoice->get_customer();
				$abiwig_totals   = $abiwig_invoice->get_totals();
				$abiwig_view_url = wp_nonce_url(
					add_query_arg( array( 'page' => ABIWIG_Admin::PAGE_VIEW, 'invoice_id' => $abiwig_invoice->get_id() ), admin_url( 'admin.php' ) ),
					'abiwig_view_invoice_' . $abiwig_invoice->get_id()
				);
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
				?>
				<tr>
					<td>
						<strong><a href="<?php echo esc_url( $abiwig_view_url ); ?>"><?php echo esc_html( $abiwig_invoice->get_number() ); ?></a></strong>
					</td>
					<td><?php echo esc_html( $abiwig_invoice->get_order_number() ? '#' . $abiwig_invoice->get_order_number() : '—' ); ?></td>
					<td>
						<?php echo esc_html( (string) ( $abiwig_customer['name'] ?? '' ) ); ?>
						<?php if ( ! empty( $abiwig_customer['email'] ) ) : ?><br><small><?php echo esc_html( $abiwig_customer['email'] ); ?></small><?php endif; ?>
					</td>
					<td><?php echo esc_html( (string) $abiwig_invoice->get( 'invoice_date', '' ) ); ?></td>
					<td><?php echo wp_kses_post( wc_price( (float) ( $abiwig_totals['total'] ?? 0 ), array( 'currency' => $abiwig_invoice->get_currency() ) ) ); ?></td>
					<td><span class="abiwig-status abiwig-status-<?php echo esc_attr( $abiwig_invoice->get_status() ); ?>"><?php echo esc_html( ucfirst( $abiwig_invoice->get_status() ) ); ?></span></td>
					<td class="abiwig-actions">
						<a class="button button-small" href="<?php echo esc_url( $abiwig_view_url ); ?>"><?php esc_html_e( 'View', 'abill-invoice-generator-for-woocommerce' ); ?></a>
						<a class="button button-small" href="<?php echo esc_url( $abiwig_edit_url ); ?>"><?php esc_html_e( 'Edit', 'abill-invoice-generator-for-woocommerce' ); ?></a>
						<a class="button button-small" target="_blank" rel="noopener" href="<?php echo esc_url( $abiwig_print_url ); ?>"><?php esc_html_e( 'Print', 'abill-invoice-generator-for-woocommerce' ); ?></a>
						<a class="button button-small" href="<?php echo esc_url( $abiwig_pdf_url ); ?>"><?php esc_html_e( 'PDF', 'abill-invoice-generator-for-woocommerce' ); ?></a>
						<form class="abiwig-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="abiwig_email_invoice">
							<input type="hidden" name="invoice_id" value="<?php echo esc_attr( $abiwig_invoice->get_id() ); ?>">
							<input type="hidden" name="recipient" value="<?php echo esc_attr( (string) ( $abiwig_customer['email'] ?? '' ) ); ?>">
							<?php wp_nonce_field( 'abiwig_email_invoice_' . $abiwig_invoice->get_id() ); ?>
							<button type="submit" class="button button-small abiwig-send-button"><?php esc_html_e( 'Send', 'abill-invoice-generator-for-woocommerce' ); ?></button>
						</form>
						<form class="abiwig-inline-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="abiwig_trash_invoice">
							<input type="hidden" name="invoice_id" value="<?php echo esc_attr( $abiwig_invoice->get_id() ); ?>">
							<?php wp_nonce_field( 'abiwig_trash_invoice_' . $abiwig_invoice->get_id() ); ?>
							<button type="submit" class="button button-small button-link-delete abiwig-delete-button"><?php esc_html_e( 'Trash', 'abill-invoice-generator-for-woocommerce' ); ?></button>
						</form>
					</td>
				</tr>
			<?php endwhile; ?>
		<?php else : ?>
			<tr><td colspan="7"><?php esc_html_e( 'No invoices found. Create one from a WooCommerce order above.', 'abill-invoice-generator-for-woocommerce' ); ?></td></tr>
		<?php endif; ?>
		</tbody>
	</table>

	<?php
	wp_reset_postdata();
	$abiwig_pagination = paginate_links(
		array(
			'base'      => add_query_arg( 'paged', '%#%', $abiwig_base_url ),
			'format'    => '',
			'current'   => max( 1, absint( $abiwig_query->get( 'paged' ) ) ),
			'total'     => max( 1, absint( $abiwig_query->max_num_pages ) ),
			'type'      => 'list',
			'add_args'  => array_filter( array( 'invoice_status' => $abiwig_status, 's' => $abiwig_search ) ),
			'prev_text' => __( 'Previous', 'abill-invoice-generator-for-woocommerce' ),
			'next_text' => __( 'Next', 'abill-invoice-generator-for-woocommerce' ),
		)
	);
	?>
	<?php if ( $abiwig_pagination ) : ?><div class="tablenav"><div class="tablenav-pages"><?php echo wp_kses_post( $abiwig_pagination ); ?></div></div><?php endif; ?>
</div>

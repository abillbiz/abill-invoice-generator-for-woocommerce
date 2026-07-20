<?php
/**
 * Plugin settings screen.
 *
 * Expected variable: $abiwig_settings.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

$abiwig_logo_id  = absint( $abiwig_settings['business_logo_id'] ?? 0 );
$abiwig_logo_url = $abiwig_logo_id ? wp_get_attachment_image_url( $abiwig_logo_id, 'medium' ) : '';
?>
<div class="wrap abiwig-admin-wrap">
	<h1><?php esc_html_e( 'ABill Invoice Settings', 'abill-invoice-generator-for-woocommerce' ); ?></h1>
	<p>
		<?php esc_html_e( 'These details become the default business snapshot for newly created invoices.', 'abill-invoice-generator-for-woocommerce' ); ?>
		<a href="https://abill.in/invoice-generator-for-woocommerce/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Documentation', 'abill-invoice-generator-for-woocommerce' ); ?></a>
	</p>
	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php settings_fields( 'abiwig_settings_group' ); ?>

		<h2><?php esc_html_e( 'Business details', 'abill-invoice-generator-for-woocommerce' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php
			$abiwig_business_fields = array(
				'business_name'      => __( 'Business name', 'abill-invoice-generator-for-woocommerce' ),
				'business_address_1' => __( 'Address line 1', 'abill-invoice-generator-for-woocommerce' ),
				'business_address_2' => __( 'Address line 2', 'abill-invoice-generator-for-woocommerce' ),
				'business_city'      => __( 'City', 'abill-invoice-generator-for-woocommerce' ),
				'business_state'     => __( 'State', 'abill-invoice-generator-for-woocommerce' ),
				'business_postcode'  => __( 'Postcode', 'abill-invoice-generator-for-woocommerce' ),
				'business_country'   => __( 'Country code', 'abill-invoice-generator-for-woocommerce' ),
				'business_tax_id'    => __( 'Tax ID / GSTIN', 'abill-invoice-generator-for-woocommerce' ),
				'business_email'     => __( 'Business email', 'abill-invoice-generator-for-woocommerce' ),
				'business_phone'     => __( 'Business phone', 'abill-invoice-generator-for-woocommerce' ),
			);
			foreach ( $abiwig_business_fields as $abiwig_key => $abiwig_label ) :
				$abiwig_type = 'business_email' === $abiwig_key ? 'email' : 'text';
				?>
				<tr>
					<th scope="row"><label for="abiwig-<?php echo esc_attr( $abiwig_key ); ?>"><?php echo esc_html( $abiwig_label ); ?></label></th>
					<td><input class="regular-text" id="abiwig-<?php echo esc_attr( $abiwig_key ); ?>" type="<?php echo esc_attr( $abiwig_type ); ?>" name="abiwig_settings[<?php echo esc_attr( $abiwig_key ); ?>]" value="<?php echo esc_attr( (string) $abiwig_settings[ $abiwig_key ] ); ?>"></td>
				</tr>
			<?php endforeach; ?>
			<tr>
				<th scope="row"><label for="abiwig-business-logo-id"><?php esc_html_e( 'Business logo', 'abill-invoice-generator-for-woocommerce' ); ?></label></th>
				<td>
					<div class="abiwig-logo-field">
						<div id="abiwig-logo-preview" class="abiwig-logo-preview<?php echo $abiwig_logo_url ? '' : ' is-empty'; ?>" data-empty-label="<?php esc_attr_e( 'No logo selected', 'abill-invoice-generator-for-woocommerce' ); ?>">
							<?php if ( $abiwig_logo_url ) : ?>
								<img src="<?php echo esc_url( $abiwig_logo_url ); ?>" alt="">
							<?php endif; ?>
						</div>
						<div>
							<input id="abiwig-business-logo-id" type="hidden" name="abiwig_settings[business_logo_id]" value="<?php echo esc_attr( $abiwig_logo_id ); ?>">
							<button id="abiwig-select-logo" type="button" class="button"><?php esc_html_e( 'Select logo', 'abill-invoice-generator-for-woocommerce' ); ?></button>
							<button id="abiwig-remove-logo" type="button" class="button-link-delete"<?php echo $abiwig_logo_url ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove logo', 'abill-invoice-generator-for-woocommerce' ); ?></button>
							<p class="description"><?php esc_html_e( 'Use a clear PNG or JPG logo. It will be copied into newly created invoice snapshots.', 'abill-invoice-generator-for-woocommerce' ); ?></p>
						</div>
					</div>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Invoice defaults', 'abill-invoice-generator-for-woocommerce' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr><th scope="row"><label for="abiwig-prefix"><?php esc_html_e( 'Invoice prefix', 'abill-invoice-generator-for-woocommerce' ); ?></label></th><td><input id="abiwig-prefix" type="text" maxlength="20" name="abiwig_settings[invoice_prefix]" value="<?php echo esc_attr( (string) $abiwig_settings['invoice_prefix'] ); ?>"></td></tr>
			<tr><th scope="row"><label for="abiwig-digits"><?php esc_html_e( 'Number digits', 'abill-invoice-generator-for-woocommerce' ); ?></label></th><td><input id="abiwig-digits" type="number" min="3" max="12" name="abiwig_settings[invoice_number_digits]" value="<?php echo esc_attr( absint( $abiwig_settings['invoice_number_digits'] ) ); ?>"></td></tr>
			<tr><th scope="row"><label for="abiwig-next-number"><?php esc_html_e( 'Next invoice sequence', 'abill-invoice-generator-for-woocommerce' ); ?></label></th><td><input id="abiwig-next-number" type="number" min="1" name="abiwig_next_invoice_number" value="<?php echo esc_attr( max( 1, absint( get_option( 'abiwig_next_invoice_number', 1 ) ) ) ); ?>"><p class="description"><?php esc_html_e( 'Only increase this value to avoid duplicate invoice numbers.', 'abill-invoice-generator-for-woocommerce' ); ?></p></td></tr>
			<tr><th scope="row"><label for="abiwig-due-days"><?php esc_html_e( 'Default due days', 'abill-invoice-generator-for-woocommerce' ); ?></label></th><td><input id="abiwig-due-days" type="number" min="0" max="3650" name="abiwig_settings[default_due_days]" value="<?php echo esc_attr( absint( $abiwig_settings['default_due_days'] ) ); ?>"></td></tr>
			<tr><th scope="row"><label for="abiwig-paper-size"><?php esc_html_e( 'PDF paper size', 'abill-invoice-generator-for-woocommerce' ); ?></label></th><td><select id="abiwig-paper-size" name="abiwig_settings[pdf_paper_size]"><option value="A4" <?php selected( $abiwig_settings['pdf_paper_size'], 'A4' ); ?>>A4</option><option value="LETTER" <?php selected( $abiwig_settings['pdf_paper_size'], 'LETTER' ); ?>>Letter</option></select></td></tr>
			<tr><th scope="row"><label for="abiwig-default-notes"><?php esc_html_e( 'Default notes', 'abill-invoice-generator-for-woocommerce' ); ?></label></th><td><textarea class="large-text" rows="4" id="abiwig-default-notes" name="abiwig_settings[default_notes]"><?php echo esc_textarea( (string) $abiwig_settings['default_notes'] ); ?></textarea></td></tr>
			<tr><th scope="row"><label for="abiwig-default-terms"><?php esc_html_e( 'Default terms', 'abill-invoice-generator-for-woocommerce' ); ?></label></th><td><textarea class="large-text" rows="4" id="abiwig-default-terms" name="abiwig_settings[default_terms]"><?php echo esc_textarea( (string) $abiwig_settings['default_terms'] ); ?></textarea></td></tr>
		</table>

		<h2><?php esc_html_e( 'Invoice email', 'abill-invoice-generator-for-woocommerce' ); ?></h2>
		<p><?php esc_html_e( 'ABill uses the standard WordPress mail system. Delivery depends on your hosting or an SMTP plugin configured separately.', 'abill-invoice-generator-for-woocommerce' ); ?></p>
		<table class="form-table" role="presentation">
			<tr><th scope="row"><label for="abiwig-email-subject"><?php esc_html_e( 'Email subject', 'abill-invoice-generator-for-woocommerce' ); ?></label></th><td><input class="large-text" id="abiwig-email-subject" type="text" name="abiwig_settings[email_subject]" value="<?php echo esc_attr( (string) $abiwig_settings['email_subject'] ); ?>"></td></tr>
			<tr><th scope="row"><label for="abiwig-email-message"><?php esc_html_e( 'Email message', 'abill-invoice-generator-for-woocommerce' ); ?></label></th><td><textarea class="large-text" rows="7" id="abiwig-email-message" name="abiwig_settings[email_message]"><?php echo esc_textarea( (string) $abiwig_settings['email_message'] ); ?></textarea><p class="description"><?php esc_html_e( 'Available placeholders: {invoice_number}, {order_number}, {customer_name}, {business_name}.', 'abill-invoice-generator-for-woocommerce' ); ?></p></td></tr>
		</table>

		<h2><?php esc_html_e( 'Data retention', 'abill-invoice-generator-for-woocommerce' ); ?></h2>
		<label><input type="checkbox" name="abiwig_settings[delete_data_on_uninstall]" value="yes" <?php checked( $abiwig_settings['delete_data_on_uninstall'], 'yes' ); ?>> <?php esc_html_e( 'Permanently delete ABill invoice data when the plugin is uninstalled.', 'abill-invoice-generator-for-woocommerce' ); ?></label>

		<?php submit_button(); ?>
	</form>
</div>

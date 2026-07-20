<?php
/**
 * HTML email template for invoice delivery.
 *
 * Themes may override this file by copying it to:
 * your-theme/abill-invoices/email-invoice.php
 *
 * Expected arguments:
 * - invoice: ABIWIG_Invoice object.
 * - message: Sanitized custom email message.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

$abiwig_invoice = isset( $args['invoice'] ) && $args['invoice'] instanceof ABIWIG_Invoice ? $args['invoice'] : null;
if ( ! $abiwig_invoice ) {
	return;
}

$abiwig_message  = isset( $args['message'] ) ? (string) $args['message'] : '';
$abiwig_business = $abiwig_invoice->get_business();
$abiwig_customer = $abiwig_invoice->get_customer();
$abiwig_totals   = $abiwig_invoice->get_totals();
$abiwig_currency = $abiwig_invoice->get_currency();
$abiwig_logo_id  = absint( $abiwig_business['logo_id'] ?? 0 );
$abiwig_logo_url = $abiwig_logo_id ? wp_get_attachment_image_url( $abiwig_logo_id, 'medium' ) : '';
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>
		<?php
		/* translators: %s: invoice number. */
		echo esc_html( sprintf( __( 'Invoice %s', 'abill-invoice-generator-for-woocommerce' ), $abiwig_invoice->get_number() ) );
		?>
	</title>
</head>
<body style="margin:0;padding:0;background:#f3f5f7;color:#17212b;font-family:Arial,Helvetica,sans-serif;line-height:1.6;">
	<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#f3f5f7;padding:24px 12px;">
		<tr>
			<td align="center">
				<table role="presentation" width="640" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:640px;background:#ffffff;border:1px solid #e1e5e9;border-radius:10px;overflow:hidden;">
					<tr>
						<td style="padding:28px 32px;background:#1f3a5f;color:#ffffff;">
							<?php if ( $abiwig_logo_url ) : ?>
								<img src="<?php echo esc_url( $abiwig_logo_url ); ?>" alt="<?php echo esc_attr( (string) ( $abiwig_business['name'] ?? '' ) ); ?>" style="display:block;max-width:180px;max-height:70px;margin:0 0 14px;">
							<?php endif; ?>
							<div style="font-size:22px;font-weight:700;"><?php echo esc_html( (string) ( $abiwig_business['name'] ?? __( 'Invoice', 'abill-invoice-generator-for-woocommerce' ) ) ); ?></div>
							<div style="margin-top:4px;opacity:.88;">
								<?php
								/* translators: %s: invoice number. */
								echo esc_html( sprintf( __( 'Invoice %s', 'abill-invoice-generator-for-woocommerce' ), $abiwig_invoice->get_number() ) );
								?>
							</div>
						</td>
					</tr>
					<tr>
						<td style="padding:30px 32px;">
							<?php if ( ! empty( $abiwig_customer['name'] ) ) : ?>
								<p style="margin:0 0 16px;">
									<?php
									/* translators: %s: customer name. */
									echo esc_html( sprintf( __( 'Hello %s,', 'abill-invoice-generator-for-woocommerce' ), $abiwig_customer['name'] ) );
									?>
								</p>
							<?php endif; ?>

							<div style="margin:0 0 24px;">
								<?php echo wp_kses_post( wpautop( esc_html( $abiwig_message ) ) ); ?>
							</div>

							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;margin:0 0 24px;border-collapse:collapse;border:1px solid #e1e5e9;">
								<tr>
									<td style="padding:12px 14px;background:#f7f8fa;font-weight:700;"><?php esc_html_e( 'Invoice number', 'abill-invoice-generator-for-woocommerce' ); ?></td>
									<td style="padding:12px 14px;text-align:right;"><?php echo esc_html( $abiwig_invoice->get_number() ); ?></td>
								</tr>
								<?php if ( $abiwig_invoice->get_order_number() ) : ?>
								<tr>
									<td style="padding:12px 14px;background:#f7f8fa;font-weight:700;border-top:1px solid #e1e5e9;"><?php esc_html_e( 'Order', 'abill-invoice-generator-for-woocommerce' ); ?></td>
									<td style="padding:12px 14px;text-align:right;border-top:1px solid #e1e5e9;"><?php echo esc_html( $abiwig_invoice->get_order_number() ); ?></td>
								</tr>
								<?php endif; ?>
								<tr>
									<td style="padding:12px 14px;background:#f7f8fa;font-weight:700;border-top:1px solid #e1e5e9;"><?php esc_html_e( 'Total', 'abill-invoice-generator-for-woocommerce' ); ?></td>
									<td style="padding:12px 14px;text-align:right;border-top:1px solid #e1e5e9;font-weight:700;color:#1f3a5f;"><?php echo wp_kses_post( wc_price( (float) ( $abiwig_totals['total'] ?? 0 ), array( 'currency' => $abiwig_currency ) ) ); ?></td>
								</tr>
							</table>

							<p style="margin:0;color:#5b6670;"><?php esc_html_e( 'A PDF copy of the invoice is attached to this email.', 'abill-invoice-generator-for-woocommerce' ); ?></p>
						</td>
					</tr>
					<tr>
						<td style="padding:18px 32px;background:#f7f8fa;color:#67717b;font-size:12px;text-align:center;">
							<?php echo esc_html( (string) ( $abiwig_business['name'] ?? '' ) ); ?>
							<?php if ( ! empty( $abiwig_business['email'] ) ) : ?> &middot; <?php echo esc_html( $abiwig_business['email'] ); ?><?php endif; ?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>

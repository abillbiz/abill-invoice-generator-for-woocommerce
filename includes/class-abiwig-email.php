<?php
/**
 * Invoice email delivery.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Send invoice emails using WordPress mail delivery.
 */
final class ABIWIG_Email {

	/** @var ABIWIG_Invoice_Repository */
	private $repository;

	/** @var ABIWIG_PDF */
	private $pdf;

	/**
	 * Constructor.
	 *
	 * @param ABIWIG_Invoice_Repository $repository Repository.
	 * @param ABIWIG_PDF                $pdf        PDF generator.
	 */
	public function __construct( ABIWIG_Invoice_Repository $repository, ABIWIG_PDF $pdf ) {
		$this->repository = $repository;
		$this->pdf        = $pdf;
	}

	/**
	 * Send one invoice.
	 *
	 * @param int    $invoice_id Invoice ID.
	 * @param string $recipient  Optional recipient override.
	 * @param string $subject    Optional subject override.
	 * @param string $message    Optional message override.
	 * @return true|WP_Error
	 */
	public function send( $invoice_id, $recipient = '', $subject = '', $message = '' ) {
		$invoice = $this->repository->get( $invoice_id );
		if ( ! $invoice ) {
			return new WP_Error( 'abiwig_invoice_not_found', __( 'Invoice not found.', 'abill-invoice-generator-for-woocommerce' ) );
		}

		$customer  = $invoice->get_customer();
		$recipient = $recipient ? sanitize_email( $recipient ) : sanitize_email( (string) ( $customer['email'] ?? '' ) );
		if ( ! is_email( $recipient ) ) {
			return new WP_Error( 'abiwig_invalid_recipient', __( 'A valid customer email address is required.', 'abill-invoice-generator-for-woocommerce' ) );
		}

		$tokens = array(
			'{invoice_number}' => $invoice->get_number(),
			'{order_number}'   => $invoice->get_order_number(),
			'{customer_name}'  => (string) ( $customer['name'] ?? '' ),
			'{business_name}'  => (string) ( $invoice->get_business()['name'] ?? '' ),
		);

		$subject = $subject ?: (string) abiwig_get_setting( 'email_subject', '' );
		$message = $message ?: (string) abiwig_get_setting( 'email_message', '' );
		$subject = sanitize_text_field( strtr( $subject, $tokens ) );
		$message = sanitize_textarea_field( strtr( $message, $tokens ) );

		$temp_file = $this->pdf->create_temp_file( $invoice );
		if ( is_wp_error( $temp_file ) ) {
			return $temp_file;
		}

		$body = abiwig_get_template_html(
			'email-invoice.php',
			array(
				'invoice'   => $invoice,
				'message'   => $message,
				'recipient' => $recipient,
			)
		);

		if ( '' === trim( $body ) ) {
			$body = wpautop( esc_html( $message ) );
		}

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		/**
		 * Filter the invoice email arguments.
		 *
		 * @param array<string, mixed> $email_args Email arguments.
		 * @param ABIWIG_Invoice       $invoice    Invoice object.
		 */
		$email_args = (array) apply_filters(
			'abiwig_invoice_email_args',
			array(
				'to'          => $recipient,
				'subject'     => $subject,
				'message'     => $body,
				'headers'     => $headers,
				'attachments' => array( $temp_file ),
			),
			$invoice
		);

		$sent = wp_mail(
			(string) $email_args['to'],
			(string) $email_args['subject'],
			(string) $email_args['message'],
			(array) $email_args['headers'],
			(array) $email_args['attachments']
		);

		wp_delete_file( $temp_file );

		if ( ! $sent ) {
			$this->log( 'error', 'Invoice email could not be sent.', array( 'invoice_id' => $invoice->get_id(), 'recipient' => $recipient ) );
			return new WP_Error( 'abiwig_email_failed', __( 'WordPress could not send the invoice email.', 'abill-invoice-generator-for-woocommerce' ) );
		}

		$this->repository->mark_sent( $invoice->get_id(), $recipient );
		$this->log( 'info', 'Invoice email sent.', array( 'invoice_id' => $invoice->get_id(), 'recipient' => $recipient ) );
		do_action( 'abiwig_invoice_emailed', $invoice->get_id(), $recipient );

		return true;
	}

	/**
	 * Write a WooCommerce log entry when the logger is available.
	 *
	 * @param string               $level   Log level.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @return void
	 */
	private function log( $level, $message, $context = array() ) {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		$context['source'] = 'abiwig';
		$logger            = wc_get_logger();

		if ( method_exists( $logger, $level ) ) {
			$logger->{$level}( $message, $context );
		}
	}
}

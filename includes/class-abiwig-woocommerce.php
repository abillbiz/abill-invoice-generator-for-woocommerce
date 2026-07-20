<?php
/**
 * WooCommerce integration.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build invoice snapshots from WooCommerce orders and expose order actions.
 */
final class ABIWIG_WooCommerce {

	/** @var ABIWIG_Invoice_Repository */
	private $repository;

	/** @var ABIWIG_Email */
	private $email;

	/**
	 * Constructor.
	 *
	 * @param ABIWIG_Invoice_Repository $repository Repository.
	 * @param ABIWIG_Email              $email      Email service.
	 */
	public function __construct( ABIWIG_Invoice_Repository $repository, ABIWIG_Email $email ) {
		$this->repository = $repository;
		$this->email      = $email;
	}

	/**
	 * Register WooCommerce hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'woocommerce_order_actions', array( $this, 'add_order_actions' ), 20, 2 );
		add_action( 'woocommerce_order_action_abiwig_create_invoice', array( $this, 'handle_create_order_action' ) );
		add_action( 'woocommerce_order_action_abiwig_email_invoice', array( $this, 'handle_email_order_action' ) );
	}

	/**
	 * Add ABill actions to the WooCommerce order action selector.
	 *
	 * @param array<string, string> $actions Order actions.
	 * @param WC_Order|null         $order   Order.
	 * @return array<string, string>
	 */
	public function add_order_actions( $actions, $order = null ) {
		if ( ! current_user_can( abiwig_manage_capability() ) ) {
			return $actions;
		}

		$actions['abiwig_create_invoice'] = __( 'Create ABill invoice', 'abill-invoice-generator-for-woocommerce' );

		if ( $order instanceof WC_Order && $this->repository->get_by_order_id( $order->get_id() ) ) {
			$actions['abiwig_email_invoice'] = __( 'Email ABill invoice', 'abill-invoice-generator-for-woocommerce' );
		}

		return $actions;
	}

	/**
	 * Create or retrieve an invoice for an order.
	 *
	 * @param int|WC_Order $order Order object or ID.
	 * @return ABIWIG_Invoice|WP_Error
	 */
	public function create_invoice_from_order( $order ) {
		$order = $order instanceof WC_Order ? $order : wc_get_order( absint( $order ) );
		if ( ! $order ) {
			return new WP_Error( 'abiwig_order_not_found', __( 'WooCommerce order not found.', 'abill-invoice-generator-for-woocommerce' ) );
		}

		$existing = $this->repository->get_by_order_id( $order->get_id() );
		if ( $existing ) {
			return $existing;
		}

		$invoice = $this->repository->create( $this->order_snapshot( $order ) );
		if ( is_wp_error( $invoice ) ) {
			return $invoice;
		}

		$order->update_meta_data( '_abiwig_invoice_id', $invoice->get_id() );
		$order->save();

		return $invoice;
	}

	/**
	 * Create invoice from WooCommerce's protected order action request.
	 *
	 * @param WC_Order $order Order.
	 * @return void
	 */
	public function handle_create_order_action( $order ) {
		if ( ! current_user_can( abiwig_manage_capability() ) || ! $order instanceof WC_Order ) {
			return;
		}

		$result = $this->create_invoice_from_order( $order );
		if ( is_wp_error( $result ) ) {
			$order->add_order_note( sprintf( 'ABill: %s', $result->get_error_message() ) );
			return;
		}

		$order->add_order_note(
			sprintf(
				/* translators: %s: invoice number. */
				__( 'ABill invoice %s is ready.', 'abill-invoice-generator-for-woocommerce' ),
				$result->get_number()
			)
		);
	}

	/**
	 * Email an existing invoice from WooCommerce's protected order action request.
	 *
	 * @param WC_Order $order Order.
	 * @return void
	 */
	public function handle_email_order_action( $order ) {
		if ( ! current_user_can( abiwig_manage_capability() ) || ! $order instanceof WC_Order ) {
			return;
		}

		$invoice = $this->repository->get_by_order_id( $order->get_id() );
		if ( ! $invoice ) {
			$order->add_order_note( __( 'ABill invoice could not be emailed because no invoice exists.', 'abill-invoice-generator-for-woocommerce' ) );
			return;
		}

		$result = $this->email->send( $invoice->get_id(), $order->get_billing_email() );
		if ( is_wp_error( $result ) ) {
			$order->add_order_note( sprintf( 'ABill: %s', $result->get_error_message() ) );
			return;
		}

		$order->add_order_note(
			sprintf(
				/* translators: %s: customer email. */
				__( 'ABill invoice emailed to %s.', 'abill-invoice-generator-for-woocommerce' ),
				$order->get_billing_email()
			)
		);
	}

	/**
	 * Create a legal snapshot from WooCommerce CRUD getters.
	 *
	 * @param WC_Order $order WooCommerce order.
	 * @return array<string, mixed>
	 */
	private function order_snapshot( WC_Order $order ) {
		$items = array();

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$product = $item->get_product();
			$items[] = array(
				'name'     => $item->get_name(),
				'sku'      => $product ? $product->get_sku() : '',
				'quantity' => $item->get_quantity(),
				'subtotal' => $item->get_subtotal(),
				'tax'      => $item->get_total_tax(),
				'total'    => $item->get_total(),
			);
		}

		foreach ( $order->get_items( 'fee' ) as $fee ) {
			$items[] = array(
				'name'     => $fee->get_name(),
				'sku'      => '',
				'quantity' => 1,
				'subtotal' => $fee->get_total(),
				'tax'      => $fee->get_total_tax(),
				'total'    => $fee->get_total(),
			);
		}

		$invoice_date = wp_date( 'Y-m-d' );
		$due_days     = max( 0, absint( abiwig_get_setting( 'default_due_days', 0 ) ) );
		$due_date     = $due_days ? wp_date( 'Y-m-d', strtotime( '+' . $due_days . ' days' ) ) : '';
		$first_name   = $order->get_billing_first_name();
		$last_name    = $order->get_billing_last_name();
		$full_name    = trim( $first_name . ' ' . $last_name );

		$snapshot = array(
			'order_id'        => $order->get_id(),
			'order_number'    => $order->get_order_number(),
			'invoice_date'    => $invoice_date,
			'due_date'        => $due_date,
			'status'          => 'draft',
			'currency'        => $order->get_currency(),
			'payment_method'  => $order->get_payment_method_title(),
			'shipping_method' => $order->get_shipping_method(),
			'business'        => ABIWIG_Settings::business_snapshot(),
			'customer'        => array(
				'name'      => $full_name ?: $order->get_billing_company(),
				'company'   => $order->get_billing_company(),
				'address_1' => $order->get_billing_address_1(),
				'address_2' => $order->get_billing_address_2(),
				'city'      => $order->get_billing_city(),
				'state'     => $order->get_billing_state(),
				'postcode'  => $order->get_billing_postcode(),
				'country'   => $order->get_billing_country(),
				'email'     => $order->get_billing_email(),
				'phone'     => $order->get_billing_phone(),
				'tax_id'    => (string) $order->get_meta( '_billing_tax_id', true ),
			),
			'items'           => $items,
			'totals'          => array(
				'subtotal' => $order->get_subtotal(),
				'discount' => $order->get_discount_total(),
				'shipping' => $order->get_shipping_total(),
				'tax'      => $order->get_total_tax(),
				'total'    => $order->get_total(),
			),
			'notes'           => (string) abiwig_get_setting( 'default_notes', '' ),
			'terms'           => (string) abiwig_get_setting( 'default_terms', '' ),
			'created_at'      => current_time( 'mysql', true ),
		);

		/**
		 * Filter the invoice snapshot created from a WooCommerce order.
		 *
		 * @param array<string, mixed> $snapshot Invoice snapshot.
		 * @param WC_Order             $order    WooCommerce order.
		 */
		return (array) apply_filters( 'abiwig_order_invoice_snapshot', $snapshot, $order );
	}
}

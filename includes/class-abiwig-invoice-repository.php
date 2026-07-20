<?php
/**
 * Invoice persistence repository.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Store and retrieve invoice snapshots using a private custom post type.
 */
final class ABIWIG_Invoice_Repository {

	/** Invoice post type. */
	const POST_TYPE = 'abiwig_invoice';

	/** Main snapshot meta key. */
	const META_DATA = '_abiwig_invoice_data';

	/** Invoice post status prefix. */
	const STATUS_PREFIX = 'abiwig_';

	/**
	 * Register the private invoice post type.
	 *
	 * @return void
	 */
	public static function register_post_type() {
		self::register_invoice_statuses();

		register_post_type(
			self::POST_TYPE,
			array(
				'labels' => array(
					'name'          => __( 'ABill Invoices', 'abill-invoice-generator-for-woocommerce' ),
					'singular_name' => __( 'ABill Invoice', 'abill-invoice-generator-for-woocommerce' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'exclude_from_search' => true,
				'supports'            => array( 'title', 'author' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);
	}


	/**
	 * Register private statuses used by invoice records.
	 *
	 * @return void
	 */
	private static function register_invoice_statuses() {
		$statuses = array(
			'draft'     => __( 'Draft', 'abill-invoice-generator-for-woocommerce' ),
			'sent'      => __( 'Sent', 'abill-invoice-generator-for-woocommerce' ),
			'paid'      => __( 'Paid', 'abill-invoice-generator-for-woocommerce' ),
			'cancelled' => __( 'Cancelled', 'abill-invoice-generator-for-woocommerce' ),
		);

		foreach ( $statuses as $status => $label ) {
			register_post_status(
				self::post_status_for_invoice_status( $status ),
				array(
					'label'                     => $label,
					'public'                    => false,
					'internal'                  => true,
					'exclude_from_search'       => true,
					'show_in_admin_all_list'    => false,
					'show_in_admin_status_list' => false,
				)
			);
		}
	}

	/**
	 * Return the WordPress post status used for an invoice status.
	 *
	 * @param string $status Invoice status.
	 * @return string
	 */
	public static function post_status_for_invoice_status( $status ) {
		$status = sanitize_key( (string) $status );
		if ( ! in_array( $status, array( 'draft', 'sent', 'paid', 'cancelled' ), true ) ) {
			$status = 'draft';
		}

		return self::STATUS_PREFIX . $status;
	}

	/**
	 * Return all post statuses used by invoice records.
	 *
	 * Standard statuses are included for compatibility with early test data.
	 *
	 * @return array<int, string>
	 */
	public static function invoice_post_statuses() {
		return array(
			self::post_status_for_invoice_status( 'draft' ),
			self::post_status_for_invoice_status( 'sent' ),
			self::post_status_for_invoice_status( 'paid' ),
			self::post_status_for_invoice_status( 'cancelled' ),
			'publish',
			'draft',
			'private',
			'pending',
		);
	}

	/**
	 * Build the unique post slug used to index an order invoice.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return string
	 */
	private static function order_invoice_slug( $order_id ) {
		return 'order-' . absint( $order_id );
	}

	/**
	 * Create an invoice.
	 *
	 * @param array<string, mixed> $data Invoice data.
	 * @return ABIWIG_Invoice|WP_Error
	 */
	public function create( $data ) {
		$data = abiwig_sanitize_invoice_data( $data );

		if ( empty( $data['invoice_number'] ) ) {
			$data['invoice_number'] = $this->next_invoice_number();
		}

		$title = sprintf(
			/* translators: 1: invoice number, 2: customer name. */
			__( 'Invoice %1$s — %2$s', 'abill-invoice-generator-for-woocommerce' ),
			$data['invoice_number'],
			$data['customer']['name'] ?: __( 'Customer', 'abill-invoice-generator-for-woocommerce' )
		);

		$invoice_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $title,
				'post_author' => get_current_user_id(),
			),
			true
		);

		if ( is_wp_error( $invoice_id ) ) {
			return $invoice_id;
		}

		$this->save_meta( $invoice_id, $data );

		/**
		 * Fires after an invoice is created.
		 *
		 * @param int                  $invoice_id Invoice ID.
		 * @param array<string, mixed> $data       Invoice data.
		 */
		do_action( 'abiwig_invoice_created', $invoice_id, $data );

		return new ABIWIG_Invoice( $invoice_id, $data );
	}

	/**
	 * Update an invoice.
	 *
	 * @param int                  $invoice_id Invoice ID.
	 * @param array<string, mixed> $changes    Changed data.
	 * @return ABIWIG_Invoice|WP_Error
	 */
	public function update( $invoice_id, $changes ) {
		$invoice = $this->get( $invoice_id );
		if ( ! $invoice ) {
			return new WP_Error( 'abiwig_invoice_not_found', __( 'Invoice not found.', 'abill-invoice-generator-for-woocommerce' ) );
		}

		$data = abiwig_sanitize_invoice_data( array_replace( $invoice->get_data(), is_array( $changes ) ? $changes : array() ) );

		$post_result = wp_update_post(
			array(
				'ID'         => $invoice->get_id(),
				'post_title' => sprintf(
					/* translators: 1: invoice number, 2: customer name. */
					__( 'Invoice %1$s — %2$s', 'abill-invoice-generator-for-woocommerce' ),
					$data['invoice_number'],
					$data['customer']['name'] ?: __( 'Customer', 'abill-invoice-generator-for-woocommerce' )
				),
			),
			true
		);

		if ( is_wp_error( $post_result ) ) {
			return $post_result;
		}

		$this->save_meta( $invoice->get_id(), $data );

		do_action( 'abiwig_invoice_updated', $invoice->get_id(), $data );

		return new ABIWIG_Invoice( $invoice->get_id(), $data );
	}

	/**
	 * Retrieve an invoice.
	 *
	 * @param int $invoice_id Invoice ID.
	 * @return ABIWIG_Invoice|null
	 */
	public function get( $invoice_id ) {
		$invoice_id = absint( $invoice_id );
		$post       = get_post( $invoice_id );

		if ( ! $post || self::POST_TYPE !== $post->post_type || 'trash' === $post->post_status ) {
			return null;
		}

		$data = get_post_meta( $invoice_id, self::META_DATA, true );
		if ( ! is_array( $data ) ) {
			return null;
		}

		return new ABIWIG_Invoice( $invoice_id, $data );
	}

	/**
	 * Retrieve an invoice by WooCommerce order ID.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return ABIWIG_Invoice|null
	 */
	public function get_by_order_id( $order_id ) {
		$order_id = absint( $order_id );
		if ( ! $order_id ) {
			return null;
		}

		$post = get_page_by_path(
			self::order_invoice_slug( $order_id ),
			OBJECT,
			self::POST_TYPE
		);

		return $post instanceof WP_Post ? $this->get( (int) $post->ID ) : null;
	}

	/**
	 * Query invoices for future admin list screens.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return WP_Query
	 */
	public function query( $args = array() ) {
		$defaults = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => self::invoice_post_statuses(),
			'posts_per_page' => 20,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		return new WP_Query( wp_parse_args( $args, $defaults ) );
	}

	/**
	 * Move an invoice to trash.
	 *
	 * @param int $invoice_id Invoice ID.
	 * @return bool
	 */
	public function trash( $invoice_id ) {
		$invoice = $this->get( $invoice_id );
		if ( ! $invoice ) {
			return false;
		}

		return false !== wp_trash_post( $invoice->get_id() );
	}

	/**
	 * Permanently delete an invoice.
	 *
	 * @param int $invoice_id Invoice ID.
	 * @return bool
	 */
	public function delete( $invoice_id ) {
		$post = wp_delete_post( absint( $invoice_id ), true );
		return null !== $post && false !== $post;
	}

	/**
	 * Mark an invoice as sent.
	 *
	 * @param int    $invoice_id Invoice ID.
	 * @param string $recipient  Recipient email.
	 * @return ABIWIG_Invoice|WP_Error
	 */
	public function mark_sent( $invoice_id, $recipient ) {
		return $this->update(
			$invoice_id,
			array(
				'status'       => 'sent',
				'sent_at'      => current_time( 'mysql', true ),
				'sent_to'      => sanitize_email( $recipient ),
			)
		);
	}

	/**
	 * Persist the snapshot and indexed fields.
	 *
	 * @param int                  $invoice_id Invoice ID.
	 * @param array<string, mixed> $data       Invoice data.
	 * @return void
	 */
	private function save_meta( $invoice_id, $data ) {
		update_post_meta( $invoice_id, self::META_DATA, wp_slash( $data ) );
		update_post_meta( $invoice_id, '_abiwig_order_id', absint( $data['order_id'] ) );
		update_post_meta( $invoice_id, '_abiwig_invoice_number', sanitize_text_field( $data['invoice_number'] ) );
		update_post_meta( $invoice_id, '_abiwig_invoice_date', sanitize_text_field( $data['invoice_date'] ) );
		update_post_meta( $invoice_id, '_abiwig_status', sanitize_key( $data['status'] ) );
	}

	/**
	 * Reserve and format the next invoice number.
	 *
	 * @return string
	 */
	private function next_invoice_number() {
		$lock_name = 'abiwig_invoice_number_lock';
		$acquired  = false;

		for ( $attempt = 0; $attempt < 10; $attempt++ ) {
			$acquired = add_option( $lock_name, time(), '', false );
			if ( $acquired ) {
				break;
			}

			$lock_time = absint( get_option( $lock_name, 0 ) );
			if ( $lock_time && ( time() - $lock_time ) > 15 ) {
				delete_option( $lock_name );
			}

			usleep( 50000 );
		}

		$sequence = max( 1, absint( get_option( 'abiwig_next_invoice_number', 1 ) ) );
		update_option( 'abiwig_next_invoice_number', $sequence + 1, false );

		if ( $acquired ) {
			delete_option( $lock_name );
		}

		return abiwig_format_invoice_number( $sequence );
	}
}

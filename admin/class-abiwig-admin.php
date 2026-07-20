<?php
/**
 * WordPress admin controller.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register and process ABill invoice administration screens.
 */
final class ABIWIG_Admin {

	/** Main invoices page slug. */
	const PAGE_INVOICES = 'abiwig-invoices';

	/** Invoice edit page slug. */
	const PAGE_EDIT = 'abiwig-invoice-edit';

	/** Invoice view page slug. */
	const PAGE_VIEW = 'abiwig-invoice-view';

	/** Settings page slug. */
	const PAGE_SETTINGS = 'abiwig-settings';

	/** @var ABIWIG_Invoice_Repository */
	private $repository;

	/** @var ABIWIG_PDF */
	private $pdf;

	/** @var ABIWIG_Email */
	private $email;

	/** @var ABIWIG_WooCommerce */
	private $woocommerce;

	/**
	 * Constructor.
	 *
	 * @param ABIWIG_Invoice_Repository $repository  Repository.
	 * @param ABIWIG_PDF                $pdf         PDF service.
	 * @param ABIWIG_Email              $email       Email service.
	 * @param ABIWIG_WooCommerce        $woocommerce WooCommerce integration.
	 */
	public function __construct( ABIWIG_Invoice_Repository $repository, ABIWIG_PDF $pdf, ABIWIG_Email $email, ABIWIG_WooCommerce $woocommerce ) {
		$this->repository  = $repository;
		$this->pdf         = $pdf;
		$this->email       = $email;
		$this->woocommerce = $woocommerce;
	}

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		add_action( 'admin_post_abiwig_create_invoice', array( $this, 'handle_create_invoice' ) );
		add_action( 'admin_post_abiwig_save_invoice', array( $this, 'handle_save_invoice' ) );
		add_action( 'admin_post_abiwig_trash_invoice', array( $this, 'handle_trash_invoice' ) );
		add_action( 'admin_post_abiwig_email_invoice', array( $this, 'handle_email_invoice' ) );
		add_action( 'admin_post_abiwig_download_invoice', array( $this, 'handle_download_invoice' ) );
		add_action( 'admin_post_abiwig_print_invoice', array( $this, 'handle_print_invoice' ) );
	}

	/**
	 * Register plugin administration pages.
	 *
	 * @return void
	 */
	public function register_menu() {
		$capability = abiwig_manage_capability();

		add_menu_page(
			__( 'ABill Invoices', 'abill-invoice-generator-for-woocommerce' ),
			__( 'ABill Invoices', 'abill-invoice-generator-for-woocommerce' ),
			$capability,
			self::PAGE_INVOICES,
			array( $this, 'render_invoices_page' ),
			'dashicons-media-spreadsheet',
			56
		);

		add_submenu_page(
			self::PAGE_INVOICES,
			__( 'Invoices', 'abill-invoice-generator-for-woocommerce' ),
			__( 'Invoices', 'abill-invoice-generator-for-woocommerce' ),
			$capability,
			self::PAGE_INVOICES,
			array( $this, 'render_invoices_page' )
		);

		add_submenu_page(
			self::PAGE_INVOICES,
			__( 'Settings', 'abill-invoice-generator-for-woocommerce' ),
			__( 'Settings', 'abill-invoice-generator-for-woocommerce' ),
			$capability,
			self::PAGE_SETTINGS,
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			null,
			__( 'Edit Invoice', 'abill-invoice-generator-for-woocommerce' ),
			__( 'Edit Invoice', 'abill-invoice-generator-for-woocommerce' ),
			$capability,
			self::PAGE_EDIT,
			array( $this, 'render_edit_page' )
		);

		add_submenu_page(
			null,
			__( 'View Invoice', 'abill-invoice-generator-for-woocommerce' ),
			__( 'View Invoice', 'abill-invoice-generator-for-woocommerce' ),
			$capability,
			self::PAGE_VIEW,
			array( $this, 'render_view_page' )
		);
	}

	/**
	 * Enqueue admin assets only on ABill pages.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		unset( $hook_suffix );

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Used only to decide whether to enqueue static assets.
		if ( ! in_array( $page, array( self::PAGE_INVOICES, self::PAGE_EDIT, self::PAGE_VIEW, self::PAGE_SETTINGS ), true ) ) {
			return;
		}

		$style_path = ABIWIG_PATH . 'assets/css/admin.css';
		if ( file_exists( $style_path ) ) {
			wp_enqueue_style(
				'abiwig-admin',
				ABIWIG_URL . 'assets/css/admin.css',
				array(),
				(string) filemtime( $style_path )
			);
		}

		if ( self::PAGE_VIEW === $page ) {
			$invoice_style_path = ABIWIG_PATH . 'assets/css/invoice.css';
			if ( file_exists( $invoice_style_path ) ) {
				wp_enqueue_style(
					'abiwig-invoice',
					ABIWIG_URL . 'assets/css/invoice.css',
					array( 'abiwig-admin' ),
					(string) filemtime( $invoice_style_path )
				);
			}
		}

		$script_path = ABIWIG_PATH . 'assets/js/admin.js';
		if ( file_exists( $script_path ) ) {
			wp_enqueue_script(
				'abiwig-admin',
				ABIWIG_URL . 'assets/js/admin.js',
				array(),
				(string) filemtime( $script_path ),
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);

			wp_localize_script(
				'abiwig-admin',
				'ABIWIGAdmin',
				array(
					'confirmDelete' => __( 'Move this invoice to Trash?', 'abill-invoice-generator-for-woocommerce' ),
					'confirmSend'   => __( 'Send this invoice to the customer email address?', 'abill-invoice-generator-for-woocommerce' ),
					'printNow'      => __( 'Print invoice', 'abill-invoice-generator-for-woocommerce' ),
					'addItem'       => __( 'Add item', 'abill-invoice-generator-for-woocommerce' ),
					'removeItem'    => __( 'Remove item', 'abill-invoice-generator-for-woocommerce' ),
					'recalculate'   => __( 'Recalculate totals', 'abill-invoice-generator-for-woocommerce' ),
					'recalculated'  => __( 'Totals recalculated.', 'abill-invoice-generator-for-woocommerce' ),
					'selectLogo'    => __( 'Select business logo', 'abill-invoice-generator-for-woocommerce' ),
					'useLogo'       => __( 'Use this logo', 'abill-invoice-generator-for-woocommerce' ),
				)
			);
		}

		if ( self::PAGE_SETTINGS === $page ) {
			wp_enqueue_media();
		}
	}

	/**
	 * Render the invoice list page.
	 *
	 * @return void
	 */
	public function render_invoices_page() {
		$this->require_capability();

		$abiwig_paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list pagination.
		$abiwig_status = isset( $_GET['invoice_status'] ) ? sanitize_key( wp_unslash( $_GET['invoice_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filter.
		$abiwig_search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only search filter.

		$abiwig_args = array(
			'paged'          => $abiwig_paged,
			'posts_per_page' => 20,
		);

		if ( '' !== $abiwig_search ) {
			$abiwig_args['s'] = $abiwig_search;
		}

		if ( in_array( $abiwig_status, array( 'draft', 'sent', 'paid', 'cancelled' ), true ) ) {
			$abiwig_args['post_status'] = ABIWIG_Invoice_Repository::post_status_for_invoice_status( $abiwig_status );
		}

		$abiwig_query      = $this->repository->query( $abiwig_args );
		$abiwig_repository = $this->repository;
		$abiwig_notice     = $this->notice();
		$abiwig_base_url   = $this->page_url( self::PAGE_INVOICES );

		include ABIWIG_PATH . 'admin/views/invoices-list.php';
	}

	/**
	 * Render invoice edit page.
	 *
	 * @return void
	 */
	public function render_edit_page() {
		$this->require_capability();
		$abiwig_invoice = $this->requested_invoice( 'abiwig_edit_invoice' );
		$abiwig_notice  = $this->notice();

		include ABIWIG_PATH . 'admin/views/invoice-edit.php';
	}

	/**
	 * Render invoice view page.
	 *
	 * @return void
	 */
	public function render_view_page() {
		$this->require_capability();
		$abiwig_invoice = $this->requested_invoice( 'abiwig_view_invoice' );
		$abiwig_notice  = $this->notice();

		include ABIWIG_PATH . 'admin/views/invoice-view.php';
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		$this->require_capability();
		$abiwig_settings = ABIWIG_Settings::get_all();
		include ABIWIG_PATH . 'admin/views/settings.php';
	}

	/**
	 * Create an invoice from a WooCommerce order ID.
	 *
	 * @return void
	 */
	public function handle_create_invoice() {
		$this->require_capability();
		check_admin_referer( 'abiwig_create_invoice' );

		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
		if ( ! $order_id ) {
			$this->redirect( self::PAGE_INVOICES, 'invalid_order' );
		}

		$result = $this->woocommerce->create_invoice_from_order( $order_id );
		if ( is_wp_error( $result ) ) {
			$this->redirect( self::PAGE_INVOICES, 'error', array( 'abiwig_error' => $result->get_error_code() ) );
		}

		$this->redirect(
			self::PAGE_VIEW,
			'created',
			array(
				'invoice_id' => $result->get_id(),
				'_wpnonce'   => wp_create_nonce( 'abiwig_view_invoice_' . $result->get_id() ),
			)
		);
	}

	/**
	 * Save invoice edits.
	 *
	 * @return void
	 */
	public function handle_save_invoice() {
		$this->require_capability();

		$invoice_id = isset( $_POST['invoice_id'] ) ? absint( wp_unslash( $_POST['invoice_id'] ) ) : 0;
		check_admin_referer( 'abiwig_save_invoice_' . $invoice_id );

		if ( ! $invoice_id || ! $this->repository->get( $invoice_id ) ) {
			$this->redirect( self::PAGE_INVOICES, 'not_found' );
		}

		$invoice_input = isset( $_POST['invoice'] ) ? map_deep( wp_unslash( (array) $_POST['invoice'] ), 'sanitize_textarea_field' ) : array();
		$changes       = $this->sanitize_invoice_form( $invoice_input );
		$result        = $this->repository->update( $invoice_id, $changes );

		if ( is_wp_error( $result ) ) {
			$this->redirect(
				self::PAGE_EDIT,
				'error',
				array(
					'invoice_id'   => $invoice_id,
					'_wpnonce'     => wp_create_nonce( 'abiwig_edit_invoice_' . $invoice_id ),
					'abiwig_error' => $result->get_error_code(),
				)
			);
		}

		$this->redirect(
			self::PAGE_VIEW,
			'updated',
			array(
				'invoice_id' => $invoice_id,
				'_wpnonce'   => wp_create_nonce( 'abiwig_view_invoice_' . $invoice_id ),
			)
		);
	}

	/**
	 * Move an invoice to Trash.
	 *
	 * @return void
	 */
	public function handle_trash_invoice() {
		$this->require_capability();
		$invoice_id = isset( $_POST['invoice_id'] ) ? absint( wp_unslash( $_POST['invoice_id'] ) ) : 0;
		check_admin_referer( 'abiwig_trash_invoice_' . $invoice_id );

		if ( ! $invoice_id || ! $this->repository->trash( $invoice_id ) ) {
			$this->redirect( self::PAGE_INVOICES, 'delete_failed' );
		}

		$this->redirect( self::PAGE_INVOICES, 'deleted' );
	}

	/**
	 * Email an invoice.
	 *
	 * @return void
	 */
	public function handle_email_invoice() {
		$this->require_capability();
		$invoice_id = isset( $_POST['invoice_id'] ) ? absint( wp_unslash( $_POST['invoice_id'] ) ) : 0;
		check_admin_referer( 'abiwig_email_invoice_' . $invoice_id );

		$recipient = isset( $_POST['recipient'] ) ? sanitize_email( wp_unslash( $_POST['recipient'] ) ) : '';
		$subject   = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		$message   = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$result    = $this->email->send( $invoice_id, $recipient, $subject, $message );

		if ( is_wp_error( $result ) ) {
			$this->redirect(
				self::PAGE_VIEW,
				'email_failed',
				array(
					'invoice_id'   => $invoice_id,
					'_wpnonce'     => wp_create_nonce( 'abiwig_view_invoice_' . $invoice_id ),
					'abiwig_error' => $result->get_error_code(),
				)
			);
		}

		$this->redirect(
			self::PAGE_VIEW,
			'emailed',
			array(
				'invoice_id' => $invoice_id,
				'_wpnonce'   => wp_create_nonce( 'abiwig_view_invoice_' . $invoice_id ),
			)
		);
	}

	/**
	 * Download a generated PDF.
	 *
	 * @return void
	 */
	public function handle_download_invoice() {
		$this->require_capability();
		$invoice_id = isset( $_GET['invoice_id'] ) ? absint( wp_unslash( $_GET['invoice_id'] ) ) : 0;
		check_admin_referer( 'abiwig_download_invoice_' . $invoice_id );

		$invoice = $this->repository->get( $invoice_id );
		if ( ! $invoice ) {
			wp_die( esc_html__( 'Invoice not found.', 'abill-invoice-generator-for-woocommerce' ) );
		}

		$pdf = $this->pdf->generate( $invoice );
		if ( is_wp_error( $pdf ) ) {
			wp_die( esc_html( $pdf->get_error_message() ) );
		}

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . $this->pdf->filename( $invoice ) . '"' );
		header( 'Content-Length: ' . strlen( $pdf ) );
		echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary PDF output.
		exit;
	}

	/**
	 * Render a standalone printable invoice document.
	 *
	 * @return void
	 */
	public function handle_print_invoice() {
		$this->require_capability();
		$invoice_id = isset( $_GET['invoice_id'] ) ? absint( wp_unslash( $_GET['invoice_id'] ) ) : 0;
		check_admin_referer( 'abiwig_print_invoice_' . $invoice_id );

		$abiwig_invoice = $this->repository->get( $invoice_id );
		if ( ! $abiwig_invoice ) {
			wp_die( esc_html__( 'Invoice not found.', 'abill-invoice-generator-for-woocommerce' ) );
		}

		$style_path = ABIWIG_PATH . 'assets/css/print.css';
		if ( file_exists( $style_path ) ) {
			wp_enqueue_style( 'abiwig-print', ABIWIG_URL . 'assets/css/print.css', array(), (string) filemtime( $style_path ) );
		}

		$script_path = ABIWIG_PATH . 'assets/js/admin.js';
		if ( file_exists( $script_path ) ) {
			wp_enqueue_script(
				'abiwig-print',
				ABIWIG_URL . 'assets/js/admin.js',
				array(),
				(string) filemtime( $script_path ),
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
			wp_localize_script(
				'abiwig-print',
				'ABIWIGAdmin',
				array(
					'printNow' => __( 'Print invoice', 'abill-invoice-generator-for-woocommerce' ),
				)
			);
		}

		nocache_headers();
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		include ABIWIG_PATH . 'admin/views/print.php';
		exit;
	}

	/**
	 * Sanitize the invoice edit form.
	 *
	 * @param array<string, mixed> $input Form input.
	 * @return array<string, mixed>
	 */
	private function sanitize_invoice_form( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$business = isset( $input['business'] ) && is_array( $input['business'] ) ? $input['business'] : array();
		$customer = isset( $input['customer'] ) && is_array( $input['customer'] ) ? $input['customer'] : array();
		$totals   = isset( $input['totals'] ) && is_array( $input['totals'] ) ? $input['totals'] : array();
		$items    = isset( $input['items'] ) && is_array( $input['items'] ) ? $input['items'] : array();
		$clean_items = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || ! empty( $item['remove'] ) ) {
				continue;
			}

			$name = sanitize_text_field( (string) ( $item['name'] ?? '' ) );
			if ( '' === $name ) {
				continue;
			}

			$clean_items[] = array(
				'name'     => $name,
				'sku'      => sanitize_text_field( (string) ( $item['sku'] ?? '' ) ),
				'quantity' => max( 0, (float) ( $item['quantity'] ?? 0 ) ),
				'subtotal' => abiwig_normalize_amount( $item['subtotal'] ?? 0 ),
				'tax'      => abiwig_normalize_amount( $item['tax'] ?? 0 ),
				'total'    => abiwig_normalize_amount( $item['total'] ?? 0 ),
			);
		}

		$status = sanitize_key( (string) ( $input['status'] ?? 'draft' ) );
		if ( ! in_array( $status, array( 'draft', 'sent', 'paid', 'cancelled' ), true ) ) {
			$status = 'draft';
		}

		return array(
			'invoice_number' => sanitize_text_field( (string) ( $input['invoice_number'] ?? '' ) ),
			'invoice_date'   => abiwig_normalize_date( $input['invoice_date'] ?? '', wp_date( 'Y-m-d' ) ),
			'due_date'       => abiwig_normalize_date( $input['due_date'] ?? '', '' ),
			'status'         => $status,
			'currency'       => sanitize_text_field( strtoupper( (string) ( $input['currency'] ?? '' ) ) ),
			'payment_method' => sanitize_text_field( (string) ( $input['payment_method'] ?? '' ) ),
			'shipping_method'=> sanitize_text_field( (string) ( $input['shipping_method'] ?? '' ) ),
			'business'       => array(
				'name'      => sanitize_text_field( (string) ( $business['name'] ?? '' ) ),
				'address_1' => sanitize_text_field( (string) ( $business['address_1'] ?? '' ) ),
				'address_2' => sanitize_text_field( (string) ( $business['address_2'] ?? '' ) ),
				'city'      => sanitize_text_field( (string) ( $business['city'] ?? '' ) ),
				'state'     => sanitize_text_field( (string) ( $business['state'] ?? '' ) ),
				'postcode'  => sanitize_text_field( (string) ( $business['postcode'] ?? '' ) ),
				'country'   => sanitize_text_field( (string) ( $business['country'] ?? '' ) ),
				'tax_id'    => sanitize_text_field( (string) ( $business['tax_id'] ?? '' ) ),
				'email'     => sanitize_email( (string) ( $business['email'] ?? '' ) ),
				'phone'     => sanitize_text_field( (string) ( $business['phone'] ?? '' ) ),
				'logo_id'   => absint( $business['logo_id'] ?? 0 ),
			),
			'customer'       => array(
				'name'      => sanitize_text_field( (string) ( $customer['name'] ?? '' ) ),
				'company'   => sanitize_text_field( (string) ( $customer['company'] ?? '' ) ),
				'address_1' => sanitize_text_field( (string) ( $customer['address_1'] ?? '' ) ),
				'address_2' => sanitize_text_field( (string) ( $customer['address_2'] ?? '' ) ),
				'city'      => sanitize_text_field( (string) ( $customer['city'] ?? '' ) ),
				'state'     => sanitize_text_field( (string) ( $customer['state'] ?? '' ) ),
				'postcode'  => sanitize_text_field( (string) ( $customer['postcode'] ?? '' ) ),
				'country'   => sanitize_text_field( (string) ( $customer['country'] ?? '' ) ),
				'email'     => sanitize_email( (string) ( $customer['email'] ?? '' ) ),
				'phone'     => sanitize_text_field( (string) ( $customer['phone'] ?? '' ) ),
				'tax_id'    => sanitize_text_field( (string) ( $customer['tax_id'] ?? '' ) ),
			),
			'items'          => $clean_items,
			'totals'         => array(
				'subtotal' => abiwig_normalize_amount( $totals['subtotal'] ?? 0 ),
				'discount' => abiwig_normalize_amount( $totals['discount'] ?? 0 ),
				'shipping' => abiwig_normalize_amount( $totals['shipping'] ?? 0 ),
				'tax'      => abiwig_normalize_amount( $totals['tax'] ?? 0 ),
				'total'    => abiwig_normalize_amount( $totals['total'] ?? 0 ),
			),
			'notes'          => sanitize_textarea_field( (string) ( $input['notes'] ?? '' ) ),
			'terms'          => sanitize_textarea_field( (string) ( $input['terms'] ?? '' ) ),
		);
	}

	/**
	 * Return requested invoice after nonce validation.
	 *
	 * @param string $action_prefix Nonce action prefix.
	 * @return ABIWIG_Invoice
	 */
	private function requested_invoice( $action_prefix ) {
		$invoice_id = isset( $_GET['invoice_id'] ) ? absint( wp_unslash( $_GET['invoice_id'] ) ) : 0;
		$nonce      = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! $invoice_id || ! wp_verify_nonce( $nonce, $action_prefix . '_' . $invoice_id ) ) {
			wp_die( esc_html__( 'The invoice link is invalid or has expired.', 'abill-invoice-generator-for-woocommerce' ) );
		}

		$invoice = $this->repository->get( $invoice_id );
		if ( ! $invoice ) {
			wp_die( esc_html__( 'Invoice not found.', 'abill-invoice-generator-for-woocommerce' ) );
		}

		return $invoice;
	}

	/**
	 * Enforce invoice management permission.
	 *
	 * @return void
	 */
	private function require_capability() {
		if ( ! current_user_can( abiwig_manage_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to manage invoices.', 'abill-invoice-generator-for-woocommerce' ) );
		}
	}

	/**
	 * Build an admin page URL.
	 *
	 * @param string               $page Page slug.
	 * @param array<string, mixed> $args Additional query args.
	 * @return string
	 */
	private function page_url( $page, $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * Redirect to an admin page with a fixed notice code.
	 *
	 * @param string               $page   Page slug.
	 * @param string               $notice Notice code.
	 * @param array<string, mixed> $args   Additional query args.
	 * @return void
	 */
	private function redirect( $page, $notice, $args = array() ) {
		$url = $this->page_url( $page, array_merge( array( 'abiwig_notice' => sanitize_key( $notice ) ), $args ) );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Return a fixed notice array for safe rendering.
	 *
	 * @return array<string, string>|null
	 */
	private function notice() {
		$code = isset( $_GET['abiwig_notice'] ) ? sanitize_key( wp_unslash( $_GET['abiwig_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Fixed display-only notice code.

		$notices = array(
			'created'       => array( 'type' => 'success', 'message' => __( 'Invoice created successfully.', 'abill-invoice-generator-for-woocommerce' ) ),
			'updated'       => array( 'type' => 'success', 'message' => __( 'Invoice updated successfully.', 'abill-invoice-generator-for-woocommerce' ) ),
			'deleted'       => array( 'type' => 'success', 'message' => __( 'Invoice moved to Trash.', 'abill-invoice-generator-for-woocommerce' ) ),
			'emailed'       => array( 'type' => 'success', 'message' => __( 'Invoice email sent.', 'abill-invoice-generator-for-woocommerce' ) ),
			'invalid_order' => array( 'type' => 'error', 'message' => __( 'Enter a valid WooCommerce order ID.', 'abill-invoice-generator-for-woocommerce' ) ),
			'not_found'     => array( 'type' => 'error', 'message' => __( 'Invoice not found.', 'abill-invoice-generator-for-woocommerce' ) ),
			'delete_failed' => array( 'type' => 'error', 'message' => __( 'The invoice could not be moved to Trash.', 'abill-invoice-generator-for-woocommerce' ) ),
			'email_failed'  => array( 'type' => 'error', 'message' => __( 'The invoice email could not be sent. Check the customer email and your WordPress mail configuration.', 'abill-invoice-generator-for-woocommerce' ) ),
			'error'         => array( 'type' => 'error', 'message' => __( 'The requested invoice action could not be completed.', 'abill-invoice-generator-for-woocommerce' ) ),
		);

		return isset( $notices[ $code ] ) ? $notices[ $code ] : null;
	}
}

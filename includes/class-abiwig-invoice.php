<?php
/**
 * Invoice model.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Immutable-style wrapper around a stored invoice snapshot.
 */
final class ABIWIG_Invoice {

	/** @var int */
	private $id;

	/** @var array<string, mixed> */
	private $data;

	/**
	 * Constructor.
	 *
	 * @param int                  $id   Invoice post ID.
	 * @param array<string, mixed> $data Invoice data.
	 */
	public function __construct( $id, $data ) {
		$this->id   = absint( $id );
		$this->data = abiwig_sanitize_invoice_data( $data );
	}

	/** @return int */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Return all invoice data.
	 *
	 * @return array<string, mixed>
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Return one data field.
	 *
	 * @param string $key     Data key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : $default;
	}

	/** @return string */
	public function get_number() {
		return (string) $this->get( 'invoice_number', '' );
	}

	/** @return int */
	public function get_order_id() {
		return absint( $this->get( 'order_id', 0 ) );
	}

	/** @return string */
	public function get_order_number() {
		return (string) $this->get( 'order_number', '' );
	}

	/** @return string */
	public function get_status() {
		return (string) $this->get( 'status', 'draft' );
	}

	/** @return string */
	public function get_currency() {
		return (string) $this->get( 'currency', '' );
	}

	/** @return array<int, array<string, mixed>> */
	public function get_items() {
		$items = $this->get( 'items', array() );
		return is_array( $items ) ? $items : array();
	}

	/** @return array<string, mixed> */
	public function get_totals() {
		$totals = $this->get( 'totals', array() );
		return is_array( $totals ) ? $totals : array();
	}

	/** @return array<string, mixed> */
	public function get_customer() {
		$customer = $this->get( 'customer', array() );
		return is_array( $customer ) ? $customer : array();
	}

	/** @return array<string, mixed> */
	public function get_business() {
		$business = $this->get( 'business', array() );
		return is_array( $business ) ? $business : array();
	}

	/**
	 * Return a copy with merged data. Persistence is handled by the repository.
	 *
	 * @param array<string, mixed> $changes Changed fields.
	 * @return self
	 */
	public function with_data( $changes ) {
		$changes = is_array( $changes ) ? $changes : array();
		return new self( $this->id, array_replace_recursive( $this->data, $changes ) );
	}
}

<?php
/**
 * Public helper functions.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the main plugin instance.
 *
 * @return ABIWIG_Plugin|null
 */
function abiwig() {
	return class_exists( 'ABIWIG_Plugin' ) ? ABIWIG_Plugin::instance() : null;
}

/**
 * Return the capability required to manage invoices.
 *
 * @return string
 */
function abiwig_manage_capability() {
	/**
	 * Filter the capability required to manage ABill invoices.
	 *
	 * @param string $capability Capability name.
	 */
	return (string) apply_filters( 'abiwig_manage_capability', 'manage_woocommerce' );
}

/**
 * Return all plugin settings.
 *
 * @return array<string, mixed>
 */
function abiwig_get_settings() {
	if ( class_exists( 'ABIWIG_Settings' ) ) {
		return ABIWIG_Settings::get_all();
	}

	return array();
}

/**
 * Return one plugin setting.
 *
 * @param string $key     Setting key.
 * @param mixed  $default Default value.
 * @return mixed
 */
function abiwig_get_setting( $key, $default = null ) {
	if ( class_exists( 'ABIWIG_Settings' ) ) {
		return ABIWIG_Settings::get( $key, $default );
	}

	return $default;
}

/**
 * Format a sequential invoice number.
 *
 * @param int    $sequence Sequence number.
 * @param string $prefix   Optional prefix.
 * @return string
 */
function abiwig_format_invoice_number( $sequence, $prefix = '' ) {
	$sequence = max( 1, absint( $sequence ) );
	$prefix   = '' === $prefix ? (string) abiwig_get_setting( 'invoice_prefix', 'AB-' ) : $prefix;
	$digits   = max( 3, min( 12, absint( abiwig_get_setting( 'invoice_number_digits', 6 ) ) ) );

	/**
	 * Filter the formatted invoice number.
	 *
	 * @param string $invoice_number Formatted number.
	 * @param int    $sequence       Numeric sequence.
	 * @param string $prefix         Number prefix.
	 */
	return (string) apply_filters(
		'abiwig_invoice_number',
		$prefix . str_pad( (string) $sequence, $digits, '0', STR_PAD_LEFT ),
		$sequence,
		$prefix
	);
}

/**
 * Normalize a date to the site date format used for stored invoice data.
 *
 * @param string|int|null $value Date string, timestamp, or null.
 * @param string          $fallback Fallback date in Y-m-d format.
 * @return string
 */
function abiwig_normalize_date( $value, $fallback = '' ) {
	if ( is_numeric( $value ) ) {
		$timestamp = absint( $value );
	} elseif ( is_string( $value ) && '' !== trim( $value ) ) {
		$timestamp = strtotime( $value );
	} else {
		$timestamp = false;
	}

	if ( false === $timestamp || 0 === $timestamp ) {
		return $fallback;
	}

	return wp_date( 'Y-m-d', $timestamp );
}

/**
 * Normalize an amount to a decimal string.
 *
 * @param mixed $value Amount.
 * @return string
 */
function abiwig_normalize_amount( $value ) {
	if ( function_exists( 'wc_format_decimal' ) ) {
		return (string) wc_format_decimal( $value, wc_get_price_decimals() );
	}

	return number_format( (float) $value, 2, '.', '' );
}

/**
 * Sanitize a complete invoice snapshot.
 *
 * @param array<string, mixed> $data Invoice data.
 * @return array<string, mixed>
 */
function abiwig_sanitize_invoice_data( $data ) {
	$data = is_array( $data ) ? $data : array();

	$business = isset( $data['business'] ) && is_array( $data['business'] ) ? $data['business'] : array();
	$customer = isset( $data['customer'] ) && is_array( $data['customer'] ) ? $data['customer'] : array();
	$totals   = isset( $data['totals'] ) && is_array( $data['totals'] ) ? $data['totals'] : array();
	$items    = isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array();

	$clean_items = array();
	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$name = isset( $item['name'] ) ? sanitize_text_field( wp_unslash( (string) $item['name'] ) ) : '';
		if ( '' === $name ) {
			continue;
		}

		$clean_items[] = array(
			'name'     => $name,
			'sku'      => isset( $item['sku'] ) ? sanitize_text_field( wp_unslash( (string) $item['sku'] ) ) : '',
			'quantity' => max( 0, (float) ( $item['quantity'] ?? 0 ) ),
			'subtotal' => abiwig_normalize_amount( $item['subtotal'] ?? 0 ),
			'tax'      => abiwig_normalize_amount( $item['tax'] ?? 0 ),
			'total'    => abiwig_normalize_amount( $item['total'] ?? 0 ),
		);
	}

	$status = isset( $data['status'] ) ? sanitize_key( (string) $data['status'] ) : 'draft';
	if ( ! in_array( $status, array( 'draft', 'sent', 'paid', 'cancelled' ), true ) ) {
		$status = 'draft';
	}

	$clean = array(
		'invoice_number' => isset( $data['invoice_number'] ) ? sanitize_text_field( wp_unslash( (string) $data['invoice_number'] ) ) : '',
		'order_id'       => absint( $data['order_id'] ?? 0 ),
		'order_number'   => isset( $data['order_number'] ) ? sanitize_text_field( wp_unslash( (string) $data['order_number'] ) ) : '',
		'invoice_date'   => abiwig_normalize_date( $data['invoice_date'] ?? '', wp_date( 'Y-m-d' ) ),
		'due_date'       => abiwig_normalize_date( $data['due_date'] ?? '', '' ),
		'status'         => $status,
		'currency'       => isset( $data['currency'] ) ? sanitize_text_field( strtoupper( (string) $data['currency'] ) ) : '',
		'payment_method' => isset( $data['payment_method'] ) ? sanitize_text_field( wp_unslash( (string) $data['payment_method'] ) ) : '',
		'shipping_method'=> isset( $data['shipping_method'] ) ? sanitize_text_field( wp_unslash( (string) $data['shipping_method'] ) ) : '',
		'business'       => array(
			'name'      => isset( $business['name'] ) ? sanitize_text_field( wp_unslash( (string) $business['name'] ) ) : '',
			'address_1' => isset( $business['address_1'] ) ? sanitize_text_field( wp_unslash( (string) $business['address_1'] ) ) : '',
			'address_2' => isset( $business['address_2'] ) ? sanitize_text_field( wp_unslash( (string) $business['address_2'] ) ) : '',
			'city'      => isset( $business['city'] ) ? sanitize_text_field( wp_unslash( (string) $business['city'] ) ) : '',
			'state'     => isset( $business['state'] ) ? sanitize_text_field( wp_unslash( (string) $business['state'] ) ) : '',
			'postcode'  => isset( $business['postcode'] ) ? sanitize_text_field( wp_unslash( (string) $business['postcode'] ) ) : '',
			'country'   => isset( $business['country'] ) ? sanitize_text_field( wp_unslash( (string) $business['country'] ) ) : '',
			'tax_id'    => isset( $business['tax_id'] ) ? sanitize_text_field( wp_unslash( (string) $business['tax_id'] ) ) : '',
			'email'     => isset( $business['email'] ) ? sanitize_email( (string) $business['email'] ) : '',
			'phone'     => isset( $business['phone'] ) ? sanitize_text_field( wp_unslash( (string) $business['phone'] ) ) : '',
			'logo_id'   => absint( $business['logo_id'] ?? 0 ),
		),
		'customer'       => array(
			'name'       => isset( $customer['name'] ) ? sanitize_text_field( wp_unslash( (string) $customer['name'] ) ) : '',
			'company'    => isset( $customer['company'] ) ? sanitize_text_field( wp_unslash( (string) $customer['company'] ) ) : '',
			'address_1'  => isset( $customer['address_1'] ) ? sanitize_text_field( wp_unslash( (string) $customer['address_1'] ) ) : '',
			'address_2'  => isset( $customer['address_2'] ) ? sanitize_text_field( wp_unslash( (string) $customer['address_2'] ) ) : '',
			'city'       => isset( $customer['city'] ) ? sanitize_text_field( wp_unslash( (string) $customer['city'] ) ) : '',
			'state'      => isset( $customer['state'] ) ? sanitize_text_field( wp_unslash( (string) $customer['state'] ) ) : '',
			'postcode'   => isset( $customer['postcode'] ) ? sanitize_text_field( wp_unslash( (string) $customer['postcode'] ) ) : '',
			'country'    => isset( $customer['country'] ) ? sanitize_text_field( wp_unslash( (string) $customer['country'] ) ) : '',
			'email'      => isset( $customer['email'] ) ? sanitize_email( (string) $customer['email'] ) : '',
			'phone'      => isset( $customer['phone'] ) ? sanitize_text_field( wp_unslash( (string) $customer['phone'] ) ) : '',
			'tax_id'     => isset( $customer['tax_id'] ) ? sanitize_text_field( wp_unslash( (string) $customer['tax_id'] ) ) : '',
		),
		'items'          => $clean_items,
		'totals'         => array(
			'subtotal' => abiwig_normalize_amount( $totals['subtotal'] ?? 0 ),
			'discount' => abiwig_normalize_amount( $totals['discount'] ?? 0 ),
			'shipping' => abiwig_normalize_amount( $totals['shipping'] ?? 0 ),
			'tax'      => abiwig_normalize_amount( $totals['tax'] ?? 0 ),
			'total'    => abiwig_normalize_amount( $totals['total'] ?? 0 ),
		),
		'notes'          => isset( $data['notes'] ) ? sanitize_textarea_field( wp_unslash( (string) $data['notes'] ) ) : '',
		'terms'          => isset( $data['terms'] ) ? sanitize_textarea_field( wp_unslash( (string) $data['terms'] ) ) : '',
		'created_at'     => isset( $data['created_at'] ) ? sanitize_text_field( (string) $data['created_at'] ) : current_time( 'mysql', true ),
		'sent_at'        => isset( $data['sent_at'] ) ? sanitize_text_field( (string) $data['sent_at'] ) : '',
		'sent_to'        => isset( $data['sent_to'] ) ? sanitize_email( (string) $data['sent_to'] ) : '',
		'updated_at'     => current_time( 'mysql', true ),
	);

	/**
	 * Filter sanitized invoice data before it is stored.
	 *
	 * @param array<string, mixed> $clean Clean invoice data.
	 * @param array<string, mixed> $data  Original invoice data.
	 */
	return (array) apply_filters( 'abiwig_sanitized_invoice_data', $clean, $data );
}

/**
 * Retrieve one invoice.
 *
 * @param int $invoice_id Invoice post ID.
 * @return ABIWIG_Invoice|null
 */
function abiwig_get_invoice( $invoice_id ) {
	$plugin = abiwig();

	return $plugin ? $plugin->repository()->get( $invoice_id ) : null;
}

/**
 * Retrieve an invoice by WooCommerce order ID.
 *
 * @param int $order_id WooCommerce order ID.
 * @return ABIWIG_Invoice|null
 */
function abiwig_get_invoice_by_order( $order_id ) {
	$plugin = abiwig();

	return $plugin ? $plugin->repository()->get_by_order_id( $order_id ) : null;
}

/**
 * Create an invoice snapshot from a WooCommerce order.
 *
 * @param int|WC_Order $order Order object or ID.
 * @return ABIWIG_Invoice|WP_Error
 */
function abiwig_create_invoice_from_order( $order ) {
	$plugin = abiwig();

	if ( ! $plugin ) {
		return new WP_Error( 'abiwig_not_loaded', __( 'ABill Invoice Generator is not loaded.', 'abill-invoice-generator-for-woocommerce' ) );
	}

	return $plugin->woocommerce()->create_invoice_from_order( $order );
}

/**
 * Return normalized address lines for display in invoice templates.
 *
 * @param array<string, mixed> $address Address data.
 * @return array<int, string>
 */
function abiwig_address_lines( $address ) {
	$address = is_array( $address ) ? $address : array();
	$lines   = array();

	foreach ( array( 'name', 'company', 'address_1', 'address_2' ) as $key ) {
		if ( ! empty( $address[ $key ] ) ) {
			$lines[] = (string) $address[ $key ];
		}
	}

	$city_line = trim(
		implode(
			' ',
			array_filter(
				array(
					$address['city'] ?? '',
					$address['state'] ?? '',
					$address['postcode'] ?? '',
				)
			)
		)
	);

	if ( $city_line ) {
		$lines[] = $city_line;
	}

	if ( ! empty( $address['country'] ) ) {
		$lines[] = (string) $address['country'];
	}

	/**
	 * Filter address lines rendered by ABill templates.
	 *
	 * @param array<int, string>   $lines   Address lines.
	 * @param array<string, mixed> $address Original address data.
	 */
	return (array) apply_filters( 'abiwig_address_lines', $lines, $address );
}

/**
 * Format a stored invoice date using the site's date format.
 *
 * @param string $date Date in Y-m-d or another parseable format.
 * @return string
 */
function abiwig_display_date( $date ) {
	$date = trim( (string) $date );
	if ( '' === $date ) {
		return '';
	}

	$timestamp = strtotime( $date );
	if ( false === $timestamp ) {
		return $date;
	}

	return wp_date( get_option( 'date_format' ), $timestamp );
}

/**
 * Format an invoice item quantity without unnecessary trailing zeros.
 *
 * @param mixed $quantity Quantity.
 * @return string
 */
function abiwig_format_quantity( $quantity ) {
	$quantity = (float) $quantity;
	if ( floor( $quantity ) === $quantity ) {
		return (string) (int) $quantity;
	}

	return rtrim( rtrim( number_format( $quantity, 3, '.', '' ), '0' ), '.' );
}

/**
 * Locate a plugin template, allowing a theme override.
 *
 * Theme overrides belong in: your-theme/abill-invoices/{template-name}
 *
 * @param string $template_name Template filename relative to the templates directory.
 * @return string
 */
function abiwig_locate_template( $template_name ) {
	$template_name = ltrim( str_replace( '\\', '/', (string) $template_name ), '/' );
	if ( '' === $template_name || false !== strpos( $template_name, '..' ) ) {
		return '';
	}

	$template_path = locate_template( 'abill-invoices/' . $template_name, false, false );
	if ( ! $template_path ) {
		$template_path = ABIWIG_PATH . 'templates/' . $template_name;
	}

	if ( ! is_readable( $template_path ) ) {
		return '';
	}

	/**
	 * Filter the resolved ABill template path.
	 *
	 * @param string $template_path Resolved path.
	 * @param string $template_name Requested template name.
	 */
	return (string) apply_filters( 'abiwig_locate_template', $template_path, $template_name );
}

/**
 * Render a template file.
 *
 * Template variables are available through the local $args array.
 *
 * @param string               $template_name Template filename.
 * @param array<string, mixed> $args          Template arguments.
 * @param bool                 $return        Return the rendered HTML instead of echoing it.
 * @return string
 */
function abiwig_get_template( $template_name, $args = array(), $return = false ) {
	$template_path = abiwig_locate_template( $template_name );
	if ( '' === $template_path ) {
		return '';
	}

	$args = is_array( $args ) ? $args : array();

	/**
	 * Filter arguments passed to an ABill template.
	 *
	 * @param array<string, mixed> $args          Template arguments.
	 * @param string               $template_name Template name.
	 */
	$args = (array) apply_filters( 'abiwig_template_args', $args, $template_name );

	do_action( 'abiwig_before_template', $template_name, $template_path, $args );

	if ( $return ) {
		ob_start();
	}

	load_template( $template_path, false, array( 'args' => $args ) );

	$output = '';
	if ( $return ) {
		$output = (string) ob_get_clean();
	}

	do_action( 'abiwig_after_template', $template_name, $template_path, $args );

	return $output;
}

/**
 * Return rendered template HTML.
 *
 * @param string               $template_name Template filename.
 * @param array<string, mixed> $args          Template arguments.
 * @return string
 */
function abiwig_get_template_html( $template_name, $args = array() ) {
	return abiwig_get_template( $template_name, $args, true );
}

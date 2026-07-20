<?php
/**
 * Plugin settings.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manage plugin settings and defaults.
 */
final class ABIWIG_Settings {

	/** Settings option name. */
	const OPTION = 'abiwig_settings';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_init', array( $this, 'register_setting' ) );
	}

	/**
	 * Register the settings option.
	 *
	 * @return void
	 */
	public function register_setting() {
		register_setting(
			'abiwig_settings_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
			)
		);

		register_setting(
			'abiwig_settings_group',
			'abiwig_next_invoice_number',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_next_invoice_number' ),
				'default'           => 1,
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Install missing default values without overwriting existing settings.
	 *
	 * @return void
	 */
	public static function install_defaults() {
		$current = get_option( self::OPTION, array() );
		$current = is_array( $current ) ? $current : array();

		if ( ! get_option( self::OPTION, false ) ) {
			add_option( self::OPTION, wp_parse_args( $current, self::defaults() ), '', false );
		} else {
			update_option( self::OPTION, wp_parse_args( $current, self::defaults() ), false );
		}

		if ( false === get_option( 'abiwig_next_invoice_number', false ) ) {
			add_option( 'abiwig_next_invoice_number', 1, '', false );
		}

		if ( false === get_option( 'abiwig_delete_data_on_uninstall', false ) ) {
			add_option( 'abiwig_delete_data_on_uninstall', 'no', '', false );
		}
	}

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		$country_state = (string) get_option( 'woocommerce_default_country', '' );
		$country_parts = array_pad( explode( ':', $country_state, 2 ), 2, '' );

		return array(
			'business_name'            => (string) get_bloginfo( 'name' ),
			'business_address_1'       => (string) get_option( 'woocommerce_store_address', '' ),
			'business_address_2'       => (string) get_option( 'woocommerce_store_address_2', '' ),
			'business_city'            => (string) get_option( 'woocommerce_store_city', '' ),
			'business_state'           => sanitize_text_field( $country_parts[1] ),
			'business_postcode'        => (string) get_option( 'woocommerce_store_postcode', '' ),
			'business_country'         => sanitize_text_field( $country_parts[0] ),
			'business_tax_id'          => '',
			'business_email'           => (string) get_option( 'admin_email', '' ),
			'business_phone'           => '',
			'business_logo_id'         => 0,
			'invoice_prefix'           => 'AB-',
			'invoice_number_digits'    => 6,
			'default_due_days'         => 0,
			'default_notes'            => __( 'Thank you for your business.', 'abill-invoice-generator-for-woocommerce' ),
			'default_terms'            => '',
			'pdf_paper_size'           => 'A4',
			'email_subject'            => __( 'Invoice {invoice_number} for order {order_number}', 'abill-invoice-generator-for-woocommerce' ),
			'email_message'            => __( "Hello {customer_name},\n\nPlease find your invoice attached.\n\nThank you.", 'abill-invoice-generator-for-woocommerce' ),
			'delete_data_on_uninstall' => 'no',
		);
	}

	/**
	 * Return all settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_all() {
		$value = get_option( self::OPTION, array() );
		$value = is_array( $value ) ? $value : array();

		return wp_parse_args( $value, self::defaults() );
	}

	/**
	 * Return one setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$settings = self::get_all();

		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}

	/**
	 * Sanitize settings submitted through the Settings API.
	 *
	 * @param mixed $input Submitted value.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();

		$clean = array(
			'business_name'            => sanitize_text_field( wp_unslash( (string) ( $input['business_name'] ?? $defaults['business_name'] ) ) ),
			'business_address_1'       => sanitize_text_field( wp_unslash( (string) ( $input['business_address_1'] ?? '' ) ) ),
			'business_address_2'       => sanitize_text_field( wp_unslash( (string) ( $input['business_address_2'] ?? '' ) ) ),
			'business_city'            => sanitize_text_field( wp_unslash( (string) ( $input['business_city'] ?? '' ) ) ),
			'business_state'           => sanitize_text_field( wp_unslash( (string) ( $input['business_state'] ?? '' ) ) ),
			'business_postcode'        => sanitize_text_field( wp_unslash( (string) ( $input['business_postcode'] ?? '' ) ) ),
			'business_country'         => sanitize_text_field( wp_unslash( (string) ( $input['business_country'] ?? '' ) ) ),
			'business_tax_id'          => sanitize_text_field( wp_unslash( (string) ( $input['business_tax_id'] ?? '' ) ) ),
			'business_email'           => sanitize_email( (string) ( $input['business_email'] ?? '' ) ),
			'business_phone'           => sanitize_text_field( wp_unslash( (string) ( $input['business_phone'] ?? '' ) ) ),
			'business_logo_id'         => absint( $input['business_logo_id'] ?? 0 ),
			'invoice_prefix'           => substr( sanitize_text_field( wp_unslash( (string) ( $input['invoice_prefix'] ?? 'AB-' ) ) ), 0, 20 ),
			'invoice_number_digits'    => max( 3, min( 12, absint( $input['invoice_number_digits'] ?? 6 ) ) ),
			'default_due_days'         => max( 0, min( 3650, absint( $input['default_due_days'] ?? 0 ) ) ),
			'default_notes'            => sanitize_textarea_field( wp_unslash( (string) ( $input['default_notes'] ?? '' ) ) ),
			'default_terms'            => sanitize_textarea_field( wp_unslash( (string) ( $input['default_terms'] ?? '' ) ) ),
			'pdf_paper_size'           => in_array( (string) ( $input['pdf_paper_size'] ?? 'A4' ), array( 'A4', 'LETTER' ), true ) ? (string) $input['pdf_paper_size'] : 'A4',
			'email_subject'            => sanitize_text_field( wp_unslash( (string) ( $input['email_subject'] ?? $defaults['email_subject'] ) ) ),
			'email_message'            => sanitize_textarea_field( wp_unslash( (string) ( $input['email_message'] ?? $defaults['email_message'] ) ) ),
			'delete_data_on_uninstall' => ! empty( $input['delete_data_on_uninstall'] ) && 'yes' === $input['delete_data_on_uninstall'] ? 'yes' : 'no',
		);

		update_option( 'abiwig_delete_data_on_uninstall', $clean['delete_data_on_uninstall'], false );

		return $clean;
	}


	/**
	 * Sanitize the next invoice sequence.
	 *
	 * @param mixed $value Submitted sequence.
	 * @return int
	 */
	public function sanitize_next_invoice_number( $value ) {
		$current = max( 1, absint( get_option( 'abiwig_next_invoice_number', 1 ) ) );
		$value   = max( 1, absint( $value ) );

		return max( $current, $value );
	}

	/**
	 * Return settings formatted as the invoice business snapshot.
	 *
	 * @return array<string, mixed>
	 */
	public static function business_snapshot() {
		$settings = self::get_all();

		return array(
			'name'      => $settings['business_name'],
			'address_1' => $settings['business_address_1'],
			'address_2' => $settings['business_address_2'],
			'city'      => $settings['business_city'],
			'state'     => $settings['business_state'],
			'postcode'  => $settings['business_postcode'],
			'country'   => $settings['business_country'],
			'tax_id'    => $settings['business_tax_id'],
			'email'     => $settings['business_email'],
			'phone'     => $settings['business_phone'],
			'logo_id'   => absint( $settings['business_logo_id'] ),
		);
	}
}

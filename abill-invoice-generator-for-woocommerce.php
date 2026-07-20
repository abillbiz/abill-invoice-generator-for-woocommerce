<?php
/**
 * Plugin Name:       ABill Invoice Generator for WooCommerce
 * Plugin URI:        https://abill.in/invoice-generator-for-woocommerce/
 * Description:       Create, edit, email, print, and download PDF invoices for WooCommerce orders.
 * Version:           1.0.0
 * Author:            ABill
 * Author URI:        https://abill.in/
 * Text Domain:       abill-invoice-generator-for-woocommerce
 * Domain Path:       /languages
 * Requires at least: 7.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.2
 * WC tested up to:     10.9.4
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'ABIWIG_VERSION' ) ) {
	define( 'ABIWIG_VERSION', '1.0.0' );
}

if ( ! defined( 'ABIWIG_FILE' ) ) {
	define( 'ABIWIG_FILE', __FILE__ );
}

if ( ! defined( 'ABIWIG_BASENAME' ) ) {
	define( 'ABIWIG_BASENAME', plugin_basename( ABIWIG_FILE ) );
}

if ( ! defined( 'ABIWIG_PATH' ) ) {
	define( 'ABIWIG_PATH', plugin_dir_path( ABIWIG_FILE ) );
}

if ( ! defined( 'ABIWIG_URL' ) ) {
	define( 'ABIWIG_URL', plugin_dir_url( ABIWIG_FILE ) );
}

/**
 * Check whether WooCommerce is available.
 *
 * @return bool
 */
function abiwig_is_woocommerce_active() {
	return class_exists( 'WooCommerce' );
}

/**
 * Declare compatibility with supported WooCommerce features.
 *
 * The declaration is intentionally kept in the bootstrap file so WooCommerce
 * can read it before the rest of the plugin is initialized.
 *
 * @return void
 */
function abiwig_declare_woocommerce_compatibility() {
	if ( ! class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		return;
	}

	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
		'custom_order_tables',
		ABIWIG_FILE,
		true
	);
}
add_action( 'before_woocommerce_init', 'abiwig_declare_woocommerce_compatibility' );

/**
 * Run when the plugin is activated.
 *
 * @return void
 */
function abiwig_activate_plugin() {
	if ( ! abiwig_is_woocommerce_active() ) {
		deactivate_plugins( ABIWIG_BASENAME );

		wp_die(
			esc_html__( 'ABill Invoice Generator for WooCommerce requires WooCommerce to be installed and active.', 'abill-invoice-generator-for-woocommerce' ),
			esc_html__( 'Plugin dependency missing', 'abill-invoice-generator-for-woocommerce' ),
			array( 'back_link' => true )
		);
	}

	update_option( 'abiwig_version', ABIWIG_VERSION, false );
}
register_activation_hook( ABIWIG_FILE, 'abiwig_activate_plugin' );

/**
 * Display a dependency notice when WooCommerce is unavailable.
 *
 * @return void
 */
function abiwig_render_woocommerce_missing_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p>
			<?php
			echo esc_html__(
				'ABill Invoice Generator for WooCommerce requires WooCommerce to be installed and active.',
				'abill-invoice-generator-for-woocommerce'
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Bootstrap the plugin after all plugins have loaded.
 *
 * @return void
 */
function abiwig_boot_plugin() {
	if ( ! abiwig_is_woocommerce_active() ) {
		add_action( 'admin_notices', 'abiwig_render_woocommerce_missing_notice' );
		return;
	}

	$plugin_class_file = ABIWIG_PATH . 'includes/class-abiwig-plugin.php';

	/*
	 * The main class will be added in the next development step. Keeping this
	 * check here makes the root scaffold safe to activate while it is built.
	 */
	if ( ! file_exists( $plugin_class_file ) ) {
		return;
	}

	require_once $plugin_class_file;

	if ( class_exists( 'ABIWIG_Plugin' ) ) {
		ABIWIG_Plugin::instance()->run();
	}
}
add_action( 'plugins_loaded', 'abiwig_boot_plugin', 20 );

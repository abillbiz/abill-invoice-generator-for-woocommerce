<?php
/**
 * Main plugin container.
 *
 * @package ABill_Invoice_Generator_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load and connect the plugin's services.
 */
final class ABIWIG_Plugin {

	/** @var self|null */
	private static $instance = null;

	/** @var bool */
	private $running = false;

	/** @var ABIWIG_Settings */
	private $settings;

	/** @var ABIWIG_Invoice_Repository */
	private $repository;

	/** @var ABIWIG_PDF */
	private $pdf;

	/** @var ABIWIG_Email */
	private $email;

	/** @var ABIWIG_WooCommerce */
	private $woocommerce;

	/** @var ABIWIG_Admin|null */
	private $admin = null;

	/**
	 * Return singleton instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/** Private constructor. */
	private function __construct() {}

	/** Prevent cloning. */
	private function __clone() {}

	/**
	 * Load dependencies and register hooks.
	 *
	 * @return void
	 */
	public function run() {
		if ( $this->running ) {
			return;
		}

		$this->load_dependencies();

		$this->settings    = new ABIWIG_Settings();
		$this->repository  = new ABIWIG_Invoice_Repository();
		$this->pdf         = new ABIWIG_PDF();
		$this->email       = new ABIWIG_Email( $this->repository, $this->pdf );
		$this->woocommerce = new ABIWIG_WooCommerce( $this->repository, $this->email );

		if ( is_admin() && class_exists( 'ABIWIG_Admin' ) ) {
			$this->admin = new ABIWIG_Admin( $this->repository, $this->pdf, $this->email, $this->woocommerce );
			$this->admin->register_hooks();
		}

		add_action( 'init', array( 'ABIWIG_Invoice_Repository', 'register_post_type' ), 5 );
		$this->settings->register_hooks();
		$this->woocommerce->register_hooks();
		$this->maybe_upgrade();

		$this->running = true;

		/**
		 * Fires after all ABill Invoice Generator services are loaded.
		 *
		 * @param ABIWIG_Plugin $plugin Main plugin instance.
		 */
		do_action( 'abiwig_loaded', $this );
	}

	/** @return ABIWIG_Settings */
	public function settings() {
		return $this->settings;
	}

	/** @return ABIWIG_Invoice_Repository */
	public function repository() {
		return $this->repository;
	}

	/** @return ABIWIG_PDF */
	public function pdf() {
		return $this->pdf;
	}

	/** @return ABIWIG_Email */
	public function email() {
		return $this->email;
	}

	/** @return ABIWIG_WooCommerce */
	public function woocommerce() {
		return $this->woocommerce;
	}

	/** @return ABIWIG_Admin|null */
	public function admin() {
		return $this->admin;
	}

	/**
	 * Include internal classes in dependency order.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		$files = array(
			'includes/functions-abiwig.php',
			'includes/class-abiwig-settings.php',
			'includes/class-abiwig-invoice.php',
			'includes/class-abiwig-invoice-repository.php',
			'includes/class-abiwig-pdf.php',
			'includes/class-abiwig-email.php',
			'includes/class-abiwig-woocommerce.php',
			'admin/class-abiwig-admin.php',
		);

		foreach ( $files as $file ) {
			require_once ABIWIG_PATH . $file;
		}
	}

	/**
	 * Run lightweight version upgrades.
	 *
	 * @return void
	 */
	private function maybe_upgrade() {
		$installed = (string) get_option( 'abiwig_version', '0.0.0' );
		if ( version_compare( $installed, ABIWIG_VERSION, '>=' ) ) {
			return;
		}

		ABIWIG_Settings::install_defaults();
		update_option( 'abiwig_version', ABIWIG_VERSION, false );
	}
}

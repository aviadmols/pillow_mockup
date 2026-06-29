<?php
/**
 * Main plugin wiring (singleton).
 *
 * @package PillowMockupGenerator
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PMG_Plugin
 */
class PMG_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var PMG_Plugin|null
	 */
	protected static $instance = null;

	/**
	 * REST controller.
	 *
	 * @var PMG_Rest
	 */
	public $rest;

	/**
	 * Shortcode controller.
	 *
	 * @var PMG_Shortcode
	 */
	public $shortcode;

	/**
	 * Admin controller.
	 *
	 * @var PMG_Admin
	 */
	public $admin;

	/**
	 * Experimental "Lab" controller (isolated room-overlay flow).
	 *
	 * @var PMG_Lab
	 */
	public $lab;

	/**
	 * Get the singleton.
	 *
	 * @return PMG_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor: wire components.
	 */
	protected function __construct() {
		load_plugin_textdomain( 'pillow-mockup-generator', false, dirname( plugin_basename( PMG_PLUGIN_FILE ) ) . '/languages' );

		$this->maybe_upgrade();

		$this->rest = new PMG_Rest();
		$this->rest->register();

		$this->shortcode = new PMG_Shortcode();
		$this->shortcode->register();

		// Experimental, fully isolated room-overlay flow ([pillow_mockup_lab]).
		$this->lab = new PMG_Lab();
		$this->lab->register();

		if ( is_admin() ) {
			$this->admin = new PMG_Admin();
			$this->admin->register();
		}
	}

	/**
	 * Run table creation if the plugin updated or tables are missing.
	 *
	 * @return void
	 */
	protected function maybe_upgrade() {
		if ( get_option( 'pmg_db_version' ) !== PMG_VERSION ) {
			PMG_Activator::create_tables();
			PMG_Activator::ensure_upload_dir();
		}

		// Switch untouched front-end texts to Hebrew on existing installs (runs once).
		PMG_Settings::maybe_migrate_texts();

		// Force English/USD sizes & pricing for the on1y.one redesign (runs once).
		PMG_Settings::maybe_migrate_sizes_en();
	}
}

<?php
/**
 * Activation / deactivation: creates database tables and the protected upload directory.
 *
 * @package PillowMockupGenerator
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PMG_Activator
 */
class PMG_Activator {

	/**
	 * Leads table name (without prefix).
	 */
	const LEADS_TABLE = 'pmg_leads';

	/**
	 * Generations log table name (without prefix).
	 */
	const GENERATIONS_TABLE = 'pmg_generations';

	/**
	 * Fully qualified leads table name.
	 *
	 * @return string
	 */
	public static function leads_table() {
		global $wpdb;
		return $wpdb->prefix . self::LEADS_TABLE;
	}

	/**
	 * Fully qualified generations table name.
	 *
	 * @return string
	 */
	public static function generations_table() {
		global $wpdb;
		return $wpdb->prefix . self::GENERATIONS_TABLE;
	}

	/**
	 * Run on activation.
	 *
	 * @return void
	 */
	public static function activate() {
		self::create_tables();
		self::ensure_upload_dir();

		if ( false === get_option( PMG_OPTION_KEY, false ) ) {
			add_option( PMG_OPTION_KEY, PMG_Settings::defaults() );
		}

		flush_rewrite_rules();
	}

	/**
	 * Run on deactivation. We keep data; just flush rules.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Create the custom tables using dbDelta.
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$leads           = self::leads_table();
		$generations     = self::generations_table();

		$leads_sql = "CREATE TABLE {$leads} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session varchar(64) NOT NULL DEFAULT '',
			name varchar(191) NOT NULL DEFAULT '',
			phone varchar(64) NOT NULL DEFAULT '',
			email varchar(191) NOT NULL DEFAULT '',
			status varchar(32) NOT NULL DEFAULT 'new',
			attempts int(10) unsigned NOT NULL DEFAULT 0,
			original_image text NULL,
			mockup_image text NULL,
			cutout_image text NULL,
			total_cost decimal(12,6) NOT NULL DEFAULT 0,
			ip varchar(64) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY session (session),
			KEY email (email),
			KEY status (status)
		) {$charset_collate};";

		$generations_sql = "CREATE TABLE {$generations} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session varchar(64) NOT NULL DEFAULT '',
			lead_id bigint(20) unsigned NULL,
			type varchar(20) NOT NULL DEFAULT 'mockup',
			model varchar(128) NOT NULL DEFAULT '',
			cost decimal(12,6) NOT NULL DEFAULT 0,
			prompt_tokens int(10) unsigned NOT NULL DEFAULT 0,
			completion_tokens int(10) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'success',
			generation_id varchar(128) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY session (session),
			KEY lead_id (lead_id),
			KEY type (type),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $leads_sql );
		dbDelta( $generations_sql );

		update_option( 'pmg_db_version', PMG_VERSION );
	}

	/**
	 * Ensure the upload directory exists and is protected from listing.
	 *
	 * @return array{path:string,url:string}
	 */
	public static function ensure_upload_dir() {
		$uploads = wp_upload_dir();
		$base    = trailingslashit( $uploads['basedir'] ) . 'pmg';
		$url     = trailingslashit( $uploads['baseurl'] ) . 'pmg';

		if ( ! file_exists( $base ) ) {
			wp_mkdir_p( $base );
		}

		$index = trailingslashit( $base ) . 'index.html';
		if ( ! file_exists( $index ) ) {
			// Silence directory listing.
			file_put_contents( $index, '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		return array(
			'path' => $base,
			'url'  => $url,
		);
	}
}

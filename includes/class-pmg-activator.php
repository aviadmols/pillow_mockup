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
	 * Modal-open click log table name (without prefix).
	 */
	const OPENS_TABLE = 'pmg_open_log';

	/**
	 * Funnel events table name (without prefix). One row per IP per stage.
	 */
	const EVENTS_TABLE = 'pmg_events';

	/**
	 * Daily unique page-view table name (without prefix). One row per IP per day.
	 */
	const VIEWS_TABLE = 'pmg_views';

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
	 * Fully qualified modal-open log table name.
	 *
	 * @return string
	 */
	public static function opens_table() {
		global $wpdb;
		return $wpdb->prefix . self::OPENS_TABLE;
	}

	/**
	 * Fully qualified funnel events table name.
	 *
	 * @return string
	 */
	public static function events_table() {
		global $wpdb;
		return $wpdb->prefix . self::EVENTS_TABLE;
	}

	/**
	 * Fully qualified daily unique page-view table name.
	 *
	 * @return string
	 */
	public static function views_table() {
		global $wpdb;
		return $wpdb->prefix . self::VIEWS_TABLE;
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
		$opens           = self::opens_table();
		$events          = self::events_table();
		$views           = self::views_table();

		$leads_sql = "CREATE TABLE {$leads} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session varchar(64) NOT NULL DEFAULT '',
			name varchar(191) NOT NULL DEFAULT '',
			phone varchar(64) NOT NULL DEFAULT '',
			email varchar(191) NOT NULL DEFAULT '',
			status varchar(32) NOT NULL DEFAULT 'new',
			address varchar(191) NOT NULL DEFAULT '',
			apartment varchar(191) NOT NULL DEFAULT '',
			city varchar(128) NOT NULL DEFAULT '',
			state varchar(128) NOT NULL DEFAULT '',
			zip varchar(32) NOT NULL DEFAULT '',
			attempts int(10) unsigned NOT NULL DEFAULT 0,
			original_image text NULL,
			mockup_image text NULL,
			cutout_image text NULL,
			total_cost decimal(12,6) NOT NULL DEFAULT 0,
			size varchar(64) NULL,
			price decimal(10,2) NULL,
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
			image_url text NULL,
			error_message text NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY session (session),
			KEY lead_id (lead_id),
			KEY type (type),
			KEY created_at (created_at)
		) {$charset_collate};";

		$opens_sql = "CREATE TABLE {$opens} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			ip varchar(64) NOT NULL DEFAULT '',
			session varchar(64) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY created_at (created_at)
		) {$charset_collate};";

		$events_sql = "CREATE TABLE {$events} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			ip varchar(64) NOT NULL DEFAULT '',
			stage varchar(20) NOT NULL DEFAULT '',
			session varchar(64) NOT NULL DEFAULT '',
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY ip_stage_post (ip,stage,post_id),
			KEY stage (stage),
			KEY post_id (post_id)
		) {$charset_collate};";

		$views_sql = "CREATE TABLE {$views} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			ip varchar(64) NOT NULL DEFAULT '',
			day date NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY ip_day (ip,day),
			KEY day (day)
		) {$charset_collate};";

		dbDelta( $leads_sql );
		dbDelta( $generations_sql );
		dbDelta( $opens_sql );
		dbDelta( $events_sql );
		dbDelta( $views_sql );

		self::migrate_events_post_id();

		update_option( 'pmg_db_version', PMG_VERSION );
	}

	/**
	 * Migrate the events table to the per-page dedup key. dbDelta never drops or
	 * alters existing keys, so the old UNIQUE KEY (ip,stage) must be removed
	 * explicitly — otherwise it keeps blocking a second row for the same IP and
	 * stage on a different page. Idempotent and safe to run on every upgrade.
	 *
	 * @return void
	 */
	protected static function migrate_events_post_id() {
		global $wpdb;
		$events = self::events_table();

		// Add the post_id column if dbDelta hasn't (older MySQL edge cases).
		$has_col = $wpdb->get_results( "SHOW COLUMNS FROM {$events} LIKE 'post_id'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		if ( empty( $has_col ) ) {
			$wpdb->query( "ALTER TABLE {$events} ADD post_id bigint(20) unsigned NOT NULL DEFAULT 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		}

		// Drop the legacy unique key so per-page rows are allowed.
		$old_key = $wpdb->get_results( "SHOW INDEX FROM {$events} WHERE Key_name = 'ip_stage'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		if ( ! empty( $old_key ) ) {
			$wpdb->query( "ALTER TABLE {$events} DROP INDEX ip_stage" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		}

		// Add the new per-page unique key if it isn't there yet.
		$new_key = $wpdb->get_results( "SHOW INDEX FROM {$events} WHERE Key_name = 'ip_stage_post'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		if ( empty( $new_key ) ) {
			$wpdb->query( "ALTER TABLE {$events} ADD UNIQUE KEY ip_stage_post (ip,stage,post_id)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		}
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

<?php
/**
 * Uninstall routine: removes options, tables and stored files.
 *
 * @package PillowMockupGenerator
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Remove options + transients.
delete_option( 'pmg_settings' );
delete_option( 'pmg_db_version' );
delete_transient( 'pmg_models_cache' );
delete_transient( 'pmg_test_result' );

// Drop custom tables.
$leads       = $wpdb->prefix . 'pmg_leads';
$generations = $wpdb->prefix . 'pmg_generations';
$wpdb->query( "DROP TABLE IF EXISTS {$leads}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$generations}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery

// Remove stored uploads.
$uploads = wp_upload_dir();
$dir     = trailingslashit( $uploads['basedir'] ) . 'pmg';
if ( is_dir( $dir ) ) {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $iterator as $file ) {
		if ( $file->isDir() ) {
			@rmdir( $file->getPathname() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		} else {
			@unlink( $file->getPathname() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_unlink
		}
	}
	@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
}

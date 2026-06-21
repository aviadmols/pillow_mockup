<?php
/**
 * Storage helper: decodes base64 data URLs and saves image files under uploads/pmg/<session>/.
 *
 * @package PillowMockupGenerator
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PMG_Storage
 */
class PMG_Storage {

	/**
	 * Allowed image mime types mapped to extensions.
	 *
	 * @var array<string,string>
	 */
	protected static $mime_ext = array(
		'image/png'  => 'png',
		'image/jpeg' => 'jpg',
		'image/jpg'  => 'jpg',
		'image/webp' => 'webp',
	);

	/**
	 * Get the base upload dir info for the plugin.
	 *
	 * @return array{path:string,url:string}
	 */
	public static function base_dir() {
		$uploads = wp_upload_dir();
		return array(
			'path' => trailingslashit( $uploads['basedir'] ) . 'pmg',
			'url'  => trailingslashit( $uploads['baseurl'] ) . 'pmg',
		);
	}

	/**
	 * Save a base64 data URL (or raw base64 with explicit mime) to a file.
	 *
	 * @param string $data_url Data URL or raw base64 string.
	 * @param string $session  Session token (used as sub-folder).
	 * @param string $label    File label (original|mockup|cutout).
	 * @return array{url:string,path:string}|WP_Error
	 */
	public static function save_data_url( $data_url, $session, $label ) {
		$decoded = self::decode_data_url( $data_url );
		if ( is_wp_error( $decoded ) ) {
			return $decoded;
		}

		$session = self::sanitize_segment( $session );
		$label   = self::sanitize_segment( $label );
		$base    = self::base_dir();
		$dir     = trailingslashit( $base['path'] ) . $session;

		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$ext      = $decoded['ext'];
		$filename = $label . '-' . substr( md5( $decoded['bytes'] . microtime() ), 0, 8 ) . '.' . $ext;
		$path     = trailingslashit( $dir ) . $filename;

		$written = file_put_contents( $path, $decoded['bytes'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === $written ) {
			return new WP_Error( 'pmg_write_failed', __( 'Could not write image file.', 'pillow-mockup-generator' ) );
		}

		return array(
			'url'  => trailingslashit( $base['url'] ) . $session . '/' . $filename,
			'path' => $path,
		);
	}

	/**
	 * Decode a data URL into raw bytes + extension.
	 *
	 * @param string $data_url Data URL.
	 * @return array{bytes:string,ext:string,mime:string}|WP_Error
	 */
	public static function decode_data_url( $data_url ) {
		$data_url = trim( (string) $data_url );

		if ( '' === $data_url ) {
			return new WP_Error( 'pmg_empty_image', __( 'No image data provided.', 'pillow-mockup-generator' ) );
		}

		$mime = 'image/png';
		$b64  = $data_url;

		if ( 0 === strpos( $data_url, 'data:' ) ) {
			if ( ! preg_match( '/^data:([a-z0-9.+\/-]+);base64,(.*)$/is', $data_url, $m ) ) {
				return new WP_Error( 'pmg_bad_data_url', __( 'Invalid image data URL.', 'pillow-mockup-generator' ) );
			}
			$mime = strtolower( $m[1] );
			$b64  = $m[2];
		}

		if ( ! isset( self::$mime_ext[ $mime ] ) ) {
			// Default unknown image types to png.
			$mime = 'image/png';
		}

		$bytes = base64_decode( $b64, true );
		if ( false === $bytes || '' === $bytes ) {
			return new WP_Error( 'pmg_bad_base64', __( 'Could not decode image data.', 'pillow-mockup-generator' ) );
		}

		return array(
			'bytes' => $bytes,
			'ext'   => self::$mime_ext[ $mime ],
			'mime'  => $mime,
		);
	}

	/**
	 * Read a stored image file and return it as a base64 data URL.
	 *
	 * @param string $path Absolute file path.
	 * @return string|WP_Error
	 */
	public static function file_to_data_url( $path ) {
		if ( ! $path || ! file_exists( $path ) ) {
			return new WP_Error( 'pmg_missing_file', __( 'Stored image not found.', 'pillow-mockup-generator' ) );
		}
		$bytes = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
		if ( false === $bytes ) {
			return new WP_Error( 'pmg_read_failed', __( 'Could not read stored image.', 'pillow-mockup-generator' ) );
		}
		$ext  = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		$mime = 'jpg' === $ext || 'jpeg' === $ext ? 'image/jpeg' : ( 'webp' === $ext ? 'image/webp' : 'image/png' );
		return 'data:' . $mime . ';base64,' . base64_encode( $bytes ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Map a public upload URL back to its absolute path (only within uploads/pmg).
	 *
	 * @param string $url Public URL.
	 * @return string Empty string if outside the plugin upload dir.
	 */
	public static function url_to_path( $url ) {
		$base = self::base_dir();
		$url  = (string) $url;
		if ( '' === $url || 0 !== strpos( $url, $base['url'] ) ) {
			return '';
		}
		$relative = ltrim( substr( $url, strlen( $base['url'] ) ), '/' );
		$relative = str_replace( array( '..', '\\' ), '', $relative );
		return trailingslashit( $base['path'] ) . $relative;
	}

	/**
	 * Remove all files stored for a session/lead folder.
	 *
	 * @param string $session Session token.
	 * @return void
	 */
	public static function delete_session_files( $session ) {
		$session = self::sanitize_segment( $session );
		if ( '' === $session ) {
			return;
		}
		$base = self::base_dir();
		$dir  = trailingslashit( $base['path'] ) . $session;
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$files = glob( trailingslashit( $dir ) . '*' );
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					wp_delete_file( $file );
				}
			}
		}
		// Attempt to remove the now-empty directory.
		@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
	}

	/**
	 * Sanitise a path segment to a safe slug.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	protected static function sanitize_segment( $value ) {
		$value = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $value );
		return substr( (string) $value, 0, 64 );
	}
}

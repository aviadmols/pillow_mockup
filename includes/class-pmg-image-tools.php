<?php
/**
 * Image post-processing helpers (chroma-key / background removal).
 *
 * Used by the experimental Lab to turn the AI-generated pillow (rendered on a
 * solid chroma background) into a transparent PNG cut-out that composites onto
 * the room mockup. Image models do not emit real alpha, so we remove the
 * uniform background ourselves via a flood-fill from the image borders — that
 * way any matching colour *inside* the pillow (e.g. white shirt text) is kept.
 *
 * @package PillowMockupGenerator
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PMG_ImageTools
 */
class PMG_ImageTools {

	/**
	 * Longest edge we process. Keeps the flood-fill fast and memory-bounded; the
	 * overlay is displayed scaled anyway.
	 */
	const MAX_EDGE = 900;

	/**
	 * Remove the uniform border background from an image data URL and return a
	 * transparent PNG data URL. On any failure the original data URL is returned
	 * unchanged (so generation still succeeds, just without transparency).
	 *
	 * @param string $data_url Source image data URL.
	 * @return string Transparent PNG data URL, or the original on failure.
	 */
	public static function cutout_data_url( $data_url ) {
		$decoded = PMG_Storage::decode_data_url( $data_url );
		if ( is_wp_error( $decoded ) ) {
			return (string) $data_url;
		}

		$png = self::remove_background( $decoded['bytes'] );
		if ( is_wp_error( $png ) || '' === (string) $png ) {
			return (string) $data_url;
		}

		return 'data:image/png;base64,' . base64_encode( $png ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Remove the border-connected background, returning PNG bytes (with alpha).
	 *
	 * @param string $bytes Raw image bytes.
	 * @return string|WP_Error
	 */
	protected static function remove_background( $bytes ) {
		if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
			$out = self::remove_background_imagick( $bytes );
			if ( ! is_wp_error( $out ) && '' !== $out ) {
				return $out;
			}
		}
		if ( function_exists( 'imagecreatefromstring' ) ) {
			return self::remove_background_gd( $bytes );
		}
		return new WP_Error( 'pmg_no_image_lib', __( 'No image library (Imagick/GD) is available to remove the background.', 'pillow-mockup-generator' ) );
	}

	/**
	 * Imagick implementation: flood-fill transparency from each corner.
	 *
	 * @param string $bytes Raw image bytes.
	 * @return string|WP_Error PNG bytes.
	 */
	protected static function remove_background_imagick( $bytes ) {
		try {
			$img = new Imagick();
			$img->readImageBlob( $bytes );
			$img->setImageFormat( 'png' );
			$img->setImageAlphaChannel( Imagick::ALPHACHANNEL_ACTIVATE );

			$w = $img->getImageWidth();
			$h = $img->getImageHeight();
			if ( $w < 2 || $h < 2 ) {
				return new WP_Error( 'pmg_imagick_size', 'image too small' );
			}
			if ( max( $w, $h ) > self::MAX_EDGE ) {
				$img->resizeImage( $w, $h, Imagick::FILTER_LANCZOS, 1, true );
				$w = $img->getImageWidth();
				$h = $img->getImageHeight();
			}

			$range = $img->getQuantumRange();
			$qr    = isset( $range['quantumRangeLong'] ) ? (float) $range['quantumRangeLong'] : 65535.0;
			$fuzz  = 0.16 * $qr;

			$seeds = array(
				array( 0, 0 ),
				array( $w - 1, 0 ),
				array( 0, $h - 1 ),
				array( $w - 1, $h - 1 ),
				array( (int) floor( $w / 2 ), 0 ),
				array( (int) floor( $w / 2 ), $h - 1 ),
			);
			foreach ( $seeds as $seed ) {
				$target = $img->getImagePixelColor( $seed[0], $seed[1] );
				$img->floodfillPaintImage( 'rgba(0,0,0,0)', $fuzz, $target, $seed[0], $seed[1], false );
			}

			$blob = $img->getImageBlob();
			$img->clear();
			$img->destroy();
			return $blob;
		} catch ( Exception $e ) {
			return new WP_Error( 'pmg_imagick_failed', $e->getMessage() );
		}
	}

	/**
	 * GD implementation: BFS flood-fill from the border, painting matching
	 * background pixels transparent. Interior pixels (even if they match the
	 * background colour) are preserved because they are not border-connected.
	 *
	 * @param string $bytes Raw image bytes.
	 * @return string|WP_Error PNG bytes.
	 */
	protected static function remove_background_gd( $bytes ) {
		$src = @imagecreatefromstring( $bytes ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! $src ) {
			return new WP_Error( 'pmg_gd_decode', 'could not decode image' );
		}

		$w = imagesx( $src );
		$h = imagesy( $src );

		if ( max( $w, $h ) > self::MAX_EDGE ) {
			$ratio = self::MAX_EDGE / max( $w, $h );
			$nw    = max( 1, (int) round( $w * $ratio ) );
			$nh    = max( 1, (int) round( $h * $ratio ) );
			$scaled = imagescale( $src, $nw, $nh );
			if ( $scaled ) {
				imagedestroy( $src );
				$src = $scaled;
				$w   = imagesx( $src );
				$h   = imagesy( $src );
			}
		}

		$total = $w * $h;

		// Reference background colour: average of the four corners.
		$bg = self::corner_average( $src, $w, $h );

		// Squared-distance threshold (per the sum of channel diffs squared).
		$thresh = 5200;

		$visited = str_repeat( "\0", $total );
		$stack   = array();

		// Seed every border pixel that matches the background.
		for ( $x = 0; $x < $w; $x++ ) {
			self::gd_seed( $src, $bg, $thresh, $x, 0, $w, $visited, $stack );
			self::gd_seed( $src, $bg, $thresh, $x, $h - 1, $w, $visited, $stack );
		}
		for ( $y = 0; $y < $h; $y++ ) {
			self::gd_seed( $src, $bg, $thresh, 0, $y, $w, $visited, $stack );
			self::gd_seed( $src, $bg, $thresh, $w - 1, $y, $w, $visited, $stack );
		}

		// Output canvas with alpha; start as a copy of the source.
		$out = imagecreatetruecolor( $w, $h );
		imagealphablending( $out, false );
		imagesavealpha( $out, true );
		imagecopy( $out, $src, 0, 0, 0, 0, $w, $h );
		$transparent = imagecolorallocatealpha( $out, 0, 0, 0, 127 );

		while ( $stack ) {
			$idx = array_pop( $stack );
			$x   = $idx % $w;
			$y   = (int) ( $idx / $w );

			imagesetpixel( $out, $x, $y, $transparent );

			// 4-connected neighbours.
			$neighbours = array(
				array( $x - 1, $y ),
				array( $x + 1, $y ),
				array( $x, $y - 1 ),
				array( $x, $y + 1 ),
			);
			foreach ( $neighbours as $n ) {
				$nx = $n[0];
				$ny = $n[1];
				if ( $nx < 0 || $ny < 0 || $nx >= $w || $ny >= $h ) {
					continue;
				}
				$nidx = $nx + $ny * $w;
				if ( "\0" !== $visited[ $nidx ] ) {
					continue;
				}
				if ( self::gd_matches( $src, $nx, $ny, $bg, $thresh ) ) {
					$visited[ $nidx ] = "\1";
					$stack[]          = $nidx;
				}
			}
		}

		ob_start();
		imagepng( $out );
		$png = ob_get_clean();

		imagedestroy( $src );
		imagedestroy( $out );

		return false === $png ? new WP_Error( 'pmg_gd_encode', 'could not encode png' ) : $png;
	}

	/**
	 * Seed helper: mark+push a border pixel if it matches the background.
	 *
	 * @param resource|GdImage $src     Image.
	 * @param array            $bg      Background rgb.
	 * @param int              $thresh  Distance threshold.
	 * @param int              $x       X.
	 * @param int              $y       Y.
	 * @param int              $w       Width.
	 * @param string           $visited Visited bitmap (by reference).
	 * @param array            $stack   Stack (by reference).
	 * @return void
	 */
	protected static function gd_seed( $src, $bg, $thresh, $x, $y, $w, &$visited, &$stack ) {
		$idx = $x + $y * $w;
		if ( "\0" !== $visited[ $idx ] ) {
			return;
		}
		if ( self::gd_matches( $src, $x, $y, $bg, $thresh ) ) {
			$visited[ $idx ] = "\1";
			$stack[]         = $idx;
		}
	}

	/**
	 * Whether a pixel is within the colour threshold of the background.
	 *
	 * @param resource|GdImage $src    Image.
	 * @param int              $x      X.
	 * @param int              $y      Y.
	 * @param array            $bg     Background rgb.
	 * @param int              $thresh Distance threshold (squared sum).
	 * @return bool
	 */
	protected static function gd_matches( $src, $x, $y, $bg, $thresh ) {
		$rgb = imagecolorat( $src, $x, $y );
		$r   = ( $rgb >> 16 ) & 0xFF;
		$g   = ( $rgb >> 8 ) & 0xFF;
		$b   = $rgb & 0xFF;
		$dr  = $r - $bg[0];
		$dg  = $g - $bg[1];
		$db  = $b - $bg[2];
		return ( $dr * $dr + $dg * $dg + $db * $db ) <= $thresh;
	}

	/**
	 * Average colour of the four corners (3x3 sample each).
	 *
	 * @param resource|GdImage $src Image.
	 * @param int              $w   Width.
	 * @param int              $h   Height.
	 * @return array{0:int,1:int,2:int}
	 */
	protected static function corner_average( $src, $w, $h ) {
		$points = array(
			array( 1, 1 ),
			array( $w - 2, 1 ),
			array( 1, $h - 2 ),
			array( $w - 2, $h - 2 ),
		);
		$r = 0;
		$g = 0;
		$b = 0;
		$n = 0;
		foreach ( $points as $p ) {
			$px = max( 0, min( $w - 1, $p[0] ) );
			$py = max( 0, min( $h - 1, $p[1] ) );
			$rgb = imagecolorat( $src, $px, $py );
			$r  += ( $rgb >> 16 ) & 0xFF;
			$g  += ( $rgb >> 8 ) & 0xFF;
			$b  += $rgb & 0xFF;
			$n++;
		}
		$n = max( 1, $n );
		return array( (int) round( $r / $n ), (int) round( $g / $n ), (int) round( $b / $n ) );
	}
}

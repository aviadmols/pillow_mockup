<?php
/**
 * Orchestrates image generation: builds prompts, calls OpenRouter, saves files, logs cost.
 *
 * @package PillowMockupGenerator
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PMG_Generator
 */
class PMG_Generator {

	/**
	 * Generate the on-sofa pillow mockup from a reference image URL.
	 *
	 * @param string $session    Session token.
	 * @param string $source_url Reference image (data URL or public URL).
	 * @param int    $lead_id    Optional lead id for logging.
	 * @return array|WP_Error { url, path, cost }
	 */
	public static function generate_mockup( $session, $source_url, $lead_id = 0 ) {
		$model  = (string) PMG_Settings::get( 'model', 'google/gemini-2.5-flash-image' );
		$prompt = (string) PMG_Settings::get( 'mockup_prompt', PMG_Settings::default_mockup_prompt() );
		return self::run( $session, $source_url, $model, $prompt, 'mockup', $lead_id );
	}

	/**
	 * Generate the print-ready, transparent die-cut shape.
	 *
	 * @param string $session    Session token.
	 * @param string $source_url Reference image (data URL or public URL).
	 * @param int    $lead_id    Lead id for logging.
	 * @return array|WP_Error { url, path, cost }
	 */
	public static function generate_cutout( $session, $source_url, $lead_id = 0 ) {
		$model = (string) PMG_Settings::get( 'cutout_model', '' );
		if ( '' === $model ) {
			$model = (string) PMG_Settings::get( 'model', 'google/gemini-2.5-flash-image' );
		}
		$prompt = (string) PMG_Settings::get( 'cutout_prompt', PMG_Settings::default_cutout_prompt() );
		return self::run( $session, $source_url, $model, $prompt, 'cutout', $lead_id );
	}

	/**
	 * Generate an isolated, transparent pillow overlay for the experimental Lab.
	 *
	 * Uses a caller-supplied prompt (the Lab's own, editable prompt) so it never
	 * shares the live print die-cut prompt. Output type is logged as 'overlay'.
	 *
	 * @param string $session    Session token.
	 * @param string $source_url Reference image (data URL or public URL).
	 * @param string $prompt     Prompt to use (falls back to the live cutout prompt if empty).
	 * @param int    $lead_id    Optional lead id for logging.
	 * @return array|WP_Error { url, path, cost }
	 */
	public static function generate_overlay( $session, $source_url, $prompt, $lead_id = 0 ) {
		$fallback = 'google/gemini-2.5-flash-image';

		$model = (string) PMG_Settings::get( 'cutout_model', '' );
		if ( '' === $model ) {
			$model = (string) PMG_Settings::get( 'model', $fallback );
		}
		$prompt = trim( (string) $prompt );
		if ( '' === $prompt ) {
			$prompt = (string) PMG_Settings::get( 'cutout_prompt', PMG_Settings::default_cutout_prompt() );
		}

		// Background removal happens client-side (browser canvas chroma-key) so we
		// keep the raw green-screen render here — no server image library needed.
		$result = self::run( $session, $source_url, $model, $prompt, 'overlay', $lead_id );

		// The strict "pro" image models frequently block benign requests with a
		// safety content_filter and return no image. Auto-retry once on the more
		// permissive flash model so the Lab keeps working without manual config.
		if ( is_wp_error( $result ) && 'pmg_no_image' === $result->get_error_code() && $model !== $fallback ) {
			$retry = self::run( $session, $source_url, $fallback, $prompt, 'overlay', $lead_id );
			if ( ! is_wp_error( $retry ) ) {
				return $retry;
			}
		}

		return $result;
	}

	/**
	 * Shared generation runner: call API, persist file, log generation row.
	 *
	 * @param string $session    Session token.
	 * @param string $source_url Reference image.
	 * @param string $model      Model id.
	 * @param string $prompt     Prompt.
	 * @param string        $type      'mockup' | 'cutout' | 'overlay'.
	 * @param int           $lead_id   Lead id (for logging).
	 * @param callable|null $transform Optional callback applied to the generated
	 *                                 image data URL before saving (e.g. background
	 *                                 removal). Returns a data URL; on failure the
	 *                                 original is kept.
	 * @return array|WP_Error
	 */
	protected static function run( $session, $source_url, $model, $prompt, $type, $lead_id, $transform = null ) {
		$client = new PMG_OpenRouter( PMG_Settings::get( 'api_key' ) );
		$result = $client->generate_image( $model, $prompt, $source_url );

		if ( is_wp_error( $result ) ) {
			PMG_Leads::log_generation(
				array(
					'session'       => $session,
					'lead_id'       => $lead_id,
					'type'          => $type,
					'model'         => $model,
					'status'        => 'error',
					'error_message' => $result->get_error_code() . ': ' . $result->get_error_message(),
				)
			);
			return $result;
		}

		$image_data_url = (string) $result['image_data_url'];
		if ( is_callable( $transform ) ) {
			$processed = call_user_func( $transform, $image_data_url );
			if ( is_string( $processed ) && '' !== $processed ) {
				$image_data_url = $processed;
			}
		}

		$saved = PMG_Storage::save_data_url( $image_data_url, $session, $type );
		if ( is_wp_error( $saved ) ) {
			PMG_Leads::log_generation(
				array(
					'session'       => $session,
					'lead_id'       => $lead_id,
					'type'          => $type,
					'model'         => $model,
					'cost'          => $result['cost'],
					'generation_id' => $result['generation_id'],
					'status'        => 'error',
					'error_message' => $saved->get_error_code() . ': ' . $saved->get_error_message(),
				)
			);
			return $saved;
		}

		PMG_Leads::log_generation(
			array(
				'session'           => $session,
				'lead_id'           => $lead_id,
				'type'              => $type,
				'model'             => $model,
				'cost'              => $result['cost'],
				'prompt_tokens'     => $result['prompt_tokens'],
				'completion_tokens' => $result['completion_tokens'],
				'generation_id'     => $result['generation_id'],
				'image_url'         => $saved['url'],
				'status'            => 'success',
			)
		);

		return array(
			'url'  => $saved['url'],
			'path' => $saved['path'],
			'cost' => (float) $result['cost'],
		);
	}
}

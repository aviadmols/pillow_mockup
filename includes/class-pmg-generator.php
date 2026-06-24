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
	 * Shared generation runner: call API, persist file, log generation row.
	 *
	 * @param string $session    Session token.
	 * @param string $source_url Reference image.
	 * @param string $model      Model id.
	 * @param string $prompt     Prompt.
	 * @param string $type       'mockup' | 'cutout'.
	 * @param int    $lead_id    Lead id (for logging).
	 * @return array|WP_Error
	 */
	protected static function run( $session, $source_url, $model, $prompt, $type, $lead_id ) {
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

		$saved = PMG_Storage::save_data_url( $result['image_data_url'], $session, $type );
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

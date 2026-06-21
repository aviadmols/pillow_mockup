<?php
/**
 * Minimal OpenRouter API client focused on image-to-image generation.
 *
 * @package PillowMockupGenerator
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PMG_OpenRouter
 */
class PMG_OpenRouter {

	/**
	 * OpenRouter chat-completions endpoint.
	 */
	const ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';

	/**
	 * OpenRouter models endpoint.
	 */
	const MODELS_ENDPOINT = 'https://openrouter.ai/api/v1/models';

	/**
	 * API key.
	 *
	 * @var string
	 */
	protected $api_key;

	/**
	 * Constructor.
	 *
	 * @param string $api_key API key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = trim( (string) $api_key );
	}

	/**
	 * Build request headers.
	 *
	 * @return array<string,string>
	 */
	protected function headers() {
		return array(
			'Authorization' => 'Bearer ' . $this->api_key,
			'Content-Type'  => 'application/json',
			'HTTP-Referer'  => home_url(),
			'X-Title'       => (string) PMG_Settings::get( 'site_title', get_bloginfo( 'name' ) ),
		);
	}

	/**
	 * Generate an image from a text prompt + a reference image.
	 *
	 * @param string $model    Model id.
	 * @param string $prompt   Text prompt.
	 * @param string $image_url Reference image as data URL or public URL.
	 * @return array|WP_Error {
	 *     @type string $image_data_url Generated image as a data URL.
	 *     @type float  $cost           Cost in USD.
	 *     @type string $generation_id  OpenRouter generation id.
	 *     @type int    $prompt_tokens
	 *     @type int    $completion_tokens
	 * }
	 */
	public function generate_image( $model, $prompt, $image_url ) {
		if ( '' === $this->api_key ) {
			return new WP_Error( 'pmg_no_key', __( 'OpenRouter API key is not configured.', 'pillow-mockup-generator' ) );
		}

		$content = array(
			array(
				'type' => 'text',
				'text' => (string) $prompt,
			),
			array(
				'type'      => 'image_url',
				'image_url' => array( 'url' => $image_url ),
			),
		);

		$body = array(
			'model'      => (string) $model,
			'messages'   => array(
				array(
					'role'    => 'user',
					'content' => $content,
				),
			),
			'modalities' => array( 'image', 'text' ),
			'usage'      => array( 'include' => true ),
		);

		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'headers' => $this->headers(),
				'body'    => wp_json_encode( $body ),
				'timeout' => 180,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code < 200 || $code >= 300 ) {
			$message = self::extract_error( $data, $raw, $code );
			return new WP_Error( 'pmg_api_error', $message, array( 'status' => $code ) );
		}

		if ( ! is_array( $data ) || empty( $data['choices'][0]['message'] ) ) {
			return new WP_Error( 'pmg_bad_response', __( 'Unexpected response from OpenRouter.', 'pillow-mockup-generator' ) );
		}

		$image_data_url = self::extract_image( $data['choices'][0]['message'] );
		if ( '' === $image_data_url ) {
			$detail = self::extract_error( $data, $raw, $code );
			return new WP_Error(
				'pmg_no_image',
				sprintf(
					/* translators: %s: detail message from the model. */
					__( 'The model did not return an image. It may not support image output. %s', 'pillow-mockup-generator' ),
					$detail
				)
			);
		}

		$usage = isset( $data['usage'] ) && is_array( $data['usage'] ) ? $data['usage'] : array();

		return array(
			'image_data_url'    => $image_data_url,
			'cost'              => isset( $usage['cost'] ) ? (float) $usage['cost'] : 0,
			'generation_id'     => isset( $data['id'] ) ? (string) $data['id'] : '',
			'prompt_tokens'     => isset( $usage['prompt_tokens'] ) ? (int) $usage['prompt_tokens'] : 0,
			'completion_tokens' => isset( $usage['completion_tokens'] ) ? (int) $usage['completion_tokens'] : 0,
		);
	}

	/**
	 * Extract the first image data URL from a message payload.
	 *
	 * @param array $message Message array.
	 * @return string
	 */
	protected static function extract_image( array $message ) {
		// OpenRouter image models return an `images` array on the message.
		if ( ! empty( $message['images'] ) && is_array( $message['images'] ) ) {
			foreach ( $message['images'] as $img ) {
				if ( isset( $img['image_url']['url'] ) && '' !== $img['image_url']['url'] ) {
					return (string) $img['image_url']['url'];
				}
				if ( isset( $img['url'] ) && '' !== $img['url'] ) {
					return (string) $img['url'];
				}
			}
		}

		// Some models embed images in the content array.
		if ( ! empty( $message['content'] ) && is_array( $message['content'] ) ) {
			foreach ( $message['content'] as $part ) {
				if ( isset( $part['image_url']['url'] ) && '' !== $part['image_url']['url'] ) {
					return (string) $part['image_url']['url'];
				}
			}
		}

		return '';
	}

	/**
	 * Build a readable error message from an error response.
	 *
	 * @param mixed  $data Parsed body.
	 * @param string $raw  Raw body.
	 * @param int    $code HTTP code.
	 * @return string
	 */
	protected static function extract_error( $data, $raw, $code ) {
		if ( is_array( $data ) ) {
			if ( isset( $data['error']['message'] ) ) {
				return (string) $data['error']['message'];
			}
			if ( isset( $data['error'] ) && is_string( $data['error'] ) ) {
				return (string) $data['error'];
			}
			if ( isset( $data['choices'][0]['message']['content'] ) && is_string( $data['choices'][0]['message']['content'] ) ) {
				return (string) $data['choices'][0]['message']['content'];
			}
		}
		$snippet = wp_strip_all_tags( (string) $raw );
		$snippet = trim( mb_substr( $snippet, 0, 300 ) );
		if ( '' !== $snippet ) {
			return $snippet;
		}
		/* translators: %d: HTTP status code. */
		return sprintf( __( 'HTTP error %d.', 'pillow-mockup-generator' ), $code );
	}

	/**
	 * Fetch the list of available models (id => name).
	 *
	 * @return array|WP_Error
	 */
	public function list_models() {
		$response = wp_remote_get(
			self::MODELS_ENDPOINT,
			array(
				'headers' => $this->headers(),
				'timeout' => 30,
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['data'] ) || ! is_array( $data['data'] ) ) {
			return new WP_Error( 'pmg_models_failed', __( 'Could not load the model list.', 'pillow-mockup-generator' ) );
		}

		$models = array();
		foreach ( $data['data'] as $m ) {
			if ( isset( $m['id'] ) ) {
				$models[ (string) $m['id'] ] = isset( $m['name'] ) ? (string) $m['name'] : (string) $m['id'];
			}
		}
		return $models;
	}

	/**
	 * Lightweight connectivity/auth test.
	 *
	 * @return true|WP_Error
	 */
	public function test_connection() {
		$models = $this->list_models();
		if ( is_wp_error( $models ) ) {
			return $models;
		}
		return true;
	}
}

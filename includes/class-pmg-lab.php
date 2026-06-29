<?php
/**
 * Experimental "Lab" flow (fully isolated from the live [pillow_mockup] flow).
 *
 * Concept:
 *  - The admin generates a single living-room base mockup once (an empty sofa
 *    scene) and configures where/how big the pillow should sit on it.
 *  - On the front end the [pillow_mockup_lab] shortcode lets a visitor upload a
 *    photo; the AI returns a transparent cut-out of just the pillow which is
 *    overlaid onto the room image. Size buttons scale the overlay in real time.
 *
 * Nothing here touches PMG_Shortcode / PMG_Rest / PMG_Admin or the existing
 * assets — it registers its own shortcode, REST route, admin page and assets,
 * and stores its configuration in a dedicated `pmg_lab` option.
 *
 * @package PillowMockupGenerator
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PMG_Lab
 */
class PMG_Lab {

	/**
	 * Shortcode tag.
	 */
	const SHORTCODE = 'pillow_mockup_lab';

	/**
	 * Dedicated option key (kept separate from PMG_OPTION_KEY).
	 */
	const OPTION = 'pmg_lab';

	/**
	 * Admin page slug.
	 */
	const PAGE = 'pmg-lab';

	/**
	 * Capability required for the admin page.
	 */
	const CAP = 'manage_options';

	/**
	 * Whether front-end assets have been enqueued this request.
	 *
	 * @var bool
	 */
	protected $enqueued = false;

	/* --------------------------------------------------------------------- */
	/* Registration                                                          */
	/* --------------------------------------------------------------------- */

	/**
	 * Wire all hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( self::SHORTCODE, array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );

		// The REST route is registered by PMG_Rest (the proven controller) so it
		// goes live exactly like the working endpoints; see PMG_Rest::routes().

		// admin-ajax transport (primary). admin-ajax.php is the most universally
		// available POST endpoint in WordPress and is not subject to the REST
		// rewrite/allow-list/proxy rules that were returning 405 for the REST route.
		add_action( 'wp_ajax_pmg_room_overlay', array( $this, 'ajax_room_overlay' ) );
		add_action( 'wp_ajax_nopriv_pmg_room_overlay', array( $this, 'ajax_room_overlay' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'menu' ), 20 );
			add_action( 'admin_init', array( $this, 'handle_actions' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		}
	}

	/* --------------------------------------------------------------------- */
	/* Options                                                               */
	/* --------------------------------------------------------------------- */

	/**
	 * Default lab configuration.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'room_url'    => '',
			'ref_url'     => '',
			'room_prompt' => 'A photorealistic modern living room interior with a single empty sofa, soft natural daylight, warm neutral tones, and clear empty space in the middle of the sofa where a decorative cushion would sit. Do not place any pillow or cushion on the sofa. Clean, realistic, high-quality interior photography.',
			'cutout_prompt' => 'From the reference photo, create a single realistic decorative throw pillow with the photo\'s main subject printed across its front fabric. Show the pillow straight-on, plump and three-dimensional with soft, natural fabric folds. Output ONLY the pillow, perfectly isolated on a fully transparent background (PNG with alpha). No room, no sofa, no surface, no background, and no drop shadow. Keep crisp, clean edges around the pillow so it can be composited cleanly onto another image.',
			'pos_x'       => 50.0,
			'pos_y'       => 56.0,
			'base_width'  => 34.0,
			'scales'      => array(
				'small'  => 0.8,
				'medium' => 1.0,
				'large'  => 1.25,
			),
		);
	}

	/**
	 * Get the merged lab option.
	 *
	 * @return array
	 */
	public static function get() {
		$stored = get_option( self::OPTION, array() );
		$stored = is_array( $stored ) ? $stored : array();
		$merged = wp_parse_args( $stored, self::defaults() );
		if ( ! is_array( $merged['scales'] ) ) {
			$merged['scales'] = self::defaults()['scales'];
		}
		return $merged;
	}

	/**
	 * Persist the lab option (merges over current values).
	 *
	 * @param array $patch Partial values to update.
	 * @return array The stored option.
	 */
	public static function update( array $patch ) {
		$current = self::get();
		$next    = array_merge( $current, $patch );
		update_option( self::OPTION, $next );
		return $next;
	}

	/* --------------------------------------------------------------------- */
	/* Front-end                                                             */
	/* --------------------------------------------------------------------- */

	/**
	 * Register (not enqueue) front-end assets.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style( 'pmg-lab', PMG_PLUGIN_URL . 'assets/css/lab.css', array(), PMG_VERSION );
		wp_register_script( 'pmg-lab', PMG_PLUGIN_URL . 'assets/js/lab.js', array(), PMG_VERSION, true );
	}

	/**
	 * Enqueue assets and localise config (once per request).
	 *
	 * @return void
	 */
	protected function enqueue() {
		if ( $this->enqueued ) {
			return;
		}
		$this->enqueued = true;

		wp_enqueue_style( 'pmg-lab' );
		wp_enqueue_script( 'pmg-lab' );

		$cfg   = self::get();
		$sizes = array();
		foreach ( PMG_Settings::sizes() as $tier ) {
			$scale = isset( $cfg['scales'][ $tier['id'] ] ) ? (float) $cfg['scales'][ $tier['id'] ] : 1.0;
			$sizes[] = array(
				'id'    => $tier['id'],
				'label' => $tier['label'],
				'cm'    => $tier['cm'],
				'scale' => $scale,
			);
		}

		wp_localize_script(
			'pmg-lab',
			'PMG_LAB_CONFIG',
			array(
				'restUrl'      => esc_url_raw( rest_url( PMG_REST_NAMESPACE . '/' ) ),
				'restRouteUrl' => esc_url_raw( add_query_arg( 'rest_route', '/' . PMG_REST_NAMESPACE . '/', home_url( '/' ) ) ),
				'ajaxUrl'      => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
				'ajaxAction'   => 'pmg_room_overlay',
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'nonceUrl'     => esc_url_raw( rest_url( PMG_REST_NAMESPACE . '/nonce' ) ),
				'roomUrl'      => esc_url_raw( (string) $cfg['room_url'] ),
				'posX'         => (float) $cfg['pos_x'],
				'posY'         => (float) $cfg['pos_y'],
				'baseWidth'    => (float) $cfg['base_width'],
				'sizes'        => $sizes,
				'maxPx'        => (int) PMG_Settings::get( 'max_upload_px', 1280 ),
				'i18n'         => array(
					'uploading'   => __( 'Creating your pillow…', 'pillow-mockup-generator' ),
					'invalidFile' => __( 'Please choose an image file (JPG, PNG or WEBP).', 'pillow-mockup-generator' ),
					'error'       => __( 'Something went wrong. Please try again.', 'pillow-mockup-generator' ),
				),
			)
		);
	}

	/**
	 * Render the lab widget.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$this->enqueue();

		$atts = shortcode_atts( array( 'class' => '' ), $atts, self::SHORTCODE );

		$lab   = self::get();
		$sizes = PMG_Settings::sizes();

		ob_start();
		include PMG_PLUGIN_DIR . 'templates/lab-widget.php';
		return (string) ob_get_clean();
	}

	/* --------------------------------------------------------------------- */
	/* REST                                                                  */
	/* --------------------------------------------------------------------- */

	/**
	 * Verify the standard WordPress REST nonce.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public function check_nonce( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'x_wp_nonce' );
		if ( ! $nonce ) {
			$nonce = (string) $request->get_param( '_wpnonce' );
		}
		return false !== wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * Shared overlay builder used by both transports (REST + admin-ajax).
	 *
	 * @param string $image       Uploaded data URL.
	 * @param mixed  $session_raw Raw session token.
	 * @return array{session:string,url:string}|WP_Error
	 */
	protected function build_overlay( $image, $session_raw ) {
		if ( ! PMG_Settings::is_configured() ) {
			return new WP_Error( 'pmg_not_configured', __( 'The mockup service is not configured yet.', 'pillow-mockup-generator' ), array( 'status' => 503 ) );
		}

		$image = (string) $image;
		if ( '' === trim( $image ) ) {
			return new WP_Error( 'pmg_no_image', __( 'No image provided.', 'pillow-mockup-generator' ), array( 'status' => 400 ) );
		}

		$session = $this->lab_session( $session_raw );
		$cfg     = self::get();
		$prompt  = isset( $cfg['cutout_prompt'] ) ? (string) $cfg['cutout_prompt'] : '';

		$cutout = PMG_Generator::generate_overlay( $session, $image, $prompt, 0 );
		if ( is_wp_error( $cutout ) ) {
			return $cutout;
		}

		return array(
			'session' => $session,
			'url'     => $cutout['url'],
		);
	}

	/**
	 * Generate a transparent pillow cut-out for the uploaded image (REST).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function lab_cutout( WP_REST_Request $request ) {
		$result = $this->build_overlay( $request->get_param( 'image' ), $request->get_param( 'session' ) );

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( $this->error_payload( $result ), 200 );
		}

		return new WP_REST_Response(
			array(
				'code'    => 'ok',
				'session' => $result['session'],
				'url'     => $result['url'],
			),
			200
		);
	}

	/**
	 * Build a JSON error payload that surfaces the real reason (the Lab is an
	 * experimental/admin tool, so showing the underlying error helps debugging).
	 * Returned with HTTP 200 so the message is always readable on the client.
	 *
	 * @param WP_Error $error Error.
	 * @return array
	 */
	protected function error_payload( WP_Error $error ) {
		$code = (string) $error->get_error_code();
		$raw  = trim( (string) $error->get_error_message() );

		switch ( $code ) {
			case 'pmg_no_image':
				// OpenRouter returned 200 but no image — usually the model's safety
				// content_filter blocking a benign request (common on the "pro" models).
				$message = __( 'The AI did not return an image for this photo — it was most likely blocked by the model\'s safety filter. Try a different photo, or set the Cutout model to "Nano Banana (Gemini 2.5 Flash Image)".', 'pillow-mockup-generator' );
				break;
			case 'pmg_not_configured':
			case 'pmg_no_key':
				$message = __( 'The AI service is not configured yet. Add your OpenRouter API key in Settings.', 'pillow-mockup-generator' );
				break;
			case 'pmg_no_image_provided':
				$message = __( 'No image was provided. Please choose a photo.', 'pillow-mockup-generator' );
				break;
			case 'pmg_api_error':
				$message = '' !== $raw ? $raw : __( 'The AI service returned an error. Please try again.', 'pillow-mockup-generator' );
				break;
			default:
				$message = '' !== $raw ? $raw : __( 'We couldn\'t create your pillow. Please try again.', 'pillow-mockup-generator' );
				break;
		}

		return array(
			'code'    => 'error',
			'error'   => $code,
			'message' => $message,
			'detail'  => $raw,
		);
	}

	/**
	 * Generate a transparent pillow cut-out (admin-ajax transport).
	 *
	 * Primary transport: admin-ajax.php reliably accepts POST on hosts where the
	 * REST route is blocked/redirected (the source of the 405). Always returns
	 * JSON and exits.
	 *
	 * @return void
	 */
	public function ajax_room_overlay() {
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			wp_send_json(
				array(
					'code'    => 'rest_cookie_invalid_nonce',
					'message' => __( 'Your session expired. Please refresh and try again.', 'pillow-mockup-generator' ),
				),
				403
			);
		}

		// The image is a base64 data URL; it must NOT be run through
		// sanitize_text_field (which would corrupt it). The nonce is verified above.
		$image   = isset( $_POST['image'] ) ? (string) wp_unslash( $_POST['image'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$session = isset( $_POST['session'] ) ? wp_unslash( $_POST['session'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		$result = $this->build_overlay( $image, $session );
		if ( is_wp_error( $result ) ) {
			wp_send_json( $this->error_payload( $result ), 200 );
		}

		wp_send_json(
			array(
				'code'    => 'ok',
				'session' => $result['session'],
				'url'     => $result['url'],
			),
			200
		);
	}

	/**
	 * Validate / create a lab session token (prefixed so it never collides
	 * with live customer sessions).
	 *
	 * @param mixed $raw Raw session value.
	 * @return string
	 */
	protected function lab_session( $raw ) {
		$raw = preg_replace( '/[^a-zA-Z0-9]/', '', (string) $raw );
		if ( strlen( (string) $raw ) >= 16 ) {
			return substr( $raw, 0, 32 );
		}
		return 'lab' . substr( md5( uniqid( 'pmglab', true ) . wp_rand() ), 0, 29 );
	}

	/* --------------------------------------------------------------------- */
	/* Admin                                                                 */
	/* --------------------------------------------------------------------- */

	/**
	 * Add the admin submenu under the existing Pillow Mockup menu.
	 *
	 * @return void
	 */
	public function menu() {
		add_submenu_page(
			'pmg-dashboard',
			__( 'Room Mockup (Lab)', 'pillow-mockup-generator' ),
			__( 'Room Mockup (Lab)', 'pillow-mockup-generator' ),
			self::CAP,
			self::PAGE,
			array( $this, 'render_lab' )
		);
	}

	/**
	 * Enqueue admin assets for the lab page only.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function admin_assets( $hook ) {
		if ( false === strpos( (string) $hook, self::PAGE ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'pmg-lab-admin', PMG_PLUGIN_URL . 'assets/js/lab-admin.js', array( 'jquery' ), PMG_VERSION, true );
		wp_enqueue_style( 'pmg-admin', PMG_PLUGIN_URL . 'assets/css/admin.css', array(), PMG_VERSION );
	}

	/**
	 * Handle admin form submissions (generate room / use image / save layout).
	 *
	 * @return void
	 */
	public function handle_actions() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		$is_generate = isset( $_POST['pmg_lab_generate'] );
		$is_use      = isset( $_POST['pmg_lab_use'] );
		$is_save     = isset( $_POST['pmg_lab_save'] );

		if ( ! $is_generate && ! $is_use && ! $is_save ) {
			return;
		}

		check_admin_referer( 'pmg_lab_action' );

		// Persist layout fields on every submission.
		$raw   = isset( $_POST['pmg_lab'] ) ? wp_unslash( $_POST['pmg_lab'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitized
		$raw   = is_array( $raw ) ? $raw : array();
		$patch = $this->sanitize_layout( $raw );

		// Reference image URL (from the WP media picker).
		$ref_url = isset( $_POST['pmg_lab']['ref_url'] ) ? esc_url_raw( wp_unslash( $_POST['pmg_lab']['ref_url'] ) ) : '';
		$patch['ref_url'] = $ref_url;

		if ( $is_save ) {
			self::update( $patch );
			$this->redirect_with( array( 'pmg_notice' => 'saved' ) );
		}

		if ( $is_use ) {
			$patch['room_url'] = $ref_url;
			self::update( $patch );
			$this->redirect_with( array( 'pmg_notice' => '' === $ref_url ? 'no_ref' : 'room_set' ) );
		}

		// Generate with AI.
		self::update( $patch );
		$notice = $this->generate_room( $ref_url, isset( $patch['room_prompt'] ) ? $patch['room_prompt'] : '' );
		$this->redirect_with( array( 'pmg_notice' => $notice ) );
	}

	/**
	 * Sanitise the layout/prompt fields from the admin form.
	 *
	 * @param array $raw Raw POST values.
	 * @return array
	 */
	protected function sanitize_layout( array $raw ) {
		$clamp = function ( $v, $min, $max, $fallback ) {
			if ( '' === $v || ! is_numeric( $v ) ) {
				return $fallback;
			}
			return max( $min, min( $max, (float) $v ) );
		};

		$defaults = self::defaults();
		$patch    = array(
			'pos_x'       => $clamp( $raw['pos_x'] ?? '', 0, 100, $defaults['pos_x'] ),
			'pos_y'       => $clamp( $raw['pos_y'] ?? '', 0, 100, $defaults['pos_y'] ),
			'base_width'  => $clamp( $raw['base_width'] ?? '', 2, 100, $defaults['base_width'] ),
			'room_prompt' => isset( $raw['room_prompt'] ) ? sanitize_textarea_field( $raw['room_prompt'] ) : $defaults['room_prompt'],
			'cutout_prompt' => isset( $raw['cutout_prompt'] ) ? sanitize_textarea_field( $raw['cutout_prompt'] ) : $defaults['cutout_prompt'],
		);

		$scales = array();
		foreach ( PMG_Settings::sizes() as $tier ) {
			$id           = $tier['id'];
			$val          = isset( $raw['scales'][ $id ] ) ? $raw['scales'][ $id ] : '';
			$scales[ $id ] = $clamp( $val, 0.1, 5, isset( $defaults['scales'][ $id ] ) ? $defaults['scales'][ $id ] : 1.0 );
		}
		$patch['scales'] = $scales;

		return $patch;
	}

	/**
	 * Generate the room base mockup from a reference image via the AI.
	 *
	 * @param string $ref_url Public reference image URL.
	 * @param string $prompt  Room prompt.
	 * @return string Notice key.
	 */
	protected function generate_room( $ref_url, $prompt ) {
		if ( '' === $ref_url ) {
			return 'no_ref';
		}
		if ( '' === trim( (string) $prompt ) ) {
			$prompt = self::defaults()['room_prompt'];
		}

		$model  = (string) PMG_Settings::get( 'model', 'google/gemini-2.5-flash-image' );
		$client = new PMG_OpenRouter( PMG_Settings::get( 'api_key' ) );
		$result = $client->generate_image( $model, $prompt, $ref_url );
		if ( is_wp_error( $result ) ) {
			return 'room_err';
		}

		$saved = PMG_Storage::save_data_url( $result['image_data_url'], 'lab', 'room' );
		if ( is_wp_error( $saved ) ) {
			return 'room_err';
		}

		self::update( array( 'room_url' => $saved['url'] ) );
		return 'room_ok';
	}

	/**
	 * Redirect back to the lab page with query args.
	 *
	 * @param array $args Extra query args.
	 * @return void
	 */
	protected function redirect_with( array $args = array() ) {
		$url = add_query_arg( array_merge( array( 'page' => self::PAGE ), $args ), admin_url( 'admin.php' ) );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Render the admin lab page.
	 *
	 * @return void
	 */
	public function render_lab() {
		$lab   = self::get();
		$sizes = PMG_Settings::sizes();
		$notice = isset( $_GET['pmg_notice'] ) ? sanitize_key( wp_unslash( $_GET['pmg_notice'] ) ) : '';
		include PMG_PLUGIN_DIR . 'templates/lab-admin.php';
	}
}

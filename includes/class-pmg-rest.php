<?php
/**
 * REST API: the three front-end actions (generate, lead, finalize) plus gating logic.
 *
 * @package PillowMockupGenerator
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PMG_Rest
 */
class PMG_Rest {

	/**
	 * Transient prefix for per-session working state.
	 */
	const WORK_PREFIX = 'pmg_work_';

	/**
	 * Max accepted base64 upload length (~10 MB encoded).
	 */
	const MAX_UPLOAD_BYTES = 10485760;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function routes() {
		$args = array(
			'methods'             => 'POST',
			'permission_callback' => array( $this, 'check_nonce' ),
		);

		register_rest_route( PMG_REST_NAMESPACE, '/generate', array_merge( $args, array( 'callback' => array( $this, 'generate' ) ) ) );
		register_rest_route( PMG_REST_NAMESPACE, '/state', array_merge( $args, array( 'callback' => array( $this, 'state' ) ) ) );
		register_rest_route( PMG_REST_NAMESPACE, '/lead', array_merge( $args, array( 'callback' => array( $this, 'lead' ) ) ) );
		register_rest_route( PMG_REST_NAMESPACE, '/finalize', array_merge( $args, array( 'callback' => array( $this, 'finalize' ) ) ) );

		// Experimental room-overlay (lab) endpoint. Registered here, in the proven
		// REST controller, so it goes live exactly like the working routes above.
		// The callback/permission live on the isolated PMG_Lab instance.
		$lab = PMG_Plugin::instance()->lab;
		if ( $lab instanceof PMG_Lab ) {
			register_rest_route(
				PMG_REST_NAMESPACE,
				'/room-overlay',
				array(
					'methods'             => 'POST',
					'permission_callback' => array( $lab, 'check_nonce' ),
					'callback'            => array( $lab, 'lab_cutout' ),
				)
			);
		}

		// Public, never-cached endpoint that hands out a fresh REST nonce.
		register_rest_route(
			PMG_REST_NAMESPACE,
			'/nonce',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'get_nonce' ),
			)
		);

		// Public, fire-and-forget counter for "open modal" button clicks. Kept
		// permission-free so navigator.sendBeacon works on cached pages without a
		// nonce header; it only ever writes an anonymous click count + IP.
		register_rest_route(
			PMG_REST_NAMESPACE,
			'/track-open',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'track_open' ),
			)
		);
	}

	/**
	 * Verify the standard WordPress REST nonce.
	 *
	 * Using the `wp_rest` action (sent via the X-WP-Nonce header) keeps logged-in
	 * users authenticated — core would otherwise downgrade them to user 0 and break
	 * a custom nonce — while still protecting anonymous requests from CSRF.
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
	 * Return a fresh REST nonce for the current visitor (cache-proof).
	 *
	 * @return WP_REST_Response
	 */
	public function get_nonce() {
		$response = new WP_REST_Response( array( 'nonce' => wp_create_nonce( 'wp_rest' ) ), 200 );
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
		return $response;
	}

	/**
	 * Record a single "open modal" button click (count + IP). Fire-and-forget:
	 * always returns a tiny no-content response so the beacon never blocks.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function track_open( WP_REST_Request $request ) {
		$session = preg_replace( '/[^a-zA-Z0-9]/', '', (string) $request->get_param( 'session' ) );
		$session = substr( (string) $session, 0, 32 );
		$ip      = $this->client_ip();
		$stage   = sanitize_key( (string) $request->get_param( 'stage' ) );
		$post_id = (int) $request->get_param( 'pageId' );

		if ( 'view' === $stage ) {
			// Unique daily page view (one row per IP per day).
			PMG_Leads::log_view( $ip );
		} elseif ( 'size' === $stage ) {
			// Funnel: visitor chose a size.
			PMG_Leads::log_event( $ip, 'size', $session, $post_id );
		} else {
			// Default: a CTA/open-modal click — keep the raw counter and the
			// per-IP funnel "cta" stage.
			PMG_Leads::log_open( $ip, $session );
			PMG_Leads::log_event( $ip, 'cta', $session, $post_id );
		}

		$response = new WP_REST_Response( null, 204 );
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
		return $response;
	}

	/* --------------------------------------------------------------------- */
	/* Endpoints                                                              */
	/* --------------------------------------------------------------------- */

	/**
	 * Generate (or regenerate) a mockup, enforcing the free-first / details-gate rules.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function generate( WP_REST_Request $request ) {
		if ( ! PMG_Settings::is_configured() ) {
			return new WP_Error( 'pmg_not_configured', __( 'The mockup service is not configured yet.', 'pillow-mockup-generator' ), array( 'status' => 503 ) );
		}

		// Experimental Lab piggybacks on this (allow-listed) endpoint: some hosts
		// block POST to every REST path except the original ones, so the isolated
		// /room-overlay route and admin-ajax both 405. When mode=lab_overlay we
		// hand off to the Lab and return early — the normal flow is untouched.
		if ( 'lab_overlay' === sanitize_key( (string) $request->get_param( 'mode' ) ) ) {
			$lab = PMG_Plugin::instance()->lab;
			if ( $lab instanceof PMG_Lab ) {
				return $lab->lab_cutout( $request );
			}
			return new WP_Error( 'pmg_not_configured', __( 'The mockup service is not configured yet.', 'pillow-mockup-generator' ), array( 'status' => 503 ) );
		}

		if ( $this->rate_limited() ) {
			return new WP_REST_Response(
				array(
					'code'    => 'rate_limited',
					'message' => __( 'Daily limit reached on this connection. Please try again tomorrow.', 'pillow-mockup-generator' ),
				),
				429
			);
		}

		$session = $this->resolve_session( $request->get_param( 'session' ) );
		$image   = (string) $request->get_param( 'image' );
		$lead    = PMG_Leads::get_by_session( $session );
		$has_lead = $lead && is_email( $lead['email'] );

		$max_mockups  = max( 1, (int) PMG_Settings::get( 'max_mockups', 3 ) );
		$done_mockups = PMG_Leads::count_generations( $session, 'mockup', 'success' );

		// Gating: up to N mockups are free with no details required. Once the cap
		// is reached, stop generating (details are only collected at checkout).
		if ( $done_mockups >= $max_mockups ) {
			return new WP_REST_Response(
				array(
					'code'          => 'max_attempts',
					'session'       => $session,
					'attempts_left' => 0,
					'message'       => (string) PMG_Settings::get( 'text_max_reached' ),
				),
				429
			);
		}

		// Resolve the reference image (new upload or reuse stored original).
		$source = $this->resolve_source_image( $request, $session, $lead, $image );
		if ( is_wp_error( $source ) ) {
			return $this->error_response( $source );
		}

		$this->bump_rate();

		$result = PMG_Generator::generate_mockup( $session, $source['data_url'], $lead ? (int) $lead['id'] : 0 );
		if ( is_wp_error( $result ) ) {
			return $this->error_response( $result );
		}

		// Update working state.
		$work               = $this->get_work( $session );
		$work['original_url']  = $source['original_url'];
		$work['original_path'] = $source['original_path'];
		$work['mockup_url']    = $result['url'];
		$work['mockup_path']   = $result['path'];
		$this->set_work( $session, $work );

		$total_done = $done_mockups + 1;

		if ( $has_lead ) {
			PMG_Leads::upsert(
				$session,
				array(
					'mockup_image'   => $result['url'],
					'original_image' => $source['original_url'],
					'attempts'       => $total_done,
					'total_cost'     => PMG_Leads::session_cost( $session ),
				)
			);
		}

		$attempts_left = max( 0, $max_mockups - $total_done );

		// Funnel: visitor successfully created a mockup (once per IP).
		PMG_Leads::log_event( $this->client_ip(), 'generate', $session, (int) $request->get_param( 'pageId' ) );

		return new WP_REST_Response(
			array(
				'code'          => 'ok',
				'session'       => $session,
				'mockup_url'    => $result['url'],
				'mockups'       => PMG_Leads::session_mockups( $session ),
				'selected'      => $result['url'],
				'attempt'       => $total_done,
				'max_attempts'  => $max_mockups,
				'attempts_left' => $attempts_left,
				'has_lead'      => $has_lead,
			),
			200
		);
	}

	/**
	 * Return the current saved state for a returning visitor (their generated
	 * mockups, selection, lead and order status). Read-only.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function state( WP_REST_Request $request ) {
		$session = $this->resolve_session( $request->get_param( 'session' ) );
		$mockups = PMG_Leads::session_mockups( $session );
		$lead    = PMG_Leads::get_by_session( $session );
		$has_lead = $lead && is_email( $lead['email'] );

		$max_mockups  = max( 1, (int) PMG_Settings::get( 'max_mockups', 3 ) );
		$done_mockups = PMG_Leads::count_generations( $session, 'mockup', 'success' );
		$attempts_left = max( 0, $max_mockups - $done_mockups );

		// Prefer the explicitly selected/saved mockup, else the latest one.
		$selected = '';
		if ( $lead && ! empty( $lead['mockup_image'] ) && in_array( $lead['mockup_image'], $mockups, true ) ) {
			$selected = $lead['mockup_image'];
		} elseif ( $mockups ) {
			$selected = $mockups[ count( $mockups ) - 1 ];
		}

		return new WP_REST_Response(
			array(
				'code'          => 'ok',
				'session'       => $session,
				'mockups'       => $mockups,
				'selected'      => $selected,
				'has_lead'      => $has_lead,
				'status'        => $lead ? (string) $lead['status'] : '',
				'attempts_left' => $attempts_left,
			),
			200
		);
	}

	/**
	 * Capture lead details, store the working images on the lead, and email both parties.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function lead( WP_REST_Request $request ) {
		$session   = $this->resolve_session( $request->get_param( 'session' ) );
		$phone     = sanitize_text_field( (string) $request->get_param( 'phone' ) );
		$address   = sanitize_text_field( (string) $request->get_param( 'address' ) );
		$apartment = sanitize_text_field( (string) $request->get_param( 'apartment' ) );
		$city      = sanitize_text_field( (string) $request->get_param( 'city' ) );
		$state     = sanitize_text_field( (string) $request->get_param( 'state' ) );
		$zip       = sanitize_text_field( (string) $request->get_param( 'zip' ) );

		// Optional contact fields kept for backward compatibility with older forms.
		$email = sanitize_email( (string) $request->get_param( 'email' ) );
		$name  = sanitize_text_field( (string) $request->get_param( 'name' ) );
		if ( '' === $name ) {
			$first_name = sanitize_text_field( (string) $request->get_param( 'first_name' ) );
			$last_name  = sanitize_text_field( (string) $request->get_param( 'last_name' ) );
			$name       = trim( $first_name . ' ' . $last_name );
		}

		// A valid email is required so we can deliver the design; the phone and
		// everything else are optional.
		$errors = array();
		if ( '' === $email || ! is_email( $email ) ) {
			$errors['email'] = __( 'A valid email is required.', 'pillow-mockup-generator' );
		}
		if ( $errors ) {
			return new WP_REST_Response(
				array(
					'code'    => 'invalid',
					'errors'  => $errors,
					'session' => $session,
				),
				422
			);
		}

		$work        = $this->get_work( $session );
		$max_attempts = (int) PMG_Settings::get( 'max_attempts', 5 );

		// Always create a fresh record for every submission, so repeat
		// registrations (even with the same email / phone / IP / session) are
		// each saved as their own row. finalize() updates the latest row.
		$lead_id = PMG_Leads::insert_lead(
			$session,
			array(
				'name'           => $name,
				'phone'          => $phone,
				'email'          => $email,
				'address'        => $address,
				'apartment'      => $apartment,
				'city'           => $city,
				'state'          => $state,
				'zip'            => $zip,
				'status'         => 'new',
				'attempts'       => PMG_Leads::count_generations( $session, 'mockup', 'success' ),
				'original_image' => isset( $work['original_url'] ) ? $work['original_url'] : '',
				'mockup_image'   => isset( $work['mockup_url'] ) ? $work['mockup_url'] : '',
				'total_cost'     => PMG_Leads::session_cost( $session ),
				'ip'             => $this->client_ip(),
			)
		);

		$this->attach_generations_to_lead( $session, $lead_id );

		// No emails are sent at this step — both the customer confirmation and the
		// admin notification are sent only once the order is finalized.

		$done          = PMG_Leads::count_generations( $session, 'mockup', 'success' );
		$attempts_left = max( 0, $max_attempts - max( 0, $done - 1 ) );

		return new WP_REST_Response(
			array(
				'code'          => 'ok',
				'session'       => $session,
				'has_lead'      => true,
				'attempts_left' => $attempts_left,
			),
			200
		);
	}

	/**
	 * Finalize the selection: record the order and notify both parties immediately.
	 *
	 * The print-ready cut-out is intentionally NOT generated here — it is produced
	 * on demand from the admin order screen — so the visitor gets an instant
	 * confirmation without waiting on a second AI render.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function finalize( WP_REST_Request $request ) {
		$session = $this->resolve_session( $request->get_param( 'session' ) );
		$lead    = PMG_Leads::get_by_session( $session );

		if ( ! $lead || ! is_email( $lead['email'] ) ) {
			return new WP_REST_Response(
				array(
					'code'    => 'need_details',
					'session' => $session,
				),
				403
			);
		}

		// Resolve which mockup the visitor chose (validated against this session).
		$work     = $this->get_work( $session );
		$mockups  = PMG_Leads::session_mockups( $session );
		$selected = esc_url_raw( (string) $request->get_param( 'mockup' ) );
		if ( '' === $selected || ! in_array( $selected, $mockups, true ) ) {
			$selected = isset( $work['mockup_url'] ) && $work['mockup_url'] ? $work['mockup_url'] : $lead['mockup_image'];
		}

		// Validate the chosen size against configured tiers and capture its price.
		$update = array(
			'status'       => 'completed',
			'mockup_image' => $selected,
			'total_cost'   => PMG_Leads::session_cost( $session ),
		);

		$size_id = sanitize_key( (string) $request->get_param( 'size' ) );
		if ( '' !== $size_id ) {
			$tier = PMG_Settings::size( $size_id );
			if ( $tier ) {
				$update['size']  = $tier['label'];
				$update['price'] = $tier['price'];
			}
		}

		PMG_Leads::upsert( $session, $update );

		$lead = PMG_Leads::get_by_session( $session );
		if ( $lead ) {
			PMG_Emailer::notify_customer( $lead );
			PMG_Emailer::notify_admin_order( $lead );
		}

		// Funnel: visitor completed the purchase (once per IP).
		PMG_Leads::log_event( $this->client_ip(), 'purchase', $session, (int) $request->get_param( 'pageId' ) );

		return new WP_REST_Response(
			array(
				'code'    => 'done',
				'session' => $session,
			),
			200
		);
	}

	/* --------------------------------------------------------------------- */
	/* Helpers                                                                */
	/* --------------------------------------------------------------------- */

	/**
	 * Validate / create a session token.
	 *
	 * @param mixed $raw Raw session value from client.
	 * @return string
	 */
	protected function resolve_session( $raw ) {
		$raw = preg_replace( '/[^a-zA-Z0-9]/', '', (string) $raw );
		if ( strlen( (string) $raw ) >= 16 ) {
			return substr( $raw, 0, 32 );
		}
		return substr( md5( uniqid( 'pmg', true ) . wp_rand() ), 0, 32 );
	}

	/**
	 * Resolve the reference image: a freshly uploaded one (saved as original) or the stored original.
	 *
	 * @param WP_REST_Request $request Request.
	 * @param string          $session Session token.
	 * @param array|null      $lead    Lead row.
	 * @param string          $image   Uploaded data URL (may be empty).
	 * @return array{data_url:string,original_url:string,original_path:string}|WP_Error
	 */
	protected function resolve_source_image( WP_REST_Request $request, $session, $lead, $image ) {
		$image = trim( (string) $image );

		if ( '' !== $image ) {
			if ( strlen( $image ) > self::MAX_UPLOAD_BYTES ) {
				return new WP_Error( 'pmg_too_large', __( 'The uploaded image is too large.', 'pillow-mockup-generator' ), array( 'status' => 413 ) );
			}
			$saved = PMG_Storage::save_data_url( $image, $session, 'original' );
			if ( is_wp_error( $saved ) ) {
				return $saved;
			}
			return array(
				'data_url'      => $image,
				'original_url'  => $saved['url'],
				'original_path' => $saved['path'],
			);
		}

		// No new image: reuse the stored original (for "try another result").
		$work          = $this->get_work( $session );
		$original_path = $this->resolve_original_path( $session, $lead, $work );
		if ( ! $original_path ) {
			return new WP_Error( 'pmg_no_image', __( 'Please upload a photo first.', 'pillow-mockup-generator' ), array( 'status' => 400 ) );
		}
		$data_url = PMG_Storage::file_to_data_url( $original_path );
		if ( is_wp_error( $data_url ) ) {
			return $data_url;
		}

		$original_url = isset( $work['original_url'] ) ? $work['original_url'] : ( $lead ? $lead['original_image'] : '' );

		return array(
			'data_url'      => $data_url,
			'original_url'  => $original_url,
			'original_path' => $original_path,
		);
	}

	/**
	 * Determine the original image file path from working state or the lead URL.
	 *
	 * @param string     $session Session token.
	 * @param array|null $lead    Lead row.
	 * @param array      $work    Working state.
	 * @return string Empty string if not found.
	 */
	protected function resolve_original_path( $session, $lead, array $work ) {
		if ( ! empty( $work['original_path'] ) && file_exists( $work['original_path'] ) ) {
			return $work['original_path'];
		}
		if ( $lead && ! empty( $lead['original_image'] ) ) {
			$path = PMG_Storage::url_to_path( $lead['original_image'] );
			if ( $path && file_exists( $path ) ) {
				return $path;
			}
		}
		return '';
	}

	/**
	 * Link unattached generation rows to the lead id.
	 *
	 * @param string $session Session token.
	 * @param int    $lead_id Lead id.
	 * @return void
	 */
	protected function attach_generations_to_lead( $session, $lead_id ) {
		global $wpdb;
		$table = PMG_Activator::generations_table();
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "UPDATE {$table} SET lead_id = %d WHERE session = %s AND ( lead_id IS NULL OR lead_id = 0 )", absint( $lead_id ), $session ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	/**
	 * Read per-session working state.
	 *
	 * @param string $session Session token.
	 * @return array
	 */
	protected function get_work( $session ) {
		$work = get_transient( self::WORK_PREFIX . $session );
		return is_array( $work ) ? $work : array();
	}

	/**
	 * Store per-session working state.
	 *
	 * @param string $session Session token.
	 * @param array  $work    State.
	 * @return void
	 */
	protected function set_work( $session, array $work ) {
		set_transient( self::WORK_PREFIX . $session, $work, DAY_IN_SECONDS );
	}

	/**
	 * Resolve the client IP address.
	 *
	 * @return string
	 */
	protected function client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return substr( $ip, 0, 64 );
	}

	/**
	 * Transient key for the per-IP daily generation counter.
	 *
	 * @return string
	 */
	protected function rate_key() {
		return 'pmg_rl_' . md5( $this->client_ip() );
	}

	/**
	 * Whether the current IP has reached its daily generation cap.
	 *
	 * @return bool
	 */
	protected function rate_limited() {
		$cap = (int) PMG_Settings::get( 'rate_limit_per_ip', 40 );
		if ( $cap <= 0 ) {
			return false;
		}
		return (int) get_transient( $this->rate_key() ) >= $cap;
	}

	/**
	 * Increment the per-IP daily generation counter.
	 *
	 * @return void
	 */
	protected function bump_rate() {
		$cap = (int) PMG_Settings::get( 'rate_limit_per_ip', 40 );
		if ( $cap <= 0 ) {
			return;
		}
		$key   = $this->rate_key();
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, DAY_IN_SECONDS );
	}

	/**
	 * Convert a WP_Error into a REST response with a sensible status.
	 *
	 * @param WP_Error $error Error.
	 * @return WP_REST_Response
	 */
	protected function error_response( WP_Error $error ) {
		$data   = $error->get_error_data();
		$status = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 500;
		return new WP_REST_Response(
			array(
				'code'    => $error->get_error_code(),
				'message' => $error->get_error_message(),
			),
			$status
		);
	}
}

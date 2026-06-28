<?php
/**
 * Data access for leads and the generations (request/cost) log.
 *
 * @package PillowMockupGenerator
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PMG_Leads
 */
class PMG_Leads {

	/**
	 * Current MySQL datetime in site timezone.
	 *
	 * @return string
	 */
	protected static function now() {
		return current_time( 'mysql' );
	}

	/* --------------------------------------------------------------------- */
	/* Leads                                                                  */
	/* --------------------------------------------------------------------- */

	/**
	 * Get a lead row by session.
	 *
	 * @param string $session Session token.
	 * @return array|null
	 */
	public static function get_by_session( $session ) {
		global $wpdb;
		$table = PMG_Activator::leads_table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE session = %s ORDER BY id DESC LIMIT 1", $session ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		return $row ? $row : null;
	}

	/**
	 * Get a lead by id.
	 *
	 * @param int $id Lead id.
	 * @return array|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$table = PMG_Activator::leads_table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		return $row ? $row : null;
	}

	/**
	 * Create or update a lead identified by session.
	 *
	 * @param string $session Session token.
	 * @param array  $data    Column => value pairs.
	 * @return int Lead id.
	 */
	public static function upsert( $session, array $data ) {
		global $wpdb;
		$table    = PMG_Activator::leads_table();
		$existing = self::get_by_session( $session );

		$allowed = array( 'name', 'phone', 'email', 'address', 'apartment', 'city', 'state', 'zip', 'status', 'attempts', 'original_image', 'mockup_image', 'cutout_image', 'total_cost', 'size', 'price', 'ip' );
		$payload = array();
		foreach ( $allowed as $col ) {
			if ( array_key_exists( $col, $data ) ) {
				$payload[ $col ] = $data[ $col ];
			}
		}

		if ( $existing ) {
			$payload['updated_at'] = self::now();
			$wpdb->update( $table, $payload, array( 'id' => $existing['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return (int) $existing['id'];
		}

		$payload['session']    = $session;
		$payload['created_at'] = self::now();
		$payload['updated_at'] = self::now();
		$wpdb->insert( $table, $payload ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->insert_id;
	}

	/**
	 * Delete a lead and its files + generation logs.
	 *
	 * @param int $id Lead id.
	 * @return bool
	 */
	public static function delete( $id ) {
		global $wpdb;
		$lead = self::get( $id );
		if ( ! $lead ) {
			return false;
		}
		PMG_Storage::delete_session_files( $lead['session'] );

		$leads_table = PMG_Activator::leads_table();
		$gen_table   = PMG_Activator::generations_table();
		$wpdb->delete( $leads_table, array( 'id' => absint( $id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $gen_table, array( 'lead_id' => absint( $id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return true;
	}

	/**
	 * Query leads for the admin list.
	 *
	 * @param array $args { per_page, page, search, status }.
	 * @return array{items:array,total:int}
	 */
	public static function query( array $args = array() ) {
		global $wpdb;
		$table = PMG_Activator::leads_table();

		$per_page = isset( $args['per_page'] ) ? max( 1, absint( $args['per_page'] ) ) : 20;
		$page     = isset( $args['page'] ) ? max( 1, absint( $args['page'] ) ) : 1;
		$offset   = ( $page - 1 ) * $per_page;
		$search   = isset( $args['search'] ) ? trim( (string) $args['search'] ) : '';
		$status   = isset( $args['status'] ) ? trim( (string) $args['status'] ) : '';

		$where  = 'WHERE 1=1';
		$params = array();

		if ( '' !== $search ) {
			$like   = '%' . $wpdb->esc_like( $search ) . '%';
			$where .= ' AND ( name LIKE %s OR email LIKE %s OR phone LIKE %s )';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}
		if ( '' !== $status ) {
			$where   .= ' AND status = %s';
			$params[] = $status;
		}

		$count_sql = "SELECT COUNT(*) FROM {$table} {$where}";
		$total     = $params
			? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			: (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery

		$list_params   = $params;
		$list_params[] = $per_page;
		$list_params[] = $offset;
		$list_sql      = "SELECT * FROM {$table} {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
		$items         = $wpdb->get_results( $wpdb->prepare( $list_sql, $list_params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared

		return array(
			'items' => is_array( $items ) ? $items : array(),
			'total' => $total,
		);
	}

	/* --------------------------------------------------------------------- */
	/* Generations log (requests + cost)                                      */
	/* --------------------------------------------------------------------- */

	/**
	 * Log one AI generation request.
	 *
	 * @param array $data Generation fields.
	 * @return int Inserted id.
	 */
	public static function log_generation( array $data ) {
		global $wpdb;
		$table = PMG_Activator::generations_table();

		$row = array(
			'session'           => isset( $data['session'] ) ? (string) $data['session'] : '',
			'lead_id'           => isset( $data['lead_id'] ) ? absint( $data['lead_id'] ) : null,
			'type'              => isset( $data['type'] ) ? (string) $data['type'] : 'mockup',
			'model'             => isset( $data['model'] ) ? (string) $data['model'] : '',
			'cost'              => isset( $data['cost'] ) ? (float) $data['cost'] : 0,
			'prompt_tokens'     => isset( $data['prompt_tokens'] ) ? absint( $data['prompt_tokens'] ) : 0,
			'completion_tokens' => isset( $data['completion_tokens'] ) ? absint( $data['completion_tokens'] ) : 0,
			'status'            => isset( $data['status'] ) ? (string) $data['status'] : 'success',
			'generation_id'     => isset( $data['generation_id'] ) ? (string) $data['generation_id'] : '',
			'image_url'         => isset( $data['image_url'] ) ? esc_url_raw( (string) $data['image_url'] ) : '',
			'error_message'     => isset( $data['error_message'] ) ? sanitize_textarea_field( (string) $data['error_message'] ) : '',
			'created_at'        => self::now(),
		);

		$wpdb->insert( $table, $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->insert_id;
	}

	/**
	 * Count generations for a session.
	 *
	 * @param string $session    Session token.
	 * @param string $type       Optional type filter.
	 * @param string $status     Status filter (default success).
	 * @return int
	 */
	public static function count_generations( $session, $type = '', $status = 'success' ) {
		global $wpdb;
		$table = PMG_Activator::generations_table();

		$sql    = "SELECT COUNT(*) FROM {$table} WHERE session = %s";
		$params = array( $session );
		if ( '' !== $type ) {
			$sql     .= ' AND type = %s';
			$params[] = $type;
		}
		if ( '' !== $status ) {
			$sql     .= ' AND status = %s';
			$params[] = $status;
		}
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * List all successfully generated mockup image URLs for a session, oldest first.
	 *
	 * @param string $session Session token.
	 * @return string[] Ordered list of public image URLs.
	 */
	public static function session_mockups( $session ) {
		global $wpdb;
		$table = PMG_Activator::generations_table();
		$urls  = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT image_url FROM {$table} WHERE session = %s AND type = %s AND status = %s AND image_url <> '' ORDER BY id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$session,
				'mockup',
				'success'
			)
		);
		return is_array( $urls ) ? array_values( array_filter( $urls ) ) : array();
	}

	/**
	 * Sum of cost for a session.
	 *
	 * @param string $session Session token.
	 * @return float
	 */
	public static function session_cost( $session ) {
		global $wpdb;
		$table = PMG_Activator::generations_table();
		return (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(cost),0) FROM {$table} WHERE session = %s", $session ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Most recent failed generation attempts, newest first.
	 *
	 * @param int $limit Max rows to return.
	 * @return array[] Rows with created_at, type, model, error_message.
	 */
	public static function recent_errors( $limit = 20 ) {
		global $wpdb;
		$table = PMG_Activator::generations_table();
		$limit = max( 1, absint( $limit ) );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT created_at, type, model, error_message FROM {$table} WHERE status = %s ORDER BY id DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'error',
				$limit
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Aggregate stats for the dashboard.
	 *
	 * @return array
	 */
	public static function stats() {
		global $wpdb;
		$gen   = PMG_Activator::generations_table();
		$leads = PMG_Activator::leads_table();

		$month_start = gmdate( 'Y-m-01 00:00:00', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

		$total_requests = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$gen}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		$total_cost     = (float) $wpdb->get_var( "SELECT COALESCE(SUM(cost),0) FROM {$gen}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		$mockup_req     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$gen} WHERE type = %s", 'mockup' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$cutout_req     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$gen} WHERE type = %s", 'cutout' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$mockup_cost    = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(cost),0) FROM {$gen} WHERE type = %s", 'mockup' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$cutout_cost    = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(cost),0) FROM {$gen} WHERE type = %s", 'cutout' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$errors         = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$gen} WHERE status = %s", 'error' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$month_requests = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$gen} WHERE created_at >= %s", $month_start ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$month_cost     = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(cost),0) FROM {$gen} WHERE created_at >= %s", $month_start ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$total_leads     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$leads}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		$completed_leads = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$leads} WHERE status = %s", 'completed' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array(
			'total_requests'  => $total_requests,
			'total_cost'      => $total_cost,
			'mockup_requests' => $mockup_req,
			'cutout_requests' => $cutout_req,
			'mockup_cost'     => $mockup_cost,
			'cutout_cost'     => $cutout_cost,
			'errors'          => $errors,
			'month_requests'  => $month_requests,
			'month_cost'      => $month_cost,
			'total_leads'     => $total_leads,
			'completed_leads' => $completed_leads,
		);
	}

	/* --------------------------------------------------------------------- */
	/* Modal-open click tracking                                             */
	/* --------------------------------------------------------------------- */

	/**
	 * Record a single "open modal" button click. Cheap: one INSERT plus a
	 * lifetime counter in an option, with occasional pruning to bound the table.
	 *
	 * @param string $ip      Visitor IP.
	 * @param string $session Session token (may be empty).
	 * @return void
	 */
	public static function log_open( $ip, $session ) {
		global $wpdb;
		$table = PMG_Activator::opens_table();

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$table,
			array(
				'ip'         => substr( (string) $ip, 0, 64 ),
				'session'    => substr( (string) $session, 0, 64 ),
				'created_at' => self::now(),
			),
			array( '%s', '%s', '%s' )
		);

		// Lifetime total (kept as the source of truth so the counter survives pruning).
		update_option( 'pmg_open_count', self::open_count() + 1, false );

		// Light, occasional pruning so the log never grows unbounded (~5% of inserts).
		if ( 1 === wp_rand( 1, 20 ) ) {
			$cutoff = gmdate( 'Y-m-d H:i:s', time() - 90 * DAY_IN_SECONDS );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		}
	}

	/**
	 * Lifetime number of times the open-modal button was clicked.
	 *
	 * @return int
	 */
	public static function open_count() {
		return (int) get_option( 'pmg_open_count', 0 );
	}

	/**
	 * Most recent open-modal clicks (IP + time).
	 *
	 * @param int $limit Max rows.
	 * @return array<int,array<string,mixed>>
	 */
	public static function recent_opens( $limit = 30 ) {
		global $wpdb;
		$table = PMG_Activator::opens_table();
		$limit = max( 1, (int) $limit );
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT ip, session, created_at FROM {$table} ORDER BY id DESC LIMIT %d", $limit ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		return is_array( $rows ) ? $rows : array();
	}
}

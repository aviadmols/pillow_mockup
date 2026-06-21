<?php
/**
 * Admin area: dashboard (requests + cost), registrants list, and settings.
 *
 * @package PillowMockupGenerator
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PMG_Admin
 */
class PMG_Admin {

	/**
	 * Capability required to manage the plugin.
	 */
	const CAP = 'manage_options';

	/**
	 * Models cache transient key.
	 */
	const MODELS_CACHE = 'pmg_models_cache';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	/**
	 * Register the admin menu.
	 *
	 * @return void
	 */
	public function menu() {
		add_menu_page(
			__( 'Pillow Mockup', 'pillow-mockup-generator' ),
			__( 'Pillow Mockup', 'pillow-mockup-generator' ),
			self::CAP,
			'pmg-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-format-image',
			58
		);
		add_submenu_page( 'pmg-dashboard', __( 'Dashboard', 'pillow-mockup-generator' ), __( 'Dashboard', 'pillow-mockup-generator' ), self::CAP, 'pmg-dashboard', array( $this, 'render_dashboard' ) );
		add_submenu_page( 'pmg-dashboard', __( 'Registrants', 'pillow-mockup-generator' ), __( 'Registrants', 'pillow-mockup-generator' ), self::CAP, 'pmg-registrants', array( $this, 'render_registrants' ) );
		add_submenu_page( 'pmg-dashboard', __( 'Settings', 'pillow-mockup-generator' ), __( 'Settings', 'pillow-mockup-generator' ), self::CAP, 'pmg-settings', array( $this, 'render_settings' ) );
	}

	/**
	 * Enqueue admin assets on plugin pages only.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function assets( $hook ) {
		if ( false === strpos( (string) $hook, 'pmg-' ) ) {
			return;
		}
		wp_enqueue_style( 'pmg-admin', PMG_PLUGIN_URL . 'assets/css/admin.css', array(), PMG_VERSION );
		wp_enqueue_script( 'pmg-admin', PMG_PLUGIN_URL . 'assets/js/admin.js', array(), PMG_VERSION, true );
	}

	/**
	 * Handle settings save, connection test, model refresh, and lead deletion.
	 *
	 * @return void
	 */
	public function handle_actions() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		// Save settings.
		if ( isset( $_POST['pmg_settings_submit'] ) ) {
			check_admin_referer( 'pmg_save_settings' );
			$raw = isset( $_POST['pmg'] ) ? wp_unslash( $_POST['pmg'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitized — sanitised in PMG_Settings::update().
			PMG_Settings::update( is_array( $raw ) ? $raw : array() );
			$this->redirect_with( 'pmg-settings', array( 'pmg_notice' => 'saved' ) );
		}

		// Test connection.
		if ( isset( $_POST['pmg_test_connection'] ) ) {
			check_admin_referer( 'pmg_save_settings' );
			$client = new PMG_OpenRouter( PMG_Settings::get( 'api_key' ) );
			$result = $client->test_connection();
			set_transient(
				'pmg_test_result',
				is_wp_error( $result ) ? array( 'ok' => false, 'msg' => $result->get_error_message() ) : array( 'ok' => true, 'msg' => __( 'Connection OK.', 'pillow-mockup-generator' ) ),
				60
			);
			$this->redirect_with( 'pmg-settings', array( 'pmg_notice' => 'tested' ) );
		}

		// Refresh model list.
		if ( isset( $_POST['pmg_load_models'] ) ) {
			check_admin_referer( 'pmg_save_settings' );
			$client = new PMG_OpenRouter( PMG_Settings::get( 'api_key' ) );
			$models = $client->list_models();
			if ( ! is_wp_error( $models ) ) {
				set_transient( self::MODELS_CACHE, $models, HOUR_IN_SECONDS );
				$this->redirect_with( 'pmg-settings', array( 'pmg_notice' => 'models' ) );
			}
			set_transient( 'pmg_test_result', array( 'ok' => false, 'msg' => $models->get_error_message() ), 60 );
			$this->redirect_with( 'pmg-settings', array( 'pmg_notice' => 'tested' ) );
		}

		// Delete a lead.
		if ( isset( $_GET['pmg_action'] ) && 'delete_lead' === $_GET['pmg_action'] && isset( $_GET['lead'] ) ) {
			$lead_id = absint( $_GET['lead'] );
			check_admin_referer( 'pmg_delete_lead_' . $lead_id );
			PMG_Leads::delete( $lead_id );
			$this->redirect_with( 'pmg-registrants', array( 'pmg_notice' => 'deleted' ) );
		}
	}

	/**
	 * Redirect to a plugin admin page with query args.
	 *
	 * @param string $page Page slug.
	 * @param array  $args Extra query args.
	 * @return void
	 */
	protected function redirect_with( $page, array $args = array() ) {
		$url = add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
		wp_safe_redirect( $url );
		exit;
	}

	/* --------------------------------------------------------------------- */
	/* Pages                                                                  */
	/* --------------------------------------------------------------------- */

	/**
	 * Dashboard with request counts and costs.
	 *
	 * @return void
	 */
	public function render_dashboard() {
		$stats    = PMG_Leads::stats();
		$currency = (string) PMG_Settings::get( 'currency', '$' );
		$cost     = function ( $v ) use ( $currency ) {
			return $currency . number_format( (float) $v, 4 );
		};
		?>
		<div class="wrap pmg-wrap">
			<h1><?php esc_html_e( 'Pillow Mockup — Dashboard', 'pillow-mockup-generator' ); ?></h1>

			<?php if ( ! PMG_Settings::is_configured() ) : ?>
				<div class="notice notice-warning"><p>
					<?php
					printf(
						/* translators: %s: settings link. */
						esc_html__( 'Add your OpenRouter API key in %s to start generating mockups.', 'pillow-mockup-generator' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=pmg-settings' ) ) . '">' . esc_html__( 'Settings', 'pillow-mockup-generator' ) . '</a>'
					);
					?>
				</p></div>
			<?php endif; ?>

			<div class="pmg-cards">
				<div class="pmg-card"><span class="pmg-card-label"><?php esc_html_e( 'Total requests', 'pillow-mockup-generator' ); ?></span><span class="pmg-card-value"><?php echo esc_html( number_format_i18n( $stats['total_requests'] ) ); ?></span></div>
				<div class="pmg-card"><span class="pmg-card-label"><?php esc_html_e( 'Total cost', 'pillow-mockup-generator' ); ?></span><span class="pmg-card-value"><?php echo esc_html( $cost( $stats['total_cost'] ) ); ?></span></div>
				<div class="pmg-card"><span class="pmg-card-label"><?php esc_html_e( 'Requests this month', 'pillow-mockup-generator' ); ?></span><span class="pmg-card-value"><?php echo esc_html( number_format_i18n( $stats['month_requests'] ) ); ?></span></div>
				<div class="pmg-card"><span class="pmg-card-label"><?php esc_html_e( 'Cost this month', 'pillow-mockup-generator' ); ?></span><span class="pmg-card-value"><?php echo esc_html( $cost( $stats['month_cost'] ) ); ?></span></div>
			</div>

			<h2><?php esc_html_e( 'Breakdown by stage', 'pillow-mockup-generator' ); ?></h2>
			<table class="widefat striped pmg-table">
				<thead><tr>
					<th><?php esc_html_e( 'Stage', 'pillow-mockup-generator' ); ?></th>
					<th><?php esc_html_e( 'Requests', 'pillow-mockup-generator' ); ?></th>
					<th><?php esc_html_e( 'Cost', 'pillow-mockup-generator' ); ?></th>
				</tr></thead>
				<tbody>
					<tr><td><?php esc_html_e( 'Mockups', 'pillow-mockup-generator' ); ?></td><td><?php echo esc_html( number_format_i18n( $stats['mockup_requests'] ) ); ?></td><td><?php echo esc_html( $cost( $stats['mockup_cost'] ) ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Print-ready cut-outs', 'pillow-mockup-generator' ); ?></td><td><?php echo esc_html( number_format_i18n( $stats['cutout_requests'] ) ); ?></td><td><?php echo esc_html( $cost( $stats['cutout_cost'] ) ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Failed requests', 'pillow-mockup-generator' ); ?></td><td><?php echo esc_html( number_format_i18n( $stats['errors'] ) ); ?></td><td>—</td></tr>
				</tbody>
			</table>

			<div class="pmg-cards">
				<div class="pmg-card"><span class="pmg-card-label"><?php esc_html_e( 'Total registrants', 'pillow-mockup-generator' ); ?></span><span class="pmg-card-value"><?php echo esc_html( number_format_i18n( $stats['total_leads'] ) ); ?></span></div>
				<div class="pmg-card"><span class="pmg-card-label"><?php esc_html_e( 'Completed selections', 'pillow-mockup-generator' ); ?></span><span class="pmg-card-value"><?php echo esc_html( number_format_i18n( $stats['completed_leads'] ) ); ?></span></div>
			</div>

			<p class="pmg-hint">
				<?php esc_html_e( 'Embed the widget anywhere with the shortcode:', 'pillow-mockup-generator' ); ?>
				<code>[pillow_mockup]</code>
			</p>
		</div>
		<?php
	}

	/**
	 * Registrants (leads) list + detail view.
	 *
	 * @return void
	 */
	public function render_registrants() {
		// Detail view.
		if ( isset( $_GET['lead'] ) && ! isset( $_GET['pmg_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->render_lead_detail( absint( $_GET['lead'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page = 20;
		$data     = PMG_Leads::query(
			array(
				'per_page' => $per_page,
				'page'     => $paged,
				'search'   => $search,
			)
		);
		$currency = (string) PMG_Settings::get( 'currency', '$' );
		$total_pages = (int) ceil( $data['total'] / $per_page );
		?>
		<div class="wrap pmg-wrap">
			<h1><?php esc_html_e( 'Registrants', 'pillow-mockup-generator' ); ?></h1>

			<?php $this->maybe_notice(); ?>

			<form method="get" class="pmg-search">
				<input type="hidden" name="page" value="pmg-registrants" />
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search name, email or phone', 'pillow-mockup-generator' ); ?>" />
				<button class="button"><?php esc_html_e( 'Search', 'pillow-mockup-generator' ); ?></button>
			</form>

			<table class="widefat striped pmg-table">
				<thead><tr>
					<th><?php esc_html_e( 'Date', 'pillow-mockup-generator' ); ?></th>
					<th><?php esc_html_e( 'Name', 'pillow-mockup-generator' ); ?></th>
					<th><?php esc_html_e( 'Email', 'pillow-mockup-generator' ); ?></th>
					<th><?php esc_html_e( 'Phone', 'pillow-mockup-generator' ); ?></th>
					<th><?php esc_html_e( 'Status', 'pillow-mockup-generator' ); ?></th>
					<th><?php esc_html_e( 'Tries', 'pillow-mockup-generator' ); ?></th>
					<th><?php esc_html_e( 'Cost', 'pillow-mockup-generator' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'pillow-mockup-generator' ); ?></th>
				</tr></thead>
				<tbody>
				<?php if ( empty( $data['items'] ) ) : ?>
					<tr><td colspan="8"><?php esc_html_e( 'No registrants yet.', 'pillow-mockup-generator' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $data['items'] as $lead ) : ?>
						<?php
						$view_url   = add_query_arg( array( 'page' => 'pmg-registrants', 'lead' => $lead['id'] ), admin_url( 'admin.php' ) );
						$delete_url = wp_nonce_url(
							add_query_arg( array( 'page' => 'pmg-registrants', 'pmg_action' => 'delete_lead', 'lead' => $lead['id'] ), admin_url( 'admin.php' ) ),
							'pmg_delete_lead_' . $lead['id']
						);
						?>
						<tr>
							<td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' H:i', $lead['created_at'] ) ); ?></td>
							<td><a href="<?php echo esc_url( $view_url ); ?>"><strong><?php echo esc_html( $lead['name'] ); ?></strong></a></td>
							<td><a href="mailto:<?php echo esc_attr( $lead['email'] ); ?>"><?php echo esc_html( $lead['email'] ); ?></a></td>
							<td><?php echo esc_html( $lead['phone'] ); ?></td>
							<td><span class="pmg-status pmg-status-<?php echo esc_attr( $lead['status'] ); ?>"><?php echo esc_html( $lead['status'] ); ?></span></td>
							<td><?php echo esc_html( $lead['attempts'] ); ?></td>
							<td><?php echo esc_html( $currency . number_format( (float) $lead['total_cost'], 4 ) ); ?></td>
							<td>
								<a class="button button-small" href="<?php echo esc_url( $view_url ); ?>"><?php esc_html_e( 'View', 'pillow-mockup-generator' ); ?></a>
								<a class="button button-small pmg-delete" href="<?php echo esc_url( $delete_url ); ?>" data-pmg-confirm="<?php echo esc_attr__( 'Delete this registrant and its files?', 'pillow-mockup-generator' ); ?>"><?php esc_html_e( 'Delete', 'pillow-mockup-generator' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav"><div class="tablenav-pages">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'current'   => $paged,
								'total'     => $total_pages,
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
							)
						)
					);
					?>
				</div></div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Single registrant detail page (shows the three saved images).
	 *
	 * @param int $id Lead id.
	 * @return void
	 */
	protected function render_lead_detail( $id ) {
		$lead = PMG_Leads::get( $id );
		$back = admin_url( 'admin.php?page=pmg-registrants' );
		if ( ! $lead ) {
			echo '<div class="wrap"><p>' . esc_html__( 'Registrant not found.', 'pillow-mockup-generator' ) . '</p><a href="' . esc_url( $back ) . '">&larr; ' . esc_html__( 'Back', 'pillow-mockup-generator' ) . '</a></div>';
			return;
		}
		$currency = (string) PMG_Settings::get( 'currency', '$' );
		$images   = array(
			__( 'Original photo', 'pillow-mockup-generator' )       => $lead['original_image'],
			__( 'Selected mockup', 'pillow-mockup-generator' )      => $lead['mockup_image'],
			__( 'Print-ready cut-out', 'pillow-mockup-generator' )  => $lead['cutout_image'],
		);
		?>
		<div class="wrap pmg-wrap">
			<h1><?php echo esc_html( $lead['name'] ? $lead['name'] : $lead['email'] ); ?></h1>
			<p><a href="<?php echo esc_url( $back ); ?>">&larr; <?php esc_html_e( 'Back to registrants', 'pillow-mockup-generator' ); ?></a></p>

			<table class="widefat pmg-detail-table">
				<tr><th><?php esc_html_e( 'Name', 'pillow-mockup-generator' ); ?></th><td><?php echo esc_html( $lead['name'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Email', 'pillow-mockup-generator' ); ?></th><td><a href="mailto:<?php echo esc_attr( $lead['email'] ); ?>"><?php echo esc_html( $lead['email'] ); ?></a></td></tr>
				<tr><th><?php esc_html_e( 'Phone', 'pillow-mockup-generator' ); ?></th><td><?php echo esc_html( $lead['phone'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Status', 'pillow-mockup-generator' ); ?></th><td><?php echo esc_html( $lead['status'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Tries', 'pillow-mockup-generator' ); ?></th><td><?php echo esc_html( $lead['attempts'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'AI cost', 'pillow-mockup-generator' ); ?></th><td><?php echo esc_html( $currency . number_format( (float) $lead['total_cost'], 4 ) ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Created', 'pillow-mockup-generator' ); ?></th><td><?php echo esc_html( $lead['created_at'] ); ?></td></tr>
			</table>

			<h2><?php esc_html_e( 'Saved files', 'pillow-mockup-generator' ); ?></h2>
			<div class="pmg-images">
				<?php foreach ( $images as $caption => $url ) : ?>
					<div class="pmg-image">
						<span class="pmg-image-caption"><?php echo esc_html( $caption ); ?></span>
						<?php if ( $url ) : ?>
							<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url( $url ); ?>" alt="" /></a>
							<a class="button button-small" href="<?php echo esc_url( $url ); ?>" download><?php esc_html_e( 'Download', 'pillow-mockup-generator' ); ?></a>
						<?php else : ?>
							<span class="pmg-image-missing"><?php esc_html_e( 'Not generated', 'pillow-mockup-generator' ); ?></span>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Settings page.
	 *
	 * @return void
	 */
	public function render_settings() {
		$s        = PMG_Settings::all();
		$models   = get_transient( self::MODELS_CACHE );
		$models   = is_array( $models ) ? $models : array();
		?>
		<div class="wrap pmg-wrap">
			<h1><?php esc_html_e( 'Pillow Mockup — Settings', 'pillow-mockup-generator' ); ?></h1>
			<?php $this->maybe_notice(); ?>

			<?php if ( $models ) : ?>
				<datalist id="pmg-models-list">
					<?php foreach ( $models as $mid => $mname ) : ?>
						<option value="<?php echo esc_attr( $mid ); ?>"><?php echo esc_html( $mname ); ?></option>
					<?php endforeach; ?>
				</datalist>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=pmg-settings' ) ); ?>">
				<?php wp_nonce_field( 'pmg_save_settings' ); ?>

				<h2><?php esc_html_e( 'OpenRouter', 'pillow-mockup-generator' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="pmg_api_key"><?php esc_html_e( 'API key', 'pillow-mockup-generator' ); ?></label></th>
						<td>
							<input type="password" autocomplete="off" id="pmg_api_key" name="pmg[api_key]" value="<?php echo esc_attr( $s['api_key'] ); ?>" class="regular-text" />
							<button type="submit" name="pmg_test_connection" value="1" class="button"><?php esc_html_e( 'Test connection', 'pillow-mockup-generator' ); ?></button>
							<button type="submit" name="pmg_load_models" value="1" class="button"><?php esc_html_e( 'Load models', 'pillow-mockup-generator' ); ?></button>
							<p class="description"><?php esc_html_e( 'Get a key at openrouter.ai. It is stored in your database.', 'pillow-mockup-generator' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="pmg_model"><?php esc_html_e( 'Mockup model', 'pillow-mockup-generator' ); ?></label></th>
						<td>
							<?php $this->model_select( 'model', $s['model'], false, '' ); ?>
							<p class="description"><?php esc_html_e( 'Choose a Nano Banana model, or pick "Other" to enter any OpenRouter image model.', 'pillow-mockup-generator' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="pmg_cutout_model"><?php esc_html_e( 'Cut-out model', 'pillow-mockup-generator' ); ?></label></th>
						<td>
							<?php $this->model_select( 'cutout_model', $s['cutout_model'], true, __( 'Same as mockup model', 'pillow-mockup-generator' ) ); ?>
							<p class="description"><?php esc_html_e( 'Leave as "Same as mockup model" to reuse the mockup model.', 'pillow-mockup-generator' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Flow', 'pillow-mockup-generator' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="pmg_max_attempts"><?php esc_html_e( 'Extra tries after details', 'pillow-mockup-generator' ); ?></label></th>
						<td>
							<input type="number" min="1" id="pmg_max_attempts" name="pmg[max_attempts]" value="<?php echo esc_attr( $s['max_attempts'] ); ?>" class="small-text" />
							<p class="description"><?php esc_html_e( 'The first mockup is free; this is how many extra tries a visitor gets after entering their details.', 'pillow-mockup-generator' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Print-ready cut-out', 'pillow-mockup-generator' ); ?></th>
						<td>
							<label><input type="checkbox" name="pmg[enable_cutout]" value="1" <?php checked( 1, (int) $s['enable_cutout'] ); ?> /> <?php esc_html_e( 'Generate the print-ready cut-out when a visitor finalizes their choice.', 'pillow-mockup-generator' ); ?></label>
						</td>
					</tr>
					<tr>
						<th><label for="pmg_max_upload_px"><?php esc_html_e( 'Max upload size (px)', 'pillow-mockup-generator' ); ?></label></th>
						<td><input type="number" min="256" id="pmg_max_upload_px" name="pmg[max_upload_px]" value="<?php echo esc_attr( $s['max_upload_px'] ); ?>" class="small-text" />
							<p class="description"><?php esc_html_e( 'Photos are downscaled on the visitor side to this longest edge before upload.', 'pillow-mockup-generator' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Prompts', 'pillow-mockup-generator' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="pmg_mockup_prompt"><?php esc_html_e( 'Mockup prompt', 'pillow-mockup-generator' ); ?></label></th>
						<td><textarea id="pmg_mockup_prompt" name="pmg[mockup_prompt]" rows="5" class="large-text"><?php echo esc_textarea( $s['mockup_prompt'] ); ?></textarea></td>
					</tr>
					<tr>
						<th><label for="pmg_cutout_prompt"><?php esc_html_e( 'Cut-out prompt', 'pillow-mockup-generator' ); ?></label></th>
						<td><textarea id="pmg_cutout_prompt" name="pmg[cutout_prompt]" rows="5" class="large-text"><?php echo esc_textarea( $s['cutout_prompt'] ); ?></textarea></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Notifications', 'pillow-mockup-generator' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="pmg_admin_email"><?php esc_html_e( 'Admin email', 'pillow-mockup-generator' ); ?></label></th>
						<td><input type="email" id="pmg_admin_email" name="pmg[admin_email]" value="<?php echo esc_attr( $s['admin_email'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="pmg_admin_subject"><?php esc_html_e( 'Admin email subject', 'pillow-mockup-generator' ); ?></label></th>
						<td><input type="text" id="pmg_admin_subject" name="pmg[admin_subject]" value="<?php echo esc_attr( $s['admin_subject'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="pmg_customer_subject"><?php esc_html_e( 'Customer email subject', 'pillow-mockup-generator' ); ?></label></th>
						<td><input type="text" id="pmg_customer_subject" name="pmg[customer_subject]" value="<?php echo esc_attr( $s['customer_subject'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="pmg_customer_message"><?php esc_html_e( 'Customer email message', 'pillow-mockup-generator' ); ?></label></th>
						<td><textarea id="pmg_customer_message" name="pmg[customer_message]" rows="4" class="large-text"><?php echo esc_textarea( $s['customer_message'] ); ?></textarea></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Appearance & texts', 'pillow-mockup-generator' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="pmg_currency"><?php esc_html_e( 'Cost currency symbol', 'pillow-mockup-generator' ); ?></label></th>
						<td><input type="text" id="pmg_currency" name="pmg[currency]" value="<?php echo esc_attr( $s['currency'] ); ?>" class="small-text" /></td>
					</tr>
					<tr>
						<th><label for="pmg_lottie_url"><?php esc_html_e( 'Lottie loader URL', 'pillow-mockup-generator' ); ?></label></th>
						<td><input type="url" id="pmg_lottie_url" name="pmg[lottie_url]" value="<?php echo esc_attr( $s['lottie_url'] ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'A .json Lottie animation URL. Leave empty to use the bundled loader.', 'pillow-mockup-generator' ); ?></p>
						</td>
					</tr>
					<?php
					$text_fields = array(
						'text_heading'       => __( 'Heading', 'pillow-mockup-generator' ),
						'text_subheading'    => __( 'Sub-heading', 'pillow-mockup-generator' ),
						'text_upload'        => __( 'Upload button', 'pillow-mockup-generator' ),
						'text_generating'    => __( 'Generating text', 'pillow-mockup-generator' ),
						'text_try_again'     => __( 'Try-again label', 'pillow-mockup-generator' ),
						'text_change_photo'  => __( 'Change-photo label', 'pillow-mockup-generator' ),
						'text_continue'      => __( '"I love it" label', 'pillow-mockup-generator' ),
						'text_finalize'      => __( 'Finalize label', 'pillow-mockup-generator' ),
						'text_details_title' => __( 'Details form title', 'pillow-mockup-generator' ),
						'text_name'          => __( 'Name field label', 'pillow-mockup-generator' ),
						'text_phone'         => __( 'Phone field label', 'pillow-mockup-generator' ),
						'text_email'         => __( 'Email field label', 'pillow-mockup-generator' ),
						'text_submit'        => __( 'Details submit label', 'pillow-mockup-generator' ),
						'text_attempts_left' => __( '"attempts left" label', 'pillow-mockup-generator' ),
						'text_max_reached'   => __( 'Max-tries message', 'pillow-mockup-generator' ),
						'text_done_title'    => __( 'Thank-you title', 'pillow-mockup-generator' ),
						'text_done_message'  => __( 'Thank-you message', 'pillow-mockup-generator' ),
					);
					foreach ( $text_fields as $key => $label ) :
						?>
						<tr>
							<th><label for="pmg_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
							<td><input type="text" id="pmg_<?php echo esc_attr( $key ); ?>" name="pmg[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $s[ $key ] ); ?>" class="large-text" /></td>
						</tr>
					<?php endforeach; ?>
				</table>

				<p class="submit">
					<button type="submit" name="pmg_settings_submit" value="1" class="button button-primary"><?php esc_html_e( 'Save settings', 'pillow-mockup-generator' ); ?></button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Render a model picker: a select of Nano Banana models plus an "Other" custom field.
	 *
	 * @param string $key           Settings key (model|cutout_model).
	 * @param string $current       Currently saved value.
	 * @param bool   $include_empty Whether to offer an empty "same as" option.
	 * @param string $empty_label   Label for the empty option.
	 * @return void
	 */
	protected function model_select( $key, $current, $include_empty, $empty_label ) {
		$models    = PMG_Settings::nano_banana_models();
		$current   = (string) $current;
		$is_known  = isset( $models[ $current ] ) || ( '' === $current && $include_empty );
		$is_custom = '' !== $current && ! isset( $models[ $current ] );
		$field_id  = 'pmg_' . $key;
		?>
		<select id="<?php echo esc_attr( $field_id ); ?>" name="pmg[<?php echo esc_attr( $key ); ?>]" class="regular-text" data-pmg-model-select="<?php echo esc_attr( $key ); ?>">
			<?php if ( $include_empty ) : ?>
				<option value="" <?php selected( '' === $current ); ?>><?php echo esc_html( $empty_label ); ?></option>
			<?php endif; ?>
			<?php foreach ( $models as $mid => $mname ) : ?>
				<option value="<?php echo esc_attr( $mid ); ?>" <?php selected( $current, $mid ); ?>><?php echo esc_html( $mname ); ?></option>
			<?php endforeach; ?>
			<option value="__custom__" <?php selected( $is_custom ); ?>><?php esc_html_e( 'Other / custom model…', 'pillow-mockup-generator' ); ?></option>
		</select>
		<input
			type="text"
			id="<?php echo esc_attr( $field_id ); ?>_custom"
			name="pmg[<?php echo esc_attr( $key ); ?>_custom]"
			value="<?php echo esc_attr( $is_custom ? $current : '' ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'e.g. google/gemini-2.5-flash-image', 'pillow-mockup-generator' ); ?>"
			list="pmg-models-list"
			data-pmg-model-custom="<?php echo esc_attr( $key ); ?>"
			<?php echo $is_custom ? '' : 'hidden'; ?>
		/>
		<?php
	}

	/**
	 * Render admin notices based on query args / transients.
	 *
	 * @return void
	 */
	protected function maybe_notice() {
		$notice = isset( $_GET['pmg_notice'] ) ? sanitize_key( wp_unslash( $_GET['pmg_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $notice ) {
			return;
		}
		if ( 'saved' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'pillow-mockup-generator' ) . '</p></div>';
		} elseif ( 'deleted' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Registrant deleted.', 'pillow-mockup-generator' ) . '</p></div>';
		} elseif ( 'models' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Model list updated.', 'pillow-mockup-generator' ) . '</p></div>';
		} elseif ( 'tested' === $notice ) {
			$result = get_transient( 'pmg_test_result' );
			delete_transient( 'pmg_test_result' );
			if ( is_array( $result ) ) {
				$class = ! empty( $result['ok'] ) ? 'notice-success' : 'notice-error';
				echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( (string) $result['msg'] ) . '</p></div>';
			}
		}
	}
}

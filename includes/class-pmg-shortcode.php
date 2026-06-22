<?php
/**
 * Front-end shortcode and asset loading for the mockup widget.
 *
 * @package PillowMockupGenerator
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PMG_Shortcode
 */
class PMG_Shortcode {

	/**
	 * Whether assets were already registered/enqueued this request.
	 *
	 * @var bool
	 */
	protected $enqueued = false;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'pillow_mockup', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register (but do not enqueue) front-end assets.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style(
			'pmg-frontend',
			PMG_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			PMG_VERSION
		);

		// Lottie runtime (only loaded when the widget is present).
		wp_register_script(
			'pmg-lottie',
			'https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js',
			array(),
			'5.12.2',
			true
		);

		wp_register_script(
			'pmg-frontend',
			PMG_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'pmg-lottie' ),
			PMG_VERSION,
			true
		);
	}

	/**
	 * Enqueue assets and localise config.
	 *
	 * @return void
	 */
	protected function enqueue() {
		if ( $this->enqueued ) {
			return;
		}
		$this->enqueued = true;

		wp_enqueue_style( 'pmg-frontend' );
		wp_enqueue_script( 'pmg-lottie' );
		wp_enqueue_script( 'pmg-frontend' );

		$lottie_url = (string) PMG_Settings::get( 'lottie_url', '' );
		if ( '' === $lottie_url ) {
			$lottie_url = PMG_PLUGIN_URL . 'assets/lottie/loader.json';
		}

		wp_localize_script(
			'pmg-frontend',
			'PMG_CONFIG',
			array(
				'restUrl'   => esc_url_raw( rest_url( PMG_REST_NAMESPACE . '/' ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'nonceUrl'  => esc_url_raw( rest_url( PMG_REST_NAMESPACE . '/nonce' ) ),
				'lottieUrl'     => esc_url_raw( $lottie_url ),
				'maxPx'         => (int) PMG_Settings::get( 'max_upload_px', 1280 ),
				'attemptsLabel' => (string) PMG_Settings::get( 'text_attempts_left', 'attempts left' ),
				'maxMockups'    => (int) PMG_Settings::get( 'max_mockups', 3 ),
				'priceCurrency' => (string) PMG_Settings::get( 'price_currency', '₪' ),
				'i18n'      => array(
					'genericError'   => __( 'אירעה תקלה. נסו שנית.', 'pillow-mockup-generator' ),
					'generateFailed' => __( 'יצירת המוקאפ נכשלה. נסו שנית.', 'pillow-mockup-generator' ),
					'invalidFile'    => __( 'בחרו קובץ תמונה (JPG, PNG או WEBP).', 'pillow-mockup-generator' ),
					'maxReached'     => __( 'הגעתם למספר התמונות המרבי.', 'pillow-mockup-generator' ),
					'cmUnit'         => __( 'ס"מ', 'pillow-mockup-generator' ),
				),
			)
		);
	}

	/**
	 * Render the widget.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$this->enqueue();

		$atts = shortcode_atts(
			array(
				'class' => '',
			),
			$atts,
			'pillow_mockup'
		);

		$settings = PMG_Settings::all();

		ob_start();
		include PMG_PLUGIN_DIR . 'templates/widget.php';
		return (string) ob_get_clean();
	}
}

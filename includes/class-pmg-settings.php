<?php
/**
 * Settings helper: reads, sanitizes and stores plugin options.
 *
 * @package PillowMockupGenerator
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PMG_Settings
 */
class PMG_Settings {

	/**
	 * Cached settings.
	 *
	 * @var array|null
	 */
	protected static $cache = null;

	/**
	 * Default mockup prompt (the on-the-sofa product render).
	 *
	 * @return string
	 */
	public static function default_mockup_prompt() {
		return 'A high-quality custom-shaped product mockup of a personalized pillow sitting on a modern living room sofa. The pillow is precisely die-cut into the unique outline shape of the main subjects from the reference image, featuring a clean, thick white fabric border framing their figures. The exact figures from the original photo are printed sharply and clearly on the front of the pillow, seamlessly adapting to the soft texture and natural fabric folds of the cushion. Clean composition, realistic shadows, and warm studio lighting.';
	}

	/**
	 * List of US states for the address form dropdown.
	 *
	 * @return string[]
	 */
	public static function us_states() {
		return array(
			'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado',
			'Connecticut', 'Delaware', 'District of Columbia', 'Florida', 'Georgia',
			'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky',
			'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota',
			'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire',
			'New Jersey', 'New Mexico', 'New York', 'North Carolina', 'North Dakota',
			'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island',
			'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont',
			'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming',
		);
	}

	/**
	 * Default print-ready cut-out prompt (transparent die-cut shape).
	 *
	 * @return string
	 */
	public static function default_cutout_prompt() {
		return 'Using only the main subjects from the reference image, produce a print-ready, custom die-cut pillow shape. Precisely cut around the subjects\' combined outline and add a clean, thick, even white fabric border that frames their figures. The subjects must be printed sharply and clearly. Output the cut-out shape centered on a fully transparent background (PNG with alpha), with crisp manufacturing-ready edges. No scene, no sofa, no extra background, no shadows outside the shape.';
	}

	/**
	 * Curated list of Google "Nano Banana" image models (OpenRouter slugs).
	 *
	 * @return array<string,string> Map of model id => human label.
	 */
	public static function nano_banana_models() {
		return array(
			'google/gemini-2.5-flash-image'         => 'Nano Banana (Gemini 2.5 Flash Image)',
			'google/gemini-2.5-flash-image-preview' => 'Nano Banana (Gemini 2.5 Flash Image Preview)',
			'google/gemini-3.1-flash-image-preview' => 'Nano Banana 2 (Gemini 3.1 Flash Image)',
			'google/gemini-3-pro-image'             => 'Nano Banana Pro (Gemini 3 Pro Image)',
		);
	}

	/**
	 * The three customer-facing size/price tiers, normalised for the front-end
	 * and for order validation.
	 *
	 * @return array<int,array{id:string,label:string,cm:string,price:float,compare:float}>
	 */
	public static function sizes() {
		$tiers = array();
		foreach ( array( 'small', 'medium', 'large' ) as $id ) {
			$label = (string) self::get( 'size_' . $id . '_label', '' );
			$price = (float) self::get( 'size_' . $id . '_price', 0 );
			if ( '' === $label || $price <= 0 ) {
				continue;
			}
			$tiers[] = array(
				'id'      => $id,
				'label'   => $label,
				'cm'      => (string) self::get( 'size_' . $id . '_cm', '' ),
				'price'   => $price,
				'compare' => (float) self::get( 'size_' . $id . '_compare', 0 ),
			);
		}
		return $tiers;
	}

	/**
	 * Look up a single size tier by id.
	 *
	 * @param string $id Size id (small|medium|large).
	 * @return array|null
	 */
	public static function size( $id ) {
		foreach ( self::sizes() as $tier ) {
			if ( $tier['id'] === $id ) {
				return $tier;
			}
		}
		return null;
	}

	/**
	 * Return all default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'api_key'           => '',
			'model'             => 'google/gemini-2.5-flash-image',
			'cutout_model'      => '',
			'max_attempts'      => 5,
			'max_mockups'       => 3,
			'enable_cutout'     => 1,
			'admin_email'       => get_option( 'admin_email' ),
			'currency'          => '$',
			'price_currency'    => '$',
			'font_family'       => '',
			'lottie_url'        => '',
			// Facebook Pixel (purchase tracking on order completion).
			'fb_pixel_id'       => '',
			'fb_currency'       => 'USD',
			// Sizes & pricing (customer facing).
			'size_small_label'    => 'Small',
			'size_small_cm'       => '10"×10"',
			'size_small_price'    => '39.99',
			'size_small_compare'  => '49.99',
			'size_medium_label'   => 'Medium',
			'size_medium_cm'      => '16"×16"',
			'size_medium_price'   => '69.99',
			'size_medium_compare' => '79.99',
			'size_large_label'    => 'Large',
			'size_large_cm'       => '22"×22"',
			'size_large_price'    => '99.99',
			'size_large_compare'  => '119.99',
			'text_size_title'     => 'Choose your size',
			'max_upload_px'     => 1280,
			'site_title'        => get_bloginfo( 'name' ),
			'mockup_prompt'     => self::default_mockup_prompt(),
			'cutout_prompt'     => self::default_cutout_prompt(),
			'admin_subject'     => 'New pillow mockup lead',
			'customer_subject'  => 'We received your request',
			'customer_message'  => "Thank you! We received your request and saved your custom pillow design.\nWe will contact you shortly to complete the process.",
			// Front-end texts (customer facing, fully editable so the store can localise).
			'text_heading'      => 'צרו את הכרית המותאמת שלכם',
			'text_subheading'   => 'העלו תמונה וראו אותה ככרית בגזירה מותאמת, אחת ויחידה.',
			'text_upload'       => 'העלאת תמונה',
			'text_uploading'    => 'מכינים את העיצוב שלכם…',
			'text_generating'   => 'יוצרים את הדמיית הכרית שלכם…',
			'text_try_again'    => 'תוצאה נוספת',
			'text_change_photo' => 'החלפת תמונה',
			'text_continue'     => 'אהבתי!',
			'text_finalize'     => 'בחרו במוצר הזה',
			'text_details_title'=> 'עוד רגע — הפרטים שלכם',
			'text_details_subtitle' => 'שמרו את העיצוב ונשלח לכם אותו ישירות — זה לוקח רק כמה שניות.',
			'text_privacy_note' => 'הפרטים שלכם שמורים אצלנו — בלי ספאם, אף פעם.',
			'text_name'         => 'שם מלא',
			'text_first_name'   => 'שם פרטי',
			'text_last_name'    => 'שם משפחה',
			'text_phone'        => 'טלפון',
			'text_email'        => 'אימייל',
			'text_address'      => 'כתובת',
			'text_city'         => 'עיר',
			'text_submit'       => 'המשך',
			'text_attempts_left'=> 'ניסיונות נותרו',
			'text_max_reached'  => 'הגעתם למספר הניסיונות המרבי. בחרו את התוצאה האהובה עליכם כדי להמשיך.',
			'text_done_title'   => 'תודה רבה!',
			'text_done_message' => 'קיבלנו את הבקשה שלכם וניצור איתכם קשר בקרוב להשלמת התהליך.',
		);
	}

	/**
	 * The original English front-end texts (pre-Hebrew). Used to detect
	 * untouched values so an existing install can be migrated to Hebrew
	 * without overwriting any texts a store owner already customised.
	 *
	 * @return array<string,string>
	 */
	public static function legacy_english_texts() {
		return array(
			'text_heading'          => 'Create your custom pillow',
			'text_subheading'       => 'Upload a photo and see it as a one-of-a-kind die-cut pillow.',
			'text_upload'           => 'Upload a photo',
			'text_uploading'        => 'Preparing your design…',
			'text_generating'       => 'Crafting your pillow mockup…',
			'text_try_again'        => 'Try another result',
			'text_change_photo'     => 'Change photo',
			'text_continue'         => 'I love it',
			'text_finalize'         => 'Choose this product',
			'text_details_title'    => 'Almost there — your details',
			'text_details_subtitle' => 'Save your design and we will send it straight to you — it only takes a few seconds.',
			'text_privacy_note'     => 'Your details are safe with us — no spam, ever.',
			'text_name'             => 'Full name',
			'text_phone'            => 'Phone',
			'text_email'            => 'Email',
			'text_submit'           => 'Continue',
			'text_attempts_left'    => 'attempts left',
			'text_max_reached'      => 'You have reached the maximum number of tries. Choose your favourite result to continue.',
			'text_done_title'       => 'Thank you!',
			'text_done_message'     => 'We received your request and will contact you shortly to complete the process.',
		);
	}

	/**
	 * One-time migration: switch the front-end texts to the new Hebrew defaults
	 * for any value that is still the original English default (or missing).
	 * Customised texts are preserved. Runs once, guarded by an option flag.
	 *
	 * @return void
	 */
	public static function maybe_migrate_texts() {
		if ( get_option( 'pmg_texts_he_migrated' ) ) {
			return;
		}

		$stored = get_option( PMG_OPTION_KEY, array() );
		$stored = is_array( $stored ) ? $stored : array();
		$legacy = self::legacy_english_texts();
		$new    = self::defaults();

		foreach ( $legacy as $key => $english ) {
			if ( ! isset( $stored[ $key ] ) || '' === $stored[ $key ] || $stored[ $key ] === $english ) {
				$stored[ $key ] = $new[ $key ];
			}
		}

		update_option( PMG_OPTION_KEY, $stored );
		update_option( 'pmg_texts_he_migrated', 1 );
		self::$cache = null;
	}

	/**
	 * One-time migration for the on1y.one English/LTR redesign: force the size
	 * tiers, currency symbol and size-step title onto the new USD defaults so
	 * existing installs (which stored Hebrew labels and ₪) reflect the new
	 * English pricing immediately. Runs once, guarded by an option flag.
	 *
	 * @return void
	 */
	public static function maybe_migrate_sizes_en() {
		if ( get_option( 'pmg_sizes_en_migrated' ) ) {
			return;
		}

		$stored = get_option( PMG_OPTION_KEY, array() );
		$stored = is_array( $stored ) ? $stored : array();
		$new    = self::defaults();

		$force_keys = array(
			'currency',
			'price_currency',
			'fb_currency',
			'size_small_label',
			'size_small_cm',
			'size_small_price',
			'size_small_compare',
			'size_medium_label',
			'size_medium_cm',
			'size_medium_price',
			'size_medium_compare',
			'size_large_label',
			'size_large_cm',
			'size_large_price',
			'size_large_compare',
			'text_size_title',
		);

		foreach ( $force_keys as $key ) {
			$stored[ $key ] = $new[ $key ];
		}

		update_option( PMG_OPTION_KEY, $stored );
		update_option( 'pmg_sizes_en_migrated', 1 );
		self::$cache = null;
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array
	 */
	public static function all() {
		if ( null === self::$cache ) {
			$stored      = get_option( PMG_OPTION_KEY, array() );
			$stored      = is_array( $stored ) ? $stored : array();
			self::$cache = wp_parse_args( $stored, self::defaults() );
		}
		return self::$cache;
	}

	/**
	 * Get a single setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	public static function get( $key, $default = '' ) {
		$all = self::all();
		if ( array_key_exists( $key, $all ) && '' !== $all[ $key ] && null !== $all[ $key ] ) {
			return $all[ $key ];
		}
		return $default;
	}

	/**
	 * Persist settings after sanitising.
	 *
	 * @param array $input Raw input.
	 * @return array Sanitised settings.
	 */
	public static function update( array $input ) {
		$defaults = self::defaults();
		$current  = self::all();
		$clean    = array();

		// Resolve the "Other / custom model" choice into the actual model slug.
		foreach ( array( 'model', 'cutout_model' ) as $model_key ) {
			if ( isset( $input[ $model_key ] ) && '__custom__' === $input[ $model_key ] ) {
				$custom_key        = $model_key . '_custom';
				$input[ $model_key ] = isset( $input[ $custom_key ] ) ? $input[ $custom_key ] : '';
			}
		}

		foreach ( $defaults as $key => $default ) {
			$value = isset( $input[ $key ] ) ? $input[ $key ] : ( isset( $current[ $key ] ) ? $current[ $key ] : $default );

			switch ( $key ) {
				case 'api_key':
					$clean[ $key ] = sanitize_text_field( $value );
					break;
				case 'max_attempts':
					$clean[ $key ] = max( 1, absint( $value ) );
					break;
				case 'max_mockups':
					$clean[ $key ] = max( 1, absint( $value ) );
					break;
				case 'size_small_price':
				case 'size_small_compare':
				case 'size_medium_price':
				case 'size_medium_compare':
				case 'size_large_price':
				case 'size_large_compare':
					$clean[ $key ] = (string) max( 0, (float) $value );
					break;
				case 'max_upload_px':
					$clean[ $key ] = max( 256, absint( $value ) );
					break;
				case 'enable_cutout':
					// Read $input directly so an unchecked box becomes 0.
					$clean[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
					break;
				case 'admin_email':
					$clean[ $key ] = sanitize_email( $value );
					break;
				case 'lottie_url':
					$clean[ $key ] = esc_url_raw( trim( (string) $value ) );
					break;
				case 'mockup_prompt':
				case 'cutout_prompt':
				case 'customer_message':
				case 'text_done_message':
					$clean[ $key ] = sanitize_textarea_field( $value );
					break;
				default:
					$clean[ $key ] = sanitize_text_field( $value );
					break;
			}
		}

		update_option( PMG_OPTION_KEY, $clean );
		self::$cache = null;
		return $clean;
	}

	/**
	 * Whether the API key is configured.
	 *
	 * @return bool
	 */
	public static function is_configured() {
		return '' !== trim( (string) self::get( 'api_key' ) );
	}
}

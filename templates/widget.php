<?php
/**
 * Front-end widget markup.
 *
 * @package PillowMockupGenerator
 *
 * @var array $atts     Shortcode attributes.
 * @var array $settings Plugin settings (from PMG_Settings::all()).
 */

defined( 'ABSPATH' ) || exit;

$pmg_extra_class = isset( $atts['class'] ) ? sanitize_html_class( $atts['class'] ) : '';
$pmg_font_family = isset( $settings['font_family'] ) ? trim( (string) $settings['font_family'] ) : '';
$pmg_root_style  = '' !== $pmg_font_family ? 'font-family:' . $pmg_font_family . ';' : '';
?>
<div class="pmg pmg--rtl pmg--loading <?php echo esc_attr( $pmg_extra_class ); ?>" data-pmg data-state="upload" dir="rtl"<?php echo '' !== $pmg_root_style ? ' style="' . esc_attr( $pmg_root_style ) . '"' : ''; ?>>

	<div class="pmg__boot" aria-hidden="true">
		<span class="pmg__boot-spinner"></span>
	</div>

	<header class="pmg__head">
		<h2 class="pmg__title"><?php echo esc_html( $settings['text_heading'] ); ?></h2>
		<p class="pmg__subtitle"><?php echo esc_html( $settings['text_subheading'] ); ?></p>
	</header>

	<div class="pmg__stage">

		<!-- Upload (Mixtiles-style empty frame + add button) -->
		<div class="pmg__panel pmg__panel--upload" data-panel="upload">
			<input type="file" class="pmg__file" accept="image/png,image/jpeg,image/webp" data-pmg-file hidden />
			<div class="pmg__placeholder">
				<div class="pmg__placeholder-frame" data-pmg-dropzone data-pmg-open-modal role="button" tabindex="0" aria-label="<?php echo esc_attr( $settings['text_upload'] ); ?>">
					<span class="pmg__placeholder-icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-4.5-4.5L5 21"/></svg>
					</span>
				</div>
				<button type="button" class="pmg__add" data-pmg-open-modal aria-label="<?php echo esc_attr( $settings['text_upload'] ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
				</button>
				<p class="pmg__placeholder-hint"><?php echo esc_html( $settings['text_upload'] ); ?></p>
			</div>
			<p class="pmg__notice" data-pmg-upload-notice hidden></p>
		</div>

		<!-- Loading -->
		<div class="pmg__panel pmg__panel--loading" data-panel="loading">
			<div class="pmg__lottie" data-pmg-lottie aria-hidden="true"></div>
			<div class="pmg__spinner" aria-hidden="true"></div>
			<p class="pmg__loading-text" data-pmg-loading-text><?php echo esc_html( $settings['text_generating'] ); ?></p>
		</div>

		<!-- Preview -->
		<div class="pmg__panel pmg__panel--preview" data-panel="preview">
			<figure class="pmg__frame">
				<img class="pmg__result" src="" alt="<?php esc_attr_e( 'Your custom pillow mockup', 'pillow-mockup-generator' ); ?>" data-pmg-result data-pmg-zoom-open />
				<button type="button" class="pmg__zoom-badge" data-pmg-zoom-open aria-label="<?php esc_attr_e( 'Zoom in', 'pillow-mockup-generator' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/><path d="M11 8v6"/><path d="M8 11h6"/></svg>
				</button>
			</figure>

			<div class="pmg__gallery" data-pmg-gallery hidden></div>

			<div class="pmg__toolbar" data-pmg-toolbar>
				<button type="button" class="pmg__tool" data-pmg-action="change">
					<span class="pmg__tool-icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3z"/><circle cx="12" cy="13" r="3"/></svg>
					</span>
					<span class="pmg__tool-label"><?php echo esc_html( $settings['text_change_photo'] ); ?></span>
				</button>
				<button type="button" class="pmg__btn pmg__btn--primary pmg__tool--cta" data-pmg-action="love">
					<?php echo esc_html( $settings['text_continue'] ); ?>
				</button>
			</div>

			<p class="pmg__notice" data-pmg-preview-notice hidden></p>
		</div>

		<!-- Size selection -->
		<div class="pmg__panel pmg__panel--size" data-panel="size">
			<h3 class="pmg__size-title"><?php echo esc_html( $settings['text_size_title'] ); ?></h3>
			<?php
			$pmg_sizes    = PMG_Settings::sizes();
			$pmg_currency = (string) $settings['price_currency'];
			?>
			<div class="pmg__sizes">
				<?php foreach ( $pmg_sizes as $pmg_size ) : ?>
					<button
						type="button"
						class="pmg__size"
						data-pmg-size="<?php echo esc_attr( $pmg_size['id'] ); ?>"
						data-price="<?php echo esc_attr( $pmg_size['price'] ); ?>"
						data-compare="<?php echo esc_attr( $pmg_size['compare'] ); ?>"
						data-label="<?php echo esc_attr( $pmg_size['label'] ); ?>"
						data-cm="<?php echo esc_attr( $pmg_size['cm'] ); ?>"
					>
						<span class="pmg__size-name"><?php echo esc_html( $pmg_size['label'] ); ?></span>
						<?php if ( '' !== $pmg_size['cm'] ) : ?>
							<span class="pmg__size-cm"><?php echo esc_html( $pmg_size['cm'] ); ?> <?php esc_html_e( 'ס"מ', 'pillow-mockup-generator' ); ?></span>
						<?php endif; ?>
						<span class="pmg__size-prices">
							<span class="pmg__size-price"><?php echo esc_html( $pmg_currency . ' ' . (string) (float) $pmg_size['price'] ); ?></span>
							<?php if ( $pmg_size['compare'] > 0 && $pmg_size['compare'] > $pmg_size['price'] ) : ?>
								<span class="pmg__size-compare"><?php echo esc_html( $pmg_currency . ' ' . (string) (float) $pmg_size['compare'] ); ?></span>
							<?php endif; ?>
						</span>
					</button>
				<?php endforeach; ?>
			</div>
			<p class="pmg__shipping">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 18V6a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h2"/><path d="M14 9h4l3 3v5a1 1 0 0 1-1 1h-1"/><circle cx="7.5" cy="18.5" r="1.5"/><circle cx="17.5" cy="18.5" r="1.5"/></svg>
				<span><?php esc_html_e( 'משלוח חינם עד הבית', 'pillow-mockup-generator' ); ?></span>
			</p>
			<div class="pmg__form-actions">
				<button type="button" class="pmg__btn pmg__btn--ghost" data-pmg-action="back-to-preview"><?php esc_html_e( 'חזרה', 'pillow-mockup-generator' ); ?></button>
			</div>
		</div>

		<!-- Details -->
		<div class="pmg__panel pmg__panel--details" data-panel="details">
			<form class="pmg__form" data-pmg-form novalidate>
				<h3 class="pmg__form-title"><?php echo esc_html( $settings['text_details_title'] ); ?></h3>
				<?php if ( ! empty( $settings['text_details_subtitle'] ) ) : ?>
					<p class="pmg__form-subtitle"><?php echo esc_html( $settings['text_details_subtitle'] ); ?></p>
				<?php endif; ?>

				<div class="pmg__order-summary" data-pmg-order-summary hidden></div>

				<div class="pmg__field-row">
					<div class="pmg__field">
						<div class="pmg__input-wrap">
							<input class="pmg__input" type="text" id="pmg-first-name" name="first_name" autocomplete="given-name" placeholder=" " data-pmg-input="first_name" />
							<label class="pmg__label" for="pmg-first-name"><?php echo esc_html( $settings['text_first_name'] ); ?></label>
						</div>
						<span class="pmg__error" data-pmg-error="first_name"></span>
					</div>

					<div class="pmg__field">
						<div class="pmg__input-wrap">
							<input class="pmg__input" type="text" id="pmg-last-name" name="last_name" autocomplete="family-name" placeholder=" " data-pmg-input="last_name" />
							<label class="pmg__label" for="pmg-last-name"><?php echo esc_html( $settings['text_last_name'] ); ?></label>
						</div>
						<span class="pmg__error" data-pmg-error="last_name"></span>
					</div>
				</div>

				<div class="pmg__field">
					<div class="pmg__input-wrap">
						<input class="pmg__input" type="tel" id="pmg-phone" name="phone" autocomplete="tel" placeholder=" " data-pmg-input="phone" />
						<label class="pmg__label" for="pmg-phone"><?php echo esc_html( $settings['text_phone'] ); ?></label>
					</div>
					<span class="pmg__error" data-pmg-error="phone"></span>
				</div>

				<div class="pmg__field">
					<div class="pmg__input-wrap">
						<input class="pmg__input" type="email" id="pmg-email" name="email" autocomplete="email" placeholder=" " data-pmg-input="email" />
						<label class="pmg__label" for="pmg-email"><?php echo esc_html( $settings['text_email'] ); ?></label>
					</div>
					<span class="pmg__error" data-pmg-error="email"></span>
				</div>

				<div class="pmg__form-actions">
					<button type="button" class="pmg__btn pmg__btn--ghost" data-pmg-action="back-to-size"><?php esc_html_e( 'חזרה', 'pillow-mockup-generator' ); ?></button>
					<button type="submit" class="pmg__btn pmg__btn--primary" data-pmg-submit><?php echo esc_html( $settings['text_submit'] ); ?></button>
				</div>

				<?php if ( ! empty( $settings['text_privacy_note'] ) ) : ?>
					<p class="pmg__privacy">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
						<span><?php echo esc_html( $settings['text_privacy_note'] ); ?></span>
					</p>
				<?php endif; ?>

				<p class="pmg__notice" data-pmg-form-notice hidden></p>
			</form>
		</div>

		<!-- Done -->
		<div class="pmg__panel pmg__panel--done" data-panel="done">
			<span class="pmg__done-icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
			</span>
			<h3 class="pmg__done-title"><?php echo esc_html( $settings['text_done_title'] ); ?></h3>
			<p class="pmg__done-message"><?php echo esc_html( $settings['text_done_message'] ); ?></p>
		</div>

	</div>

	<ul class="pmg__benefits">
		<li class="pmg__benefit">
			<img class="pmg__benefit-icon" src="https://www.schoolist.co.il/wp-content/uploads/2026/06/sewing.svg" alt="" width="44" height="44" />
			<div class="pmg__benefit-body">
				<h3 class="pmg__benefit-title"><?php esc_html_e( 'תפירה ידנית קפדנית', 'pillow-mockup-generator' ); ?></h3>
				<p class="pmg__benefit-text"><?php esc_html_e( 'כל מוצר מיוצר ביד דקה ובתשומת לב מלאה לפרטים הקטנים ביותר, בשביל איכות גימור ללא פשרות.', 'pillow-mockup-generator' ); ?></p>
			</div>
		</li>
		<li class="pmg__benefit">
			<img class="pmg__benefit-icon" src="https://www.schoolist.co.il/wp-content/uploads/2026/06/paint-bucket.svg" alt="" width="44" height="44" />
			<div class="pmg__benefit-body">
				<h3 class="pmg__benefit-title"><?php esc_html_e( 'הדפסה איכותית', 'pillow-mockup-generator' ); ?></h3>
				<p class="pmg__benefit-text"><?php esc_html_e( 'צבעים חיים ועמידים שלא דוהים בכביסה או בשימוש ממושך. המוצר שלך יישאר כמו חדש לאורך זמן.', 'pillow-mockup-generator' ); ?></p>
			</div>
		</li>
		<li class="pmg__benefit">
			<img class="pmg__benefit-icon" src="https://www.schoolist.co.il/wp-content/uploads/2026/06/gift.svg" alt="" width="44" height="44" />
			<div class="pmg__benefit-body">
				<h3 class="pmg__benefit-title"><?php esc_html_e( 'המתנה המושלמת עבורכם', 'pillow-mockup-generator' ); ?></h3>
				<p class="pmg__benefit-text"><?php esc_html_e( 'פריט ייחודי ומרגש שכיף לתת ועוד יותר כיף לקבל. סגירת פינה מושלמת לכל אירוע.', 'pillow-mockup-generator' ); ?></p>
			</div>
		</li>
	</ul>

	<!-- Upload modal (opens from the + button, Mixtiles-style) -->
	<div class="pmg__modal" data-pmg-modal hidden>
		<div class="pmg__modal-backdrop" data-pmg-close-modal></div>
		<div class="pmg__modal-card" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( $settings['text_upload'] ); ?>">
			<button type="button" class="pmg__modal-close" data-pmg-close-modal aria-label="<?php esc_attr_e( 'Close', 'pillow-mockup-generator' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
			</button>
			<button type="button" class="pmg__upload-option" data-pmg-upload>
				<span class="pmg__upload-icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M5 16v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3"/></svg>
				</span>
				<span class="pmg__upload-label"><?php echo esc_html( $settings['text_upload'] ); ?></span>
				<span class="pmg__upload-hint"><?php esc_html_e( 'JPG, PNG or WEBP', 'pillow-mockup-generator' ); ?></span>
			</button>
		</div>
	</div>

	<!-- Zoom lightbox for the generated mockup -->
	<div class="pmg__lightbox" data-pmg-lightbox hidden>
		<div class="pmg__lightbox-backdrop" data-pmg-zoom-close></div>
		<button type="button" class="pmg__lightbox-close" data-pmg-zoom-close aria-label="<?php esc_attr_e( 'Close', 'pillow-mockup-generator' ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
		</button>
		<div class="pmg__lightbox-stage">
			<img class="pmg__lightbox-img" src="" alt="<?php esc_attr_e( 'Your custom pillow mockup', 'pillow-mockup-generator' ); ?>" data-pmg-zoom-img />
		</div>
	</div>
</div>
<?php

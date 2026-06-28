<?php
/**
 * Front-end widget markup — popup modal flow (English, LTR).
 *
 * The widget renders only a hidden file input plus a popup modal that hosts the
 * full purchase flow (loading -> preview -> size -> details -> done). The modal
 * is opened by any element on the page carrying `class="pmg-open"` or the
 * `data-pmg-open` attribute, which triggers the native file picker.
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
<div class="pmg pmg--v2 <?php echo esc_attr( $pmg_extra_class ); ?>" data-pmg data-state="idle" dir="ltr"<?php echo '' !== $pmg_root_style ? ' style="' . esc_attr( $pmg_root_style ) . '"' : ''; ?>>

	<input type="file" class="pmg__file" accept="image/*" data-pmg-file hidden />

	<!-- Popup modal hosting the full flow -->
	<div class="pmg__modal" data-pmg-modal hidden>
		<div class="pmg__modal-backdrop" data-pmg-close></div>
		<div class="pmg__modal-card" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Your custom pillow', 'pillow-mockup-generator' ); ?>">
			<button type="button" class="pmg__modal-close" data-pmg-close aria-label="<?php esc_attr_e( 'Close', 'pillow-mockup-generator' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
			</button>

			<div class="pmg__topbar">
				<img draggable="false" class="pmg__emoji" alt="" src="https://s.w.org/images/core/emoji/17.0.2/svg/26a1.svg" /> <?php esc_html_e( 'Free US Shipping – Limited Time Only!', 'pillow-mockup-generator' ); ?>
			</div>

			<div class="pmg__modal-body">

				<section class="pmg__hero">
					<div class="pmg__award">
						<img decoding="async" src="https://on1y.one/wp-content/uploads/2026/06/download-8.svg" alt="<?php esc_attr_e( 'Award', 'pillow-mockup-generator' ); ?>" />
						<?php esc_html_e( 'Most Creative Gift of 2026', 'pillow-mockup-generator' ); ?>
					</div>
					<h1 class="pmg__hero-title"><?php esc_html_e( 'Magic', 'pillow-mockup-generator' ); ?> <span><?php esc_html_e( 'in Progress!', 'pillow-mockup-generator' ); ?></span></h1>
					<p class="pmg__hero-subtitle"><?php esc_html_e( 'We\'re crafting your design. Your custom pillow preview will appear in just a few seconds.', 'pillow-mockup-generator' ); ?></p>
				</section>

				<div class="pmg__stage">

				<!-- Loading -->
				<div class="pmg__panel pmg__panel--loading" data-panel="loading">
					<div class="pmg__lottie" data-pmg-lottie aria-hidden="true"></div>
					<div class="pmg__spinner" aria-hidden="true"></div>
					<p class="pmg__loading-text" data-pmg-loading-text><?php esc_html_e( 'Creating your pillow mockup…', 'pillow-mockup-generator' ); ?></p>
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
							<span class="pmg__tool-label"><?php esc_html_e( 'Change Photo', 'pillow-mockup-generator' ); ?></span>
						</button>
						<button type="button" class="pmg__btn pmg__btn--primary pmg__tool--cta" data-pmg-action="love">
							<?php esc_html_e( 'Continue', 'pillow-mockup-generator' ); ?>
						</button>
					</div>

					<p class="pmg__notice" data-pmg-preview-notice hidden></p>
				</div>

				<!-- Size selection -->
				<div class="pmg__panel pmg__panel--size" data-panel="size">
					<figure class="pmg__size-preview" data-pmg-size-preview-wrap hidden>
						<img class="pmg__size-preview-img" src="" alt="<?php esc_attr_e( 'Your selected design', 'pillow-mockup-generator' ); ?>" data-pmg-size-preview />
					</figure>
					<h3 class="pmg__size-title"><?php esc_html_e( 'Choose your size', 'pillow-mockup-generator' ); ?></h3>
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
									<span class="pmg__size-cm"><?php echo esc_html( $pmg_size['cm'] ); ?></span>
								<?php endif; ?>
								<span class="pmg__size-prices">
									<span class="pmg__size-price"><?php echo esc_html( $pmg_currency . (string) (float) $pmg_size['price'] ); ?></span>
									<?php if ( $pmg_size['compare'] > 0 && $pmg_size['compare'] > $pmg_size['price'] ) : ?>
										<span class="pmg__size-compare"><?php echo esc_html( $pmg_currency . (string) (float) $pmg_size['compare'] ); ?></span>
									<?php endif; ?>
								</span>
							</button>
						<?php endforeach; ?>
					</div>
					<p class="pmg__shipping">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 18V6a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h2"/><path d="M14 9h4l3 3v5a1 1 0 0 1-1 1h-1"/><circle cx="7.5" cy="18.5" r="1.5"/><circle cx="17.5" cy="18.5" r="1.5"/></svg>
						<span><?php esc_html_e( 'Free shipping to your door', 'pillow-mockup-generator' ); ?></span>
					</p>
					<div class="pmg__form-actions">
						<button type="button" class="pmg__btn pmg__btn--ghost" data-pmg-action="back-to-preview"><?php esc_html_e( 'Back', 'pillow-mockup-generator' ); ?></button>
					</div>
				</div>

				<!-- Details -->
				<div class="pmg__panel pmg__panel--details" data-panel="details">
					<form class="pmg__form" data-pmg-form novalidate>
						<h3 class="pmg__form-title"><?php esc_html_e( 'Almost there — your details', 'pillow-mockup-generator' ); ?></h3>
						<p class="pmg__form-subtitle"><?php esc_html_e( "Save your design and we'll send it straight to you — it only takes a few seconds.", 'pillow-mockup-generator' ); ?></p>

						<div class="pmg__order-summary" data-pmg-order-summary hidden></div>

						<div class="pmg__field">
							<div class="pmg__input-wrap">
								<input class="pmg__input" type="text" id="pmg-first-name" name="first_name" autocomplete="given-name" placeholder=" " data-pmg-input="first_name" />
								<label class="pmg__label" for="pmg-first-name"><?php esc_html_e( 'First name', 'pillow-mockup-generator' ); ?></label>
							</div>
							<span class="pmg__error" data-pmg-error="first_name"></span>
						</div>

						<div class="pmg__field">
							<div class="pmg__input-wrap">
								<input class="pmg__input" type="text" id="pmg-last-name" name="last_name" autocomplete="family-name" placeholder=" " data-pmg-input="last_name" />
								<label class="pmg__label" for="pmg-last-name"><?php esc_html_e( 'Last name', 'pillow-mockup-generator' ); ?></label>
							</div>
							<span class="pmg__error" data-pmg-error="last_name"></span>
						</div>

						<div class="pmg__field">
							<div class="pmg__input-wrap">
								<input class="pmg__input" type="email" id="pmg-email" name="email" autocomplete="email" placeholder=" " data-pmg-input="email" />
								<label class="pmg__label" for="pmg-email"><?php esc_html_e( 'Email', 'pillow-mockup-generator' ); ?></label>
							</div>
							<span class="pmg__error" data-pmg-error="email"></span>
						</div>

						<div class="pmg__field">
							<div class="pmg__input-wrap">
								<input class="pmg__input" type="text" id="pmg-address" name="address" autocomplete="street-address" placeholder=" " data-pmg-input="address" />
								<label class="pmg__label" for="pmg-address"><?php esc_html_e( 'Address', 'pillow-mockup-generator' ); ?></label>
							</div>
							<span class="pmg__error" data-pmg-error="address"></span>
						</div>

						<div class="pmg__field">
							<div class="pmg__input-wrap">
								<input class="pmg__input" type="text" id="pmg-apartment" name="apartment" autocomplete="address-line2" placeholder=" " data-pmg-input="apartment" />
								<label class="pmg__label" for="pmg-apartment"><?php esc_html_e( 'Apartment, suite, etc. (optional)', 'pillow-mockup-generator' ); ?></label>
							</div>
							<span class="pmg__error" data-pmg-error="apartment"></span>
						</div>

						<div class="pmg__field">
							<div class="pmg__input-wrap">
								<input class="pmg__input" type="text" id="pmg-city" name="city" autocomplete="address-level2" placeholder=" " data-pmg-input="city" />
								<label class="pmg__label" for="pmg-city"><?php esc_html_e( 'City', 'pillow-mockup-generator' ); ?></label>
							</div>
							<span class="pmg__error" data-pmg-error="city"></span>
						</div>

						<div class="pmg__field">
							<div class="pmg__input-wrap pmg__input-wrap--select">
								<select class="pmg__input pmg__select" id="pmg-state" name="state" autocomplete="address-level1" data-pmg-input="state">
									<option value="" selected><?php esc_html_e( 'State', 'pillow-mockup-generator' ); ?></option>
									<?php foreach ( PMG_Settings::us_states() as $pmg_state ) : ?>
										<option value="<?php echo esc_attr( $pmg_state ); ?>"><?php echo esc_html( $pmg_state ); ?></option>
									<?php endforeach; ?>
								</select>
								<span class="pmg__select-arrow" aria-hidden="true">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
								</span>
							</div>
							<span class="pmg__error" data-pmg-error="state"></span>
						</div>

						<div class="pmg__field">
							<div class="pmg__input-wrap">
								<input class="pmg__input" type="text" id="pmg-zip" name="zip" autocomplete="postal-code" inputmode="numeric" placeholder=" " data-pmg-input="zip" />
								<label class="pmg__label" for="pmg-zip"><?php esc_html_e( 'ZIP code', 'pillow-mockup-generator' ); ?></label>
							</div>
							<span class="pmg__error" data-pmg-error="zip"></span>
						</div>

						<div class="pmg__field">
							<div class="pmg__input-wrap">
								<input class="pmg__input" type="tel" id="pmg-phone" name="phone" autocomplete="tel" placeholder=" " data-pmg-input="phone" />
								<label class="pmg__label" for="pmg-phone"><?php esc_html_e( 'Phone', 'pillow-mockup-generator' ); ?></label>
							</div>
							<span class="pmg__error" data-pmg-error="phone"></span>
						</div>

						<div class="pmg__form-actions">
							<button type="button" class="pmg__btn pmg__btn--ghost" data-pmg-action="back-to-size"><?php esc_html_e( 'Back', 'pillow-mockup-generator' ); ?></button>
							<button type="submit" class="pmg__btn pmg__btn--primary" data-pmg-submit><?php esc_html_e( 'Continue', 'pillow-mockup-generator' ); ?></button>
						</div>

						<p class="pmg__privacy">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
							<span><?php esc_html_e( 'Your details are safe with us — no spam, ever.', 'pillow-mockup-generator' ); ?></span>
						</p>

						<p class="pmg__notice" data-pmg-form-notice hidden></p>
					</form>
				</div>

				<!-- Done -->
				<div class="pmg__panel pmg__panel--done" data-panel="done">
					<span class="pmg__done-icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
					</span>
					<h3 class="pmg__done-title"><?php esc_html_e( 'Thank you!', 'pillow-mockup-generator' ); ?></h3>
					<p class="pmg__done-message"><?php esc_html_e( 'We received your request and will contact you shortly to complete the process.', 'pillow-mockup-generator' ); ?></p>
				</div>

				</div>
			</div>
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

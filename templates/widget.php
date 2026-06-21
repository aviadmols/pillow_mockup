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
?>
<div class="pmg pmg--rtl pmg--loading <?php echo esc_attr( $pmg_extra_class ); ?>" data-pmg data-state="upload" dir="rtl">

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

			<div class="pmg__toolbar" data-pmg-toolbar>
				<button type="button" class="pmg__tool" data-pmg-action="change">
					<span class="pmg__tool-icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3z"/><circle cx="12" cy="13" r="3"/></svg>
					</span>
					<span class="pmg__tool-label"><?php echo esc_html( $settings['text_change_photo'] ); ?></span>
				</button>
				<button type="button" class="pmg__tool" data-pmg-action="retry">
					<span class="pmg__tool-icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
					</span>
					<span class="pmg__tool-label"><?php echo esc_html( $settings['text_try_again'] ); ?></span>
					<span class="pmg__attempts" data-pmg-attempts hidden></span>
				</button>
				<button type="button" class="pmg__btn pmg__btn--primary pmg__tool--cta" data-pmg-action="love">
					<?php echo esc_html( $settings['text_continue'] ); ?>
				</button>
			</div>

			<p class="pmg__notice" data-pmg-preview-notice hidden></p>
		</div>

		<!-- Details -->
		<div class="pmg__panel pmg__panel--details" data-panel="details">
			<form class="pmg__form" data-pmg-form novalidate>
				<h3 class="pmg__form-title"><?php echo esc_html( $settings['text_details_title'] ); ?></h3>
				<?php if ( ! empty( $settings['text_details_subtitle'] ) ) : ?>
					<p class="pmg__form-subtitle"><?php echo esc_html( $settings['text_details_subtitle'] ); ?></p>
				<?php endif; ?>

				<div class="pmg__field-row">
					<div class="pmg__field">
						<label class="pmg__label" for="pmg-first-name"><?php echo esc_html( $settings['text_first_name'] ); ?></label>
						<div class="pmg__input-wrap">
							<span class="pmg__input-icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
							</span>
							<input class="pmg__input" type="text" id="pmg-first-name" name="first_name" autocomplete="given-name" placeholder="<?php echo esc_attr( $settings['text_first_name'] ); ?>" data-pmg-input="first_name" />
						</div>
						<span class="pmg__error" data-pmg-error="first_name"></span>
					</div>

					<div class="pmg__field">
						<label class="pmg__label" for="pmg-last-name"><?php echo esc_html( $settings['text_last_name'] ); ?></label>
						<div class="pmg__input-wrap">
							<span class="pmg__input-icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
							</span>
							<input class="pmg__input" type="text" id="pmg-last-name" name="last_name" autocomplete="family-name" placeholder="<?php echo esc_attr( $settings['text_last_name'] ); ?>" data-pmg-input="last_name" />
						</div>
						<span class="pmg__error" data-pmg-error="last_name"></span>
					</div>
				</div>

				<div class="pmg__field">
					<label class="pmg__label" for="pmg-phone"><?php echo esc_html( $settings['text_phone'] ); ?></label>
					<div class="pmg__input-wrap">
						<span class="pmg__input-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.94.36 1.86.7 2.74a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.88.34 1.8.57 2.74.7A2 2 0 0 1 22 16.92z"/></svg>
						</span>
						<input class="pmg__input" type="tel" id="pmg-phone" name="phone" autocomplete="tel" placeholder="<?php echo esc_attr( $settings['text_phone'] ); ?>" data-pmg-input="phone" />
					</div>
					<span class="pmg__error" data-pmg-error="phone"></span>
				</div>

				<div class="pmg__field">
					<label class="pmg__label" for="pmg-email"><?php echo esc_html( $settings['text_email'] ); ?></label>
					<div class="pmg__input-wrap">
						<span class="pmg__input-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
						</span>
						<input class="pmg__input" type="email" id="pmg-email" name="email" autocomplete="email" placeholder="<?php echo esc_attr( $settings['text_email'] ); ?>" data-pmg-input="email" />
					</div>
					<span class="pmg__error" data-pmg-error="email"></span>
				</div>

				<div class="pmg__form-actions">
					<button type="button" class="pmg__btn pmg__btn--ghost" data-pmg-action="back-to-preview"><?php esc_html_e( 'Back', 'pillow-mockup-generator' ); ?></button>
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

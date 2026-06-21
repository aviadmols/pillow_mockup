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
$pmg_rtl_class   = is_rtl() ? ' pmg--rtl' : '';
?>
<div class="pmg<?php echo esc_attr( $pmg_rtl_class ); ?> <?php echo esc_attr( $pmg_extra_class ); ?>" data-pmg data-state="upload"<?php echo is_rtl() ? ' dir="rtl"' : ''; ?>>

	<header class="pmg__head">
		<h2 class="pmg__title"><?php echo esc_html( $settings['text_heading'] ); ?></h2>
		<p class="pmg__subtitle"><?php echo esc_html( $settings['text_subheading'] ); ?></p>
	</header>

	<div class="pmg__stage">

		<!-- Upload -->
		<div class="pmg__panel pmg__panel--upload" data-panel="upload">
			<label class="pmg__dropzone" data-pmg-dropzone>
				<input type="file" class="pmg__file" accept="image/png,image/jpeg,image/webp" data-pmg-file hidden />
				<span class="pmg__dropzone-icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M5 16v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3"/></svg>
				</span>
				<span class="pmg__btn pmg__btn--primary"><?php echo esc_html( $settings['text_upload'] ); ?></span>
				<span class="pmg__dropzone-hint"><?php esc_html_e( 'JPG, PNG or WEBP', 'pillow-mockup-generator' ); ?></span>
			</label>
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
				<img class="pmg__result" src="" alt="<?php esc_attr_e( 'Your custom pillow mockup', 'pillow-mockup-generator' ); ?>" data-pmg-result />
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

				<div class="pmg__field">
					<label class="pmg__label" for="pmg-name"><?php echo esc_html( $settings['text_name'] ); ?></label>
					<input class="pmg__input" type="text" id="pmg-name" name="name" autocomplete="name" data-pmg-input="name" />
					<span class="pmg__error" data-pmg-error="name"></span>
				</div>

				<div class="pmg__field">
					<label class="pmg__label" for="pmg-phone"><?php echo esc_html( $settings['text_phone'] ); ?></label>
					<input class="pmg__input" type="tel" id="pmg-phone" name="phone" autocomplete="tel" data-pmg-input="phone" />
					<span class="pmg__error" data-pmg-error="phone"></span>
				</div>

				<div class="pmg__field">
					<label class="pmg__label" for="pmg-email"><?php echo esc_html( $settings['text_email'] ); ?></label>
					<input class="pmg__input" type="email" id="pmg-email" name="email" autocomplete="email" data-pmg-input="email" />
					<span class="pmg__error" data-pmg-error="email"></span>
				</div>

				<div class="pmg__form-actions">
					<button type="button" class="pmg__btn pmg__btn--ghost" data-pmg-action="back-to-preview"><?php esc_html_e( 'Back', 'pillow-mockup-generator' ); ?></button>
					<button type="submit" class="pmg__btn pmg__btn--primary" data-pmg-submit><?php echo esc_html( $settings['text_submit'] ); ?></button>
				</div>

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
</div>
<?php

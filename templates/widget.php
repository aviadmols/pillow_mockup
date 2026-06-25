<?php
/**
 * Front-end widget markup (on1y.one design — English, LTR).
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
$pmg_media       = 'https://on1y.one/wp-content/uploads/2026/06/';
?>
<div class="pmg pmg--v2 pmg--loading <?php echo esc_attr( $pmg_extra_class ); ?>" data-pmg data-state="upload" dir="ltr"<?php echo '' !== $pmg_root_style ? ' style="' . esc_attr( $pmg_root_style ) . '"' : ''; ?>>

	<div class="pmg__boot" aria-hidden="true">
		<span class="pmg__boot-spinner"></span>
	</div>

	<!-- Landing (marketing) — shown only on the upload screen -->
	<div class="pmg__landing">

		<div class="pmg__topbar">
			<?php esc_html_e( '⚡ Free US Shipping – Limited Time Only!', 'pillow-mockup-generator' ); ?>
		</div>

		<div class="pmg__logo">
			<img src="<?php echo esc_url( $pmg_media . 'only01.png' ); ?>" alt="on1y.one" />
		</div>

		<div class="pmg__container">

			<div class="pmg__hero-slider" aria-hidden="true">
				<img src="<?php echo esc_url( $pmg_media . '034.jpg' ); ?>" class="pmg__slide" alt="" />
				<img src="<?php echo esc_url( $pmg_media . '04.jpg' ); ?>" class="pmg__slide" alt="" />
				<img src="<?php echo esc_url( $pmg_media . '08.jpg' ); ?>" class="pmg__slide" alt="" />
				<img src="<?php echo esc_url( $pmg_media . '03.jpg' ); ?>" class="pmg__slide" alt="" />
				<img src="<?php echo esc_url( $pmg_media . '02.jpg' ); ?>" class="pmg__slide" alt="" />
			</div>

			<section class="pmg__hero">
				<div class="pmg__award">
					<img src="<?php echo esc_url( $pmg_media . 'download-8.svg' ); ?>" alt="" />
					<?php esc_html_e( 'Most Creative Gift of 2026', 'pillow-mockup-generator' ); ?>
				</div>
				<h1 class="pmg__hero-title"><?php esc_html_e( 'Turn Your Photo into a Custom Pillow', 'pillow-mockup-generator' ); ?> <span><?php esc_html_e( '– In Less Than 5 Seconds!', 'pillow-mockup-generator' ); ?></span></h1>
				<p class="pmg__hero-subtitle"><?php esc_html_e( 'Upload now for a 100% Free live preview. See exactly how your unique pillow will look before you buy!', 'pillow-mockup-generator' ); ?></p>
			</section>

			<section class="pmg__steps">
				<div class="pmg__steps-line"></div>
				<div class="pmg__step">
					<span class="pmg__step-badge"><?php esc_html_e( 'Step 1', 'pillow-mockup-generator' ); ?></span>
					<div class="pmg__step-text">
						<h3 class="pmg__step-title"><?php esc_html_e( 'Upload in 1-Click', 'pillow-mockup-generator' ); ?></h3>
						<p class="pmg__step-desc"><?php esc_html_e( 'Free & Instant', 'pillow-mockup-generator' ); ?></p>
					</div>
				</div>
				<div class="pmg__step">
					<span class="pmg__step-badge"><?php esc_html_e( 'Step 2', 'pillow-mockup-generator' ); ?></span>
					<div class="pmg__step-text">
						<h3 class="pmg__step-title"><?php esc_html_e( 'See the Magic', 'pillow-mockup-generator' ); ?></h3>
						<p class="pmg__step-desc"><?php esc_html_e( 'See exactly how it looks live!', 'pillow-mockup-generator' ); ?></p>
					</div>
				</div>
				<div class="pmg__step">
					<span class="pmg__step-badge"><?php esc_html_e( 'Step 3', 'pillow-mockup-generator' ); ?></span>
					<div class="pmg__step-text">
						<h3 class="pmg__step-title"><?php esc_html_e( 'Love it? Order!', 'pillow-mockup-generator' ); ?></h3>
						<p class="pmg__step-desc"><?php esc_html_e( 'Hand-sew & delivered', 'pillow-mockup-generator' ); ?></p>
					</div>
				</div>
			</section>

			<!-- Upload area (direct native file picker) -->
			<div class="pmg__upload" data-pmg-dropzone data-pmg-pick role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Upload your photo', 'pillow-mockup-generator' ); ?>">
				<input type="file" class="pmg__file" accept="image/*" data-pmg-file hidden />
				<img src="<?php echo esc_url( $pmg_media . 'download-9.svg' ); ?>" class="pmg__upload-icon-img" alt="" />
				<h2 class="pmg__upload-main"><?php esc_html_e( 'Tap anywhere inside to upload your photo', 'pillow-mockup-generator' ); ?></h2>
				<p class="pmg__upload-sub"><?php esc_html_e( 'Supports JPG or PNG • High quality recommended', 'pillow-mockup-generator' ); ?></p>
				<div class="pmg__upload-inner">
					<div class="pmg__arrow">
						<span><?php esc_html_e( 'Try it here!', 'pillow-mockup-generator' ); ?></span>
						<img src="<?php echo esc_url( $pmg_media . 'arrow-down.svg' ); ?>" alt="" />
					</div>
					<div class="pmg__upload-cta">
						<span class="pmg__plus" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
						</span>
						<span class="pmg__upload-cta-label"><?php esc_html_e( 'Choose Photo', 'pillow-mockup-generator' ); ?></span>
					</div>
				</div>
				<p class="pmg__upload-micro"><?php esc_html_e( 'Takes less than 5 seconds', 'pillow-mockup-generator' ); ?> <br><?php esc_html_e( '• Fully Free • Zero Obligation', 'pillow-mockup-generator' ); ?></p>
				<p class="pmg__notice" data-pmg-upload-notice hidden></p>
			</div>

			<section class="pmg__features">
				<div class="pmg__features-grid">
					<div class="pmg__feature">
						<span class="pmg__feature-icon"><img src="<?php echo esc_url( $pmg_media . 'download.svg' ); ?>" alt="" /></span>
						<div>
							<h4 class="pmg__feature-title"><?php esc_html_e( 'Proudly Made in the USA', 'pillow-mockup-generator' ); ?></h4>
							<p class="pmg__feature-desc"><?php esc_html_e( 'Both sourcing and production are done locally with premium materials.', 'pillow-mockup-generator' ); ?></p>
						</div>
					</div>
					<div class="pmg__feature">
						<span class="pmg__feature-icon"><img src="<?php echo esc_url( $pmg_media . 'download-3.svg' ); ?>" alt="" /></span>
						<div>
							<h4 class="pmg__feature-title"><?php esc_html_e( 'Super Soft & Zipper-Free', 'pillow-mockup-generator' ); ?></h4>
							<p class="pmg__feature-desc"><?php esc_html_e( '100% polyester fabric that is soft to the touch and sealed without scratchy zippers.', 'pillow-mockup-generator' ); ?></p>
						</div>
					</div>
					<div class="pmg__feature">
						<span class="pmg__feature-icon"><img src="<?php echo esc_url( $pmg_media . 'download-2.svg' ); ?>" alt="" /></span>
						<div>
							<h4 class="pmg__feature-title"><?php esc_html_e( 'Custom-Shaped for You', 'pillow-mockup-generator' ); ?></h4>
							<p class="pmg__feature-desc"><?php esc_html_e( "We don't just print on a square — we cut and sew the fabric to match the unique shape of your photo!", 'pillow-mockup-generator' ); ?></p>
						</div>
					</div>
					<div class="pmg__feature">
						<span class="pmg__feature-icon"><img src="<?php echo esc_url( $pmg_media . 'download-1.svg' ); ?>" alt="" /></span>
						<div>
							<h4 class="pmg__feature-title"><?php esc_html_e( '3 Perfect Sizes', 'pillow-mockup-generator' ); ?></h4>
							<p class="pmg__feature-desc"><?php esc_html_e( 'Choose the perfect fit for your couch or bed: Small (10"), Medium (16"), or Large (22").', 'pillow-mockup-generator' ); ?></p>
						</div>
					</div>
				</div>
			</section>

			<section class="pmg__reviews">
				<h2 class="pmg__reviews-title"><?php esc_html_e( 'Loved by Thousands', 'pillow-mockup-generator' ); ?> <br><?php esc_html_e( 'Across the US', 'pillow-mockup-generator' ); ?></h2>
				<div class="pmg__reviews-wrap" data-pmg-reviews-wrap>
					<div class="pmg__reviews-track" data-pmg-reviews-track>
						<?php
						$pmg_reviews = array(
							array( '"We ordered a custom shape pillow of the grandkids for my parents\' anniversary. They both cried tears of joy! The fabric is so unbelievably soft and the print is crisp."', 'Sarah M.', 'Austin, TX' ),
							array( '"Got this for my husband for Father\'s Day with a picture of him and our daughter. He keeps it on his favorite armchair. Best personalized gift I\'ve ever purchased online."', 'David K.', 'Chicago, IL' ),
							array( '"Our golden retriever passed away last month, and I had a custom pillow made from his photo. It feels like having a little piece of him back on the sofa with us."', 'Emily R.', 'San Diego, CA' ),
							array( '"Surprised my sister with a custom shape pillow of her cat for her college dorm room. She absolutely loves it and says all her roommates keep trying to steal it!"', 'Michael T.', 'Boston, MA' ),
							array( '"I made a silly face pillow of my brother for his graduation gift. It turned out so funny and well-made! The custom cutting around the hair outline is totally flawless."', 'Jessica W.', 'Nashville, TN' ),
							array( '"Brought this as a housewarming gift with a photo of our tight-knit friend group. It\'s now the centerpiece of the living room. Super soft and looks very premium."', 'Brian L.', 'Denver, CO' ),
							array( '"Ordered a large size pillow of our wedding photo for our bedroom. The details are incredible and the color matching is spot on. Will definitely order more as holiday gifts."', 'Amanda P.', 'Seattle, WA' ),
							array( '"My kids made one for me using an old photo of our first family trip. It\'s completely zipper-free, making it perfect to actually lean on. Truly a flawless keepsake."', 'Robert H.', 'Orlando, FL' ),
						);
						foreach ( $pmg_reviews as $pmg_review ) :
							?>
							<div class="pmg__review-wrap">
								<div class="pmg__review">
									<div class="pmg__stars">
										<?php for ( $pmg_s = 0; $pmg_s < 5; $pmg_s++ ) : ?>
											<img src="<?php echo esc_url( $pmg_media . 'download-7.svg' ); ?>" alt="" />
										<?php endfor; ?>
									</div>
									<p class="pmg__review-text"><?php echo esc_html( $pmg_review[0] ); ?></p>
									<div>
										<p class="pmg__review-author"><?php echo esc_html( $pmg_review[1] ); ?></p>
										<p class="pmg__review-location"><?php echo esc_html( $pmg_review[2] ); ?></p>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="pmg__dots" data-pmg-reviews-dots></div>
			</section>

			<div class="pmg__trust">
				<div class="pmg__trust-badge">
					<img src="<?php echo esc_url( $pmg_media . 'download-6.svg' ); ?>" alt="" />
					<?php esc_html_e( 'Secure Checkout', 'pillow-mockup-generator' ); ?>
				</div>
				<div class="pmg__trust-badge">
					<img src="<?php echo esc_url( $pmg_media . 'download-4.svg' ); ?>" alt="" />
					<?php esc_html_e( 'Happiness Guarantee', 'pillow-mockup-generator' ); ?>
				</div>
				<div class="pmg__trust-badge">
					<img src="<?php echo esc_url( $pmg_media . 'download-5.svg' ); ?>" alt="" />
					<?php esc_html_e( 'Fast US Shipping', 'pillow-mockup-generator' ); ?>
				</div>
			</div>

			<p class="pmg__footnote">
				<?php esc_html_e( '*Care Instructions: Spot wash with warm water.', 'pillow-mockup-generator' ); ?><br>
				<?php esc_html_e( 'Custom pillows include a subtle white outline around the design to make your photo pop perfectly.', 'pillow-mockup-generator' ); ?>
			</p>
		</div>

		<footer class="pmg__site-footer">
			<div class="pmg__footer-inner">
				<div class="pmg__footer-links">
					<a href="#"><?php esc_html_e( 'Terms of Service', 'pillow-mockup-generator' ); ?></a>
					<a href="#"><?php esc_html_e( 'Privacy Policy', 'pillow-mockup-generator' ); ?></a>
					<a href="#"><?php esc_html_e( 'Shipping & Returns', 'pillow-mockup-generator' ); ?></a>
				</div>
				<div class="pmg__footer-contact">
					<?php esc_html_e( 'Need help? Contact us at', 'pillow-mockup-generator' ); ?> <a href="mailto:support@on1y.one">support@on1y.one</a>
				</div>
				<div class="pmg__copyright">
					<?php esc_html_e( '© 2026 on1y.one. All rights reserved.', 'pillow-mockup-generator' ); ?>
				</div>
			</div>
		</footer>
	</div>

	<!-- Flow stage — shown once a photo is uploaded -->
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

				<div class="pmg__field-row">
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
				</div>

				<div class="pmg__field">
					<div class="pmg__input-wrap">
						<input class="pmg__input" type="tel" id="pmg-phone" name="phone" autocomplete="tel" placeholder=" " data-pmg-input="phone" />
						<label class="pmg__label" for="pmg-phone"><?php esc_html_e( 'Phone', 'pillow-mockup-generator' ); ?></label>
					</div>
					<span class="pmg__error" data-pmg-error="phone"></span>
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
						<input class="pmg__input" type="text" id="pmg-city" name="city" autocomplete="address-level2" placeholder=" " data-pmg-input="city" />
						<label class="pmg__label" for="pmg-city"><?php esc_html_e( 'City', 'pillow-mockup-generator' ); ?></label>
					</div>
					<span class="pmg__error" data-pmg-error="city"></span>
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

	<!-- Sticky upload CTA (mobile, upload screen only) -->
	<div class="pmg__sticky" data-pmg-sticky>
		<div class="pmg__upload-cta" data-pmg-pick role="button" tabindex="0">
			<span class="pmg__plus" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
			</span>
			<span class="pmg__upload-cta-label"><?php esc_html_e( 'See Your Photo as a Pillow!', 'pillow-mockup-generator' ); ?></span>
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

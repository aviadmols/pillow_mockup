<?php
/**
 * Front-end template for the experimental room-overlay lab ([pillow_mockup_lab]).
 *
 * Available vars:
 *  - array $atts  Shortcode atts (class).
 *  - array $lab   Lab option (room_url, pos_x, pos_y, base_width, scales...).
 *  - array $sizes PMG_Settings::sizes() tiers.
 *
 * @package PillowMockupGenerator
 */

defined( 'ABSPATH' ) || exit;

$pmg_lab_class = isset( $atts['class'] ) ? trim( (string) $atts['class'] ) : '';
$pmg_room_url  = (string) $lab['room_url'];
$pmg_demo_url  = (string) $lab['demo_url'];
$pmg_demo_text = (string) $lab['demo_text'];
?>
<div class="pmg-lab <?php echo esc_attr( $pmg_lab_class ); ?>" data-pmg-lab dir="ltr">

	<input type="file" class="pmg-lab__file" accept="image/*" data-lab-file hidden />

	<?php if ( '' === $pmg_room_url ) : ?>
		<div class="pmg-lab__empty">
			<?php esc_html_e( 'The room mockup has not been set up yet.', 'pillow-mockup-generator' ); ?>
		</div>
	<?php else : ?>
		<div class="pmg-lab__stage" data-lab-stage>
			<img class="pmg-lab__room" src="<?php echo esc_url( $pmg_room_url ); ?>" alt="" data-lab-room />
			<img class="pmg-lab__overlay" src="" alt="" data-lab-overlay hidden />
			<?php if ( '' !== $pmg_demo_url && '' !== trim( $pmg_demo_text ) ) : ?>
				<div class="pmg-lab__cta" data-lab-cta hidden>
					<span class="pmg-lab__cta-text"><?php echo esc_html( $pmg_demo_text ); ?></span>
					<svg class="pmg-lab__cta-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<path d="M12 4v15" />
						<path d="m5 13 7 7 7-7" />
					</svg>
				</div>
			<?php endif; ?>
			<div class="pmg-lab__loading" data-lab-loading hidden>
				<div class="pmg-lab__lottie" data-lab-lottie aria-hidden="true"></div>
				<span class="pmg-lab__spinner" aria-hidden="true"></span>
				<span class="pmg-lab__loading-text"><?php esc_html_e( 'Creating your pillow…', 'pillow-mockup-generator' ); ?></span>
			</div>
		</div>

		<div class="pmg-lab__controls">
			<button type="button" class="pmg-lab__btn pmg-lab__btn--primary" data-lab-upload>
				<?php esc_html_e( 'Upload photo', 'pillow-mockup-generator' ); ?>
			</button>

			<?php if ( ! empty( $sizes ) ) : ?>
				<div class="pmg-lab__sizes" data-lab-sizes hidden>
					<?php foreach ( $sizes as $pmg_i => $pmg_size ) : ?>
						<button
							type="button"
							class="pmg-lab__size<?php echo 'medium' === $pmg_size['id'] ? ' is-selected' : ''; ?>"
							data-lab-size="<?php echo esc_attr( $pmg_size['id'] ); ?>"
						>
							<span class="pmg-lab__size-name"><?php echo esc_html( $pmg_size['label'] ); ?></span>
							<?php if ( '' !== $pmg_size['cm'] ) : ?>
								<span class="pmg-lab__size-cm"><?php echo esc_html( $pmg_size['cm'] ); ?></span>
							<?php endif; ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<p class="pmg-lab__notice" data-lab-notice hidden></p>
	<?php endif; ?>
</div>

<?php
/**
 * Admin page for the experimental room-overlay lab.
 *
 * Available vars:
 *  - array  $lab    Lab option.
 *  - array  $sizes  PMG_Settings::sizes() tiers.
 *  - string $notice Notice key from the redirect.
 *
 * @package PillowMockupGenerator
 */

defined( 'ABSPATH' ) || exit;

$pmg_room_url    = (string) $lab['room_url'];
$pmg_ref_url     = (string) $lab['ref_url'];
$pmg_preview_src = '' !== $pmg_room_url ? $pmg_room_url : $pmg_ref_url;

$pmg_notices = array(
	'room_ok'  => array( 'updated', __( 'Room mockup generated.', 'pillow-mockup-generator' ) ),
	'room_err' => array( 'error', __( 'Could not generate the room mockup. Check your API key and try again.', 'pillow-mockup-generator' ) ),
	'room_set' => array( 'updated', __( 'Room image saved.', 'pillow-mockup-generator' ) ),
	'no_ref'   => array( 'error', __( 'Please choose a reference image first.', 'pillow-mockup-generator' ) ),
	'saved'    => array( 'updated', __( 'Settings saved.', 'pillow-mockup-generator' ) ),
);
?>
<div class="wrap pmg-wrap">
	<h1><?php esc_html_e( 'Room Mockup (Lab) — experimental', 'pillow-mockup-generator' ); ?></h1>

	<?php if ( isset( $pmg_notices[ $notice ] ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $pmg_notices[ $notice ][0] ); ?> is-dismissible"><p><?php echo esc_html( $pmg_notices[ $notice ][1] ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! PMG_Settings::is_configured() ) : ?>
		<div class="notice notice-warning"><p>
			<?php
			printf(
				/* translators: %s: settings link. */
				esc_html__( 'Add your OpenRouter API key in %s to generate the room mockup.', 'pillow-mockup-generator' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=pmg-settings' ) ) . '">' . esc_html__( 'Settings', 'pillow-mockup-generator' ) . '</a>'
			);
			?>
		</p></div>
	<?php endif; ?>

	<p class="pmg-hint">
		<?php esc_html_e( 'Experimental flow, separate from the live widget. Place it with the shortcode:', 'pillow-mockup-generator' ); ?>
		<code>[pillow_mockup_lab]</code>
	</p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . PMG_Lab::PAGE ) ); ?>">
		<?php wp_nonce_field( 'pmg_lab_action' ); ?>

		<h2><?php esc_html_e( '1. Living-room base', 'pillow-mockup-generator' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Reference image', 'pillow-mockup-generator' ); ?></th>
				<td>
					<input type="hidden" name="pmg_lab[ref_url]" value="<?php echo esc_attr( $pmg_ref_url ); ?>" data-lab-ref-url />
					<p>
						<button type="button" class="button" data-lab-pick><?php esc_html_e( 'Choose image', 'pillow-mockup-generator' ); ?></button>
					</p>
					<p class="description"><?php esc_html_e( 'Pick a room/sofa photo. The AI is image-to-image, so it needs a reference to transform into the room scene.', 'pillow-mockup-generator' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="pmg-lab-room-prompt"><?php esc_html_e( 'Room prompt', 'pillow-mockup-generator' ); ?></label></th>
				<td>
					<textarea id="pmg-lab-room-prompt" name="pmg_lab[room_prompt]" rows="4" class="large-text"><?php echo esc_textarea( (string) $lab['room_prompt'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Describe the empty living-room scene (no pillow on the sofa).', 'pillow-mockup-generator' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Generate', 'pillow-mockup-generator' ); ?></th>
				<td>
					<button type="submit" name="pmg_lab_generate" value="1" class="button button-primary"><?php esc_html_e( 'Generate room with AI', 'pillow-mockup-generator' ); ?></button>
					<button type="submit" name="pmg_lab_use" value="1" class="button"><?php esc_html_e( 'Use selected image as room', 'pillow-mockup-generator' ); ?></button>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( '2. Pillow cut-out prompt', 'pillow-mockup-generator' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="pmg-lab-cutout-prompt"><?php esc_html_e( 'Pillow prompt', 'pillow-mockup-generator' ); ?></label></th>
				<td>
					<textarea id="pmg-lab-cutout-prompt" name="pmg_lab[cutout_prompt]" rows="5" class="large-text"><?php echo esc_textarea( (string) $lab['cutout_prompt'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Sent with each uploaded photo. Ask the AI for a finished pillow isolated on a fully transparent background (PNG with alpha), with no room, surface or shadow, so it overlays cleanly on the room mockup.', 'pillow-mockup-generator' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( '3. Pillow placement & size', 'pillow-mockup-generator' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="pmg-lab-pos-x"><?php esc_html_e( 'Horizontal position (%)', 'pillow-mockup-generator' ); ?></label></th>
				<td><input type="number" step="0.5" min="0" max="100" id="pmg-lab-pos-x" name="pmg_lab[pos_x]" value="<?php echo esc_attr( (string) $lab['pos_x'] ); ?>" class="small-text" data-lab-input="pos_x" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="pmg-lab-pos-y"><?php esc_html_e( 'Vertical position (%)', 'pillow-mockup-generator' ); ?></label></th>
				<td><input type="number" step="0.5" min="0" max="100" id="pmg-lab-pos-y" name="pmg_lab[pos_y]" value="<?php echo esc_attr( (string) $lab['pos_y'] ); ?>" class="small-text" data-lab-input="pos_y" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="pmg-lab-base-width"><?php esc_html_e( 'Base width (% of room)', 'pillow-mockup-generator' ); ?></label></th>
				<td><input type="number" step="0.5" min="2" max="100" id="pmg-lab-base-width" name="pmg_lab[base_width]" value="<?php echo esc_attr( (string) $lab['base_width'] ); ?>" class="small-text" data-lab-input="base_width" /></td>
			</tr>
			<?php if ( ! empty( $sizes ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Scale per size', 'pillow-mockup-generator' ); ?></th>
					<td>
						<?php foreach ( $sizes as $pmg_size ) : ?>
							<?php $pmg_scale = isset( $lab['scales'][ $pmg_size['id'] ] ) ? $lab['scales'][ $pmg_size['id'] ] : 1; ?>
							<label style="display:inline-block;margin:0 16px 8px 0;">
								<strong><?php echo esc_html( $pmg_size['label'] ); ?></strong><br />
								<input type="number" step="0.05" min="0.1" max="5" name="pmg_lab[scales][<?php echo esc_attr( $pmg_size['id'] ); ?>]" value="<?php echo esc_attr( (string) $pmg_scale ); ?>" class="small-text" />
							</label>
						<?php endforeach; ?>
						<p class="description"><?php esc_html_e( 'Multiplier applied to the base width for each size button (e.g. 0.8 = smaller, 1.25 = larger).', 'pillow-mockup-generator' ); ?></p>
					</td>
				</tr>
			<?php endif; ?>
		</table>

		<?php if ( '' !== $pmg_preview_src ) : ?>
			<h2><?php esc_html_e( 'Preview', 'pillow-mockup-generator' ); ?></h2>
			<p class="description"><?php esc_html_e( 'The box shows the base placement and size of the pillow on the room.', 'pillow-mockup-generator' ); ?></p>
			<div class="pmg-lab-admin-preview" data-lab-preview style="position:relative;max-width:520px;border:1px solid #dcdcde;border-radius:8px;overflow:hidden;line-height:0;">
				<img src="<?php echo esc_url( $pmg_preview_src ); ?>" alt="" style="display:block;width:100%;height:auto;" data-lab-preview-img />
				<div data-lab-preview-box style="position:absolute;left:<?php echo esc_attr( (string) $lab['pos_x'] ); ?>%;top:<?php echo esc_attr( (string) $lab['pos_y'] ); ?>%;width:<?php echo esc_attr( (string) $lab['base_width'] ); ?>%;aspect-ratio:1/1;transform:translate(-50%,-50%);border:2px dashed #2271b1;background:rgba(34,113,177,0.18);border-radius:6px;"></div>
			</div>
		<?php endif; ?>

		<p class="submit">
			<button type="submit" name="pmg_lab_save" value="1" class="button button-primary"><?php esc_html_e( 'Save layout', 'pillow-mockup-generator' ); ?></button>
		</p>
	</form>
</div>

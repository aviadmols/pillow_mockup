<?php
/**
 * Sends notification emails to the admin and confirmation emails to the customer.
 *
 * @package PillowMockupGenerator
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PMG_Emailer
 */
class PMG_Emailer {

	/**
	 * Resolve the admin recipient address.
	 *
	 * @return string
	 */
	protected static function admin_recipient() {
		$email = sanitize_email( (string) PMG_Settings::get( 'admin_email', get_option( 'admin_email' ) ) );
		if ( ! is_email( $email ) ) {
			$email = get_option( 'admin_email' );
		}
		return $email;
	}

	/**
	 * HTML email headers.
	 *
	 * @return array
	 */
	protected static function headers() {
		return array( 'Content-Type: text/html; charset=UTF-8' );
	}

	/**
	 * Notify the admin a new lead was captured.
	 *
	 * @param array $lead Lead row.
	 * @return bool
	 */
	public static function notify_admin_new_lead( array $lead ) {
		$subject = (string) PMG_Settings::get( 'admin_subject', 'New pillow mockup lead' );
		$body    = self::lead_html( $lead, __( 'A new lead was captured from the pillow mockup widget.', 'pillow-mockup-generator' ) );
		return wp_mail( self::admin_recipient(), $subject, $body, self::headers() );
	}

	/**
	 * Notify the admin a lead finalized their selection (includes print-ready file).
	 *
	 * @param array $lead Lead row.
	 * @return bool
	 */
	public static function notify_admin_finalized( array $lead ) {
		$subject = sprintf(
			/* translators: %s: customer name. */
			__( 'Pillow order ready for print — %s', 'pillow-mockup-generator' ),
			$lead['name'] ? $lead['name'] : $lead['email']
		);
		$body = self::lead_html( $lead, __( 'A customer selected their final design. The print-ready cut-out is attached below.', 'pillow-mockup-generator' ), true );
		return wp_mail( self::admin_recipient(), $subject, $body, self::headers() );
	}

	/**
	 * Notify the admin that a customer placed an order (finalized their choice).
	 * Sent immediately on finalize; the print-ready cut-out is produced later.
	 *
	 * @param array $lead Lead row.
	 * @return bool
	 */
	public static function notify_admin_order( array $lead ) {
		$subject = sprintf(
			/* translators: %s: customer name. */
			__( 'New pillow order — %s', 'pillow-mockup-generator' ),
			$lead['name'] ? $lead['name'] : $lead['email']
		);
		$body = self::lead_html( $lead, __( 'A customer placed an order and selected their final design.', 'pillow-mockup-generator' ) );
		return wp_mail( self::admin_recipient(), $subject, $body, self::headers() );
	}

	/**
	 * Send the customer confirmation email.
	 *
	 * @param array $lead Lead row.
	 * @return bool
	 */
	public static function notify_customer( array $lead ) {
		$to = sanitize_email( $lead['email'] );
		if ( ! is_email( $to ) ) {
			return false;
		}
		$subject = (string) PMG_Settings::get( 'customer_subject', 'We received your request' );
		$message = (string) PMG_Settings::get( 'customer_message', '' );

		$body  = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#222;">';
		$body .= '<p>' . nl2br( esc_html( $message ) ) . '</p>';
		if ( ! empty( $lead['size'] ) || ( isset( $lead['price'] ) && '' !== $lead['price'] && null !== $lead['price'] ) ) {
			$price_currency = (string) PMG_Settings::get( 'price_currency', '₪' );
			$summary        = array();
			if ( ! empty( $lead['size'] ) ) {
				$summary[] = esc_html( $lead['size'] );
			}
			if ( isset( $lead['price'] ) && '' !== $lead['price'] && null !== $lead['price'] ) {
				$summary[] = esc_html( $price_currency . ' ' . number_format( (float) $lead['price'], 2 ) );
			}
			$body .= '<p style="font-weight:bold;">' . implode( ' — ', $summary ) . '</p>';
		}
		if ( ! empty( $lead['mockup_image'] ) ) {
			$body .= '<p><img src="' . esc_url( $lead['mockup_image'] ) . '" alt="" style="max-width:420px;width:100%;height:auto;border-radius:8px;" /></p>';
		}
		$body .= '<p style="color:#888;font-size:13px;">' . esc_html( get_bloginfo( 'name' ) ) . '</p>';
		$body .= '</div>';

		return wp_mail( $to, $subject, $body, self::headers() );
	}

	/**
	 * Build the HTML body describing a lead.
	 *
	 * @param array  $lead        Lead row.
	 * @param string $intro       Intro sentence.
	 * @param bool   $show_cutout Whether to include the cut-out image.
	 * @return string
	 */
	protected static function lead_html( array $lead, $intro, $show_cutout = false ) {
		$currency = (string) PMG_Settings::get( 'currency', '$' );
		$rows     = array(
			__( 'Name', 'pillow-mockup-generator' )     => $lead['name'],
			__( 'Email', 'pillow-mockup-generator' )    => $lead['email'],
			__( 'Phone', 'pillow-mockup-generator' )    => $lead['phone'],
			__( 'Status', 'pillow-mockup-generator' )   => $lead['status'],
		);

		if ( ! empty( $lead['address'] ) ) {
			$rows[ __( 'Address', 'pillow-mockup-generator' ) ] = $lead['address'];
		}
		if ( ! empty( $lead['apartment'] ) ) {
			$rows[ __( 'Apartment / suite', 'pillow-mockup-generator' ) ] = $lead['apartment'];
		}
		if ( ! empty( $lead['city'] ) ) {
			$rows[ __( 'City', 'pillow-mockup-generator' ) ] = $lead['city'];
		}
		if ( ! empty( $lead['state'] ) ) {
			$rows[ __( 'State', 'pillow-mockup-generator' ) ] = $lead['state'];
		}
		if ( ! empty( $lead['zip'] ) ) {
			$rows[ __( 'ZIP code', 'pillow-mockup-generator' ) ] = $lead['zip'];
		}

		if ( ! empty( $lead['size'] ) ) {
			$rows[ __( 'Size', 'pillow-mockup-generator' ) ] = $lead['size'];
		}
		if ( isset( $lead['price'] ) && '' !== $lead['price'] && null !== $lead['price'] ) {
			$price_currency = (string) PMG_Settings::get( 'price_currency', '₪' );
			$rows[ __( 'Order price', 'pillow-mockup-generator' ) ] = $price_currency . ' ' . number_format( (float) $lead['price'], 2 );
		}

		$rows[ __( 'Attempts', 'pillow-mockup-generator' ) ] = $lead['attempts'];
		$rows[ __( 'AI cost', 'pillow-mockup-generator' ) ]  = $currency . number_format( (float) $lead['total_cost'], 4 );

		$html  = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#222;">';
		$html .= '<p>' . esc_html( $intro ) . '</p>';
		$html .= '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;">';
		foreach ( $rows as $label => $value ) {
			$html .= '<tr><td style="font-weight:bold;border-bottom:1px solid #eee;">' . esc_html( $label ) . '</td>';
			$html .= '<td style="border-bottom:1px solid #eee;">' . esc_html( (string) $value ) . '</td></tr>';
		}
		$html .= '</table>';

		$images = array(
			__( 'Original photo', 'pillow-mockup-generator' )  => $lead['original_image'],
			__( 'Selected mockup', 'pillow-mockup-generator' ) => $lead['mockup_image'],
		);
		if ( $show_cutout ) {
			$images[ __( 'Print-ready cut-out', 'pillow-mockup-generator' ) ] = $lead['cutout_image'];
		}

		$html .= '<div style="margin-top:16px;">';
		foreach ( $images as $caption => $url ) {
			if ( empty( $url ) ) {
				continue;
			}
			$html .= '<div style="display:inline-block;margin:0 12px 12px 0;text-align:center;">';
			$html .= '<div style="font-size:12px;color:#777;margin-bottom:4px;">' . esc_html( $caption ) . '</div>';
			$html .= '<a href="' . esc_url( $url ) . '"><img src="' . esc_url( $url ) . '" alt="" style="max-width:240px;width:100%;height:auto;border:1px solid #eee;border-radius:8px;" /></a>';
			$html .= '</div>';
		}
		$html .= '</div></div>';

		return $html;
	}
}

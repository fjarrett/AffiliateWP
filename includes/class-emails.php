<?php

class Affiliate_WP_Emails {

	public function __construct() {

		add_action( 'affwp_register_user', array( $this, 'notify_on_registration' ), 10, 3 );
		add_action( 'affwp_set_affiliate_status', array( $this, 'notify_on_approval' ), 10, 3 );
		add_action( 'affwp_referral_accepted', array( $this, 'notify_of_new_referral' ), 10, 2 );

	}

	public function notify_on_registration( $affiliate_id = 0, $status = '', $args = array() ) {

		if ( affiliate_wp()->settings->get( 'registration_notifications' ) ) {
			affiliate_wp()->emails->notification( 'registration', array( 'affiliate_id' => $affiliate_id, 'name' => $args['display_name'] ) );
		}
	}

	public function notify_on_approval( $affiliate_id = 0, $status = '', $old_status = '' ) {

		if ( 'active' != $status || 'pending' != $old_status ) {
			return;
		}

		affiliate_wp()->emails->notification( 'application_accepted', array( 'affiliate_id' => $affiliate_id ) );
	}

	public function notify_of_new_referral( $affiliate_id = 0, $referral ) {

		$user_id = affwp_get_affiliate_user_id( $affiliate_id );

		if ( ! get_user_meta( $user_id, 'affwp_referral_notifications', true ) ) {
			return;
		}

		affiliate_wp()->emails->notification( 'new_referral', array( 'affiliate_id' => $affiliate_id, 'amount' => $referral->amount ) );
	}

	public function notification( $type = '', $args = array() ) {

		if ( empty( $type ) ) {
			return false;
		}

		switch ( $type ) {

			case 'registration' :
				
				$settings = get_option( 'affwp_settings' );
				$email    = $this->get_admin_notification_emails();

				// subject
				$subject  = ! empty( $settings['admin_registration_subject'] ) ? wp_strip_all_tags( $settings['admin_registration_subject'], true ) : __( 'New Affiliate Registration', 'affiliate-wp' );
				$subject  = affwp_do_email_tags( $subject, $args );

				// message
				$message  = $this->get_email_body_header();
				$message  .= $this->get_registration_body( $args );
				$message  .= $this->get_email_body_footer();

				if ( affiliate_wp()->settings->get( 'require_approval' ) ) {
					$message .= sprintf( "\n\nReview pending applications: %s\n\n", admin_url( 'admin.php?page=affiliate-wp-affiliates&status=pending' ) );
				}
				
				$subject  = apply_filters( 'affwp_registration_subject', $subject, $args );
				$message  = apply_filters( 'affwp_registration_email', $message, $args );

				break;

			case 'application_accepted' :

				$email    = affwp_get_affiliate_email( $args['affiliate_id'] );
				$subject  = __( 'Affiliate Application Accepted', 'affiliate-wp' );
				$message  = sprintf( __( "Congratulations %s!\n\n", "affiliate-wp" ), affiliate_wp()->affiliates->get_affiliate_name( $args['affiliate_id'] ) );
				$message .= sprintf( __( "Your affiliate application on %s has been accepted!\n\n", "affiliate-wp" ), home_url() );
				$message .= sprintf( __( "Log into your affiliate area at %s\n\n", "affiliate-wp" ), affiliate_wp()->login->get_login_url() );

				$subject = apply_filters( 'affwp_application_accepted_subject', $subject, $args );
				$message = apply_filters( 'affwp_application_accepted_email', $message, $args );

				break;

			case 'new_referral' :

				$email    = affwp_get_affiliate_email( $args['affiliate_id'] );
				$subject  = __( 'Referral Awarded!', 'affiliate-wp' );
				$amount   = html_entity_decode( affwp_currency_filter( $args['amount'] ), ENT_COMPAT, 'UTF-8' );
				$message  = sprintf( __( "Congratulations %s!\n\n", "affiliate-wp" ), affiliate_wp()->affiliates->get_affiliate_name( $args['affiliate_id'] ) );
				$message .= sprintf( __( "You have been awarded a new referral of %s on %s!\n\n", "affiliate-wp" ), $amount, home_url() );
				$message .= sprintf( __( "Log into your affiliate area to view your earnings or disable these notifications: %s\n\n", "affiliate-wp" ), affiliate_wp()->login->get_login_url() );

				$subject = apply_filters( 'affwp_new_referral_subject', $subject, $args );
				$message = apply_filters( 'affwp_new_referral_email', $message, $args );

				break;

		}

		$this->send( $email, $subject, $message );
	}

	public function send( $email, $subject, $message ) {
		$settings   = get_option( 'affwp_settings' );
		$from_email = isset( $settings['from_email'] ) ? $settings['from_email'] : get_option('admin_email');

		$headers    = array();
		$headers[]  = 'From: ' . stripslashes_deep( html_entity_decode( get_bloginfo( 'name' ), ENT_COMPAT, 'UTF-8' ) ) . ' <' . get_option( 'admin_email' ) . '>';
		$headers[]  = 'Reply-To: ' . $from_email . "\r\n";
		$headers[]  = "Content-Type: text/html; charset=utf-8\r\n";
		$headers    = apply_filters( 'affwp_email_headers', $headers );

		wp_mail( $email, $subject, $message, $headers );

	}

	/**
	 * Default Registration Body
	 *
	 * @since 1.2
	 * @return string $email_body Body of the email
	 */
	public function get_registration_body( $args ) {
		$settings = get_option( 'affwp_settings' );

		$default_email_body  = "A new affiliate has registered on your site, " . home_url() ."\n\n";
		$default_email_body .= sprintf( __( 'Name: %s', 'affiliate-wp' ), $args['name'] ) . "\n\n";
		$default_email_body .= "{sitename}";

		$email = isset( $settings['admin_registration'] ) ? stripslashes( $settings['admin_registration'] ) : $default_email_body;

		$email_body = affwp_do_email_tags( $email, $args );

		return apply_filters( 'affwp_default_registration_email', wpautop( $email_body ) );
	}

	/**
	 * Retrieves the emails for which admin notifications are sent to (these can be
	 * changed in the AffiliateWP Settings)
	 *
	 * @since 1.2
	 * @return mixed
	 */
	public function get_admin_notification_emails() {
		$settings = get_option( 'affwp_settings' );

		$emails = isset( $settings['admin_notification_emails'] ) && strlen( trim( $settings['admin_notification_emails'] ) ) > 0 ? $settings['admin_notification_emails'] : get_option( 'admin_email' );
		$emails = array_map( 'trim', explode( "\n", $emails ) );

		return apply_filters( 'affwp_admin_notification_emails', $emails );
	}

	/**
	 * Email Template Header
	 *
	 * @since 1.2
	 * @return string Email template header
	 */
	public function get_email_body_header() {
		ob_start();
		?>
		<html>
		<head>
			<style type="text/css">#outlook a { padding: 0; }</style>
		</head>
		<body dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
		<?php
		do_action( 'affwp_email_body_header' );
		return ob_get_clean();
	}

	/**
	 * Email Template Footer
	 *
	 * @since 1.2
	 * @return string Email template footer
	 */
	public function get_email_body_footer() {
		ob_start();
		do_action( 'affwp_email_body_footer' );
		?>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

}
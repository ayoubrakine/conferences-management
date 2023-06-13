<?php
/**
 * Configure Email
 *
 * @package  User_Registration_Settings_Email_Verified_Admin_Email
 * @extends  UR_Settings_Email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'User_Registration_Settings_Email_Verified_Admin_Email', false ) ) :

	/**
	 * User_Registration_Settings_Email_Verified_Admin_Email Class.
	 */
	class User_Registration_Settings_Email_Verified_Admin_Email {
		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id          = 'email_verified_admin_email';
			$this->title       = __( 'Email Verified - Awaiting Admin Approval Email', 'user-registration' );
			$this->description = __( 'Email sent to the admin when a user confirmed his/her email', 'user-registration' );
		}

		/**
		 * Get settings
		 *
		 * @return array
		 */
		public function get_settings() {

			$settings = apply_filters(
				'user_registration_pro_email_verified_admin_email',
				array(
					'title' => __( 'Emails', 'user-registration' ),
					'sections' => array(
						'admin_email' => array(
							'title' => __( 'Email Verified - Awaiting Admin Approval Email', 'user-registration' ),
							'type'  => 'card',
							'desc'  => '',
							'back_link' => ur_back_link( __( 'Return to emails', 'user-registration' ), admin_url( 'admin.php?page=user-registration-settings&tab=email' ) ),
							'settings' => array(
								array(
									'title'    => __( 'Enable this email', 'user-registration' ),
									'desc'     => __( 'Enable this email to send to admin requesting admin approval after user has successfully confirmed email.', 'user-registration' ),
									'id'       => 'user_registration_enable_email_verified_admin_email',
									'default'  => 'yes',
									'type'     => 'checkbox',
									'autoload' => false,
								),
								array(
									'title'    => __( 'Email Receipents', 'user-registration' ),
									'desc'     => __( 'Use comma to send emails to multiple receipents.', 'user-registration' ),
									'id'       => 'user_registration_pro_email_verified_admin_email_receipents',
									'default'  => get_option( 'admin_email' ),
									'type'     => 'text',
									'css'      => 'min-width: 350px;',
									'autoload' => false,
									'desc_tip' => true,
								),
								array(
									'title'    => __( 'Email Subject', 'user-registration' ),
									'desc'     => __( 'The email subject you want to customize.', 'user-registration' ),
									'id'       => 'user_registration_pro_email_verified_admin_email_subject',
									'type'     => 'text',
									'default'  => __( 'User email confirmed awaiting admin approval', 'user-registration' ),
									'css'      => 'min-width: 350px;',
									'desc_tip' => true,
								),
								array(
									'title'    => __( 'Email Content', 'user-registration' ),
									'desc'     => __( 'The email content you want to customize.', 'user-registration' ),
									'id'       => 'user_registration_pro_email_verified_admin_email',
									'type'     => 'tinymce',
									'default'  => $this->ur_get_email_verified_admin_email(),
									'css'      => 'min-width: 350px;',
									'desc_tip' => true,
								),
							),
						),
					),
				)
			);

			return apply_filters( 'user_registration_get_settings_' . $this->id, $settings );
		}

		/**
		 * Email format.
		 */
		public function ur_get_email_verified_admin_email() {

			$message = apply_filters(
				'user_registration_pro_email_verified_admin_email_message',
				sprintf(
					__(
						'Hi Admin,<br/>
						The user {{username}} has successfully verified the email address - {{email}}.<br/>
						Please review the user role and details from the \'<b>Users</b>\' menu in your WP dashboard and approve accordingly.<br/>
						Thank You!',
						'user-registration'
					)
				)
			);

			return $message;
		}
	}
endif;

return new User_Registration_Settings_Email_Verified_Admin_Email();

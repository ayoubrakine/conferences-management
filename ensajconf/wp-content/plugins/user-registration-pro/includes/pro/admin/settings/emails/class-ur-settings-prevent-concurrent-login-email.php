<?php
/**
 * Configure Email
 *
 * @class   User_Registration_Settings_Prevent_Concurrent_Email
 * @extends  User_Registration_Settings_Email
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'User_Registration_Settings_Prevent_Concurrent_Login_Email', false ) ) :

	/**
	 * User_Registration_Settings_Prevent_Concurrent_Email Class.
	 */
	class User_Registration_Settings_Prevent_Concurrent_Login_Email {

		public function __construct() {
			$this->id          = 'prevent_concurrent_login_email';
			$this->title       = esc_html__( 'Prevent Concuurent Login Email', 'user-registration' );
			$this->description = esc_html__( 'Email sent to the user to force logout from the all devices', 'user-registration' );
		}

		/**
		 * Get settings
		 *
		 * @return array
		 */
		public function get_settings() {

			$settings = apply_filters(
				'user_registration_prevent_concurrent_login_email',
				array(
					'title' => __( 'Emails', 'user-registration' ),
					'sections' => array (
						'prevent_concurrent_email' => array(
							'title' => __( 'Prevent Concurrent Login Email', 'user-registration' ),
							'type'  => 'card',
							'desc'  => '',
							'back_link' => ur_back_link( __( 'Return to emails', 'user-registration' ), admin_url( 'admin.php?page=user-registration-settings&tab=email' ) ),
							'settings' => array(
								array(
									'title'    => __( 'Enable this email', 'user-registration' ),
									'desc'     => __( 'Enable this email sent to the user after succesfully email sent', 'user-registration' ),
									'id'       => 'user_registration_enable_prevent_concurrent_login_email',
									'default'  => 'yes',
									'type'     => 'checkbox',
									'autoload' => false,
								),
								array(
									'title'    => __( 'Email Subject', 'user-registration' ),
									'desc'     => __( 'The email subject you want to customize.', 'user-registration' ),
									'id'       => 'user_registration_prevent_concurrent_login_email_subject',
									'type'     => 'text',
									'default'  => __( 'Force logout', 'user-registration' ),
									'css'      => 'min-width: 350px;',
									'desc_tip' => true,
								),
								array(
									'title'    => __( 'Email Content', 'user-registration' ),
									'desc'     => __( 'The email content you want to customize.', 'user-registration' ),
									'id'       => 'user_registration_prevent_concurrent_login_email_content',
									'type'     => 'tinymce',
									'default'  => $this->user_registration_get_prevent_concurrent_login_email(),
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
		 * Email Format.
		 */
		public static function user_registration_get_prevent_concurrent_login_email() {

			$message = apply_filters(
				'user_registration_reset_password_email_message',
				sprintf(
					__(
						'Someone has requested a force logout for the following account: <br/>
If this was a mistake, just ignore this email and nothing will happen. <br/>
To force logout, visit the following address: <br/>
<a href="{{home_url}}/{{ur_login}}?action=force-logout&login={{user_id}}" rel="noreferrer noopener" target="_blank">Click Here</a><br/>
Thank You!',
						'user-registration'
					)
				)
			);

			return $message;
		}
	}
endif;

return new User_Registration_Settings_Prevent_Concurrent_Login_Email();

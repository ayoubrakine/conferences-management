<?php
/**
 * Configure Redirection After Registration
 *
 * @class    User_Registration_Settings_Redirection_After_Registration
 * @extends  User_Registration_Pro_Settings
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'User_Registration_Settings_Redirection_After_Registration', false ) ) :

	/**
	 * User_Registration_Settings_Redirection_After_Registration Class.
	 */
	class User_Registration_Settings_Redirection_After_Registration {

		public function __construct() {
			$this->id          = 'redirection_after_registration';
			$this->title       = esc_html__( 'Redirection After Registration', 'user-registration' );
			$this->description = esc_html__( 'Redirect users to specific pages after registration based on role', 'user-registration' );
		}

		/**
		 * Get settings
		 *
		 * @return array
		 */
		public function get_settings() {

			$settings = apply_filters(
				'user_registration_redirection_after_registration',
				array(
					'title' => __( 'Role Based Redirection', 'user-registration' ),
					'sections' => array (
						'redirection_after_registration' => array(
							'title' => __( 'Redirection After Registration Settings', 'user-registration' ),
							'type'  => 'card',
							'desc'  => '',
							'back_link' => ur_back_link( __( 'Return to Redirection Type', 'user-registration' ), admin_url( 'admin.php?page=user-registration-settings&tab=misc&section=role-based-redirection' ) ),
							'settings' => array(

								array(
									'id'       => 'user_registration_pro_redirecttion_after_registration',
									'type'     => 'redirection_after_registration',
								),
							),
						),
					),
				)
			);
			add_filter( 'user_registration_admin_field_redirection_after_registration', array( $this, 'redirection_after_registration' ), 10, 2 );
			return apply_filters( 'user_registration_get_settings_' . $this->id, $settings );
		}

		/**
		 * Add Role to Page Mapper for redirection after registration.
		 *
		 * @param string $settings Settings template for role to page mapper.
		 */
		public function redirection_after_registration( $settings ) {

		    $settings .= '<tr valign="top">';
			$settings .= '<td class="ur_emails_wrapper" colspan="2">';
			$settings .= '<table class="ur_emails widefat" cellspacing="0">';
			$settings .= '<tbody>';

			$selected_roles_pages = get_option( 'ur_pro_settings_redirection_after_registration', array() );

			foreach ( ur_get_default_admin_roles() as $key => $value ) {
				$settings .= '<tr><td class="ur-email-settings-table">';
				$settings .=  __( $value, 'user-registration' );
				$settings .= '</td>';
				$settings .= '<td class="ur-email-settings-table">';
				$settings .= '<select name="'.$key.'" id="'.$key.'" >';
				$pages = get_pages();
				$settings .= '<option value="" >---Select a page---</option>';

				foreach ( $pages as $page ) {

					if ( !empty( $selected_roles_pages ) && $selected_roles_pages[$key] === $page->ID ) {
						$selected ='selected=selected';
					} else {
						$selected = '';
					}
					$settings .= '<option value="'.$page->ID.'" '.$selected.' >'.$page->post_title.'</option>';
				}
				$settings .= '</select>';
				$settings .= '</td>';
				$settings .= '</tr>';
			}

			$settings .= '</tbody>';
			$settings .= '</table>';
			$settings .= '</td>';
			$settings .= '</tr>';

			return $settings;
		}
	}
endif;

return new User_Registration_Settings_Redirection_After_Registration();

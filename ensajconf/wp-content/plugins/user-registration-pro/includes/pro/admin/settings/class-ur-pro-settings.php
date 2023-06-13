<?php
/**
 * UserRegistration Pro Settings class.
 *
 * @version  1.0.0
 * @package  UserRegistration/Admin
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'User_Registration_Pro_Settings' ) ) :

	/**
	 * User_Registration_Pro_Settings Setting
	 */
	class User_Registration_Pro_Settings extends UR_Settings_Page {

		/**
		 * Redirect class
		 */
		 public $redirect_type = array();

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'user-registration-pro';
			$this->label = esc_html__( 'Misc', 'user-registration' );

			add_filter( 'wp_editor_settings', array( $this, 'user_registration_pro_editor_settings' ) );
			add_filter( 'show_user_registration_setting_message', array( $this, 'filter_notice' ) );
			add_filter( 'user-registration-setting-save-label', array( $this, 'filter_label' ) );
			add_filter( 'user_registration_admin_field_role_redirect_settings', array( $this, 'ur_role_based_redirection_mapping_table' ), 10, 2 );
			$this->redirect_type['User_Registration_Settings_Redirection_After_Login'] = include 'redirect/class-ur-settings-redirection-after-login.php';
			$this->redirect_type['User_Registration_Settings_Redirection_After_Registration'] = include 'redirect/class-ur-settings-redirection-after-registeration.php';
			$this->redirect_type['User_Registration_Settings_Redirection_After_Logout'] = include 'redirect/class-ur-settings-redirection-after-logout.php';
			add_filter( 'ur_pro_settings_redirection_after_login', array( $this, 'save_custom_options' ), 10, 2 );
			add_filter( 'ur_pro_settings_redirection_after_logout', array( $this, 'save_custom_options' ), 10, 2 );
			add_filter( 'ur_pro_settings_redirection_after_registration', array( $this, 'save_custom_options' ), 10, 2 );

			// Hooks to return data to ur core.
			add_filter( 'user_registration_get_sections_misc', array( $this, 'add_misc_sections' ), 10, 1 );
			add_filter( 'user_registration_get_output_settings_misc', array( $this, 'add_misc_settings' ), 10, 1 );
			add_filter( 'user_registration_get_save_settings_misc', array( $this, 'add_misc_save_settings' ), 10, 1 );
		}

		/**
		 * Add Popups and Role Based Redirection tabs to Misc Option
		 */
		public function add_misc_sections( $sections ) {
			$sections['popups'] = __( 'Popups', 'user-registration' );
			$sections['role-based-redirection'] = __( 'Role based Redirection', 'user-registration' );

			return apply_filters( 'user_registration_get_sections_' . $this->id, $sections );
		}

		/**
		 * Return Settings array for added tabs
		 */
		public function add_misc_settings( $settings ) {

			global $current_section;

			switch ( $current_section ) {
				case '':
					break;
				case 'popups':
					$GLOBALS['hide_save_button'] = true;
					// Add screen option.
					add_screen_option(
						'per_page',
						array(
							'default' => 20,
							'option'  => 'user_registration_pro_popups_per_page',
						)
					);

					if ( isset( $_REQUEST['success'] ) ) {
						if ( get_option( 'ur-popup-created' ) ) {
							echo '<div id="message" class="inline updated"><p><strong>' . __( 'Popup successfully generated.', 'user-registration' ) . '</strong></p></div>';
							update_option( 'ur-popup-created', false );
						}
						if ( get_option( 'ur-popup-edited' ) ) {
							echo '<div id="message" class="inline updated"><p><strong>' . __( 'Popup successfully updated.', 'user-registration' ) . '</strong></p></div>';
							update_option( 'ur-popup-edited', false );
						}
					}
					echo '</form>';
					User_Registration_Pro_Admin::user_registration_pro_popup_list_table_output();
					$settings = array();
					break;
				case 'add-new-popup':
					$handle_action = user_registration_pro_popup_settings_handler();

					if ( $handle_action === true ) {

						if ( ! isset( $_REQUEST['edit-popup'] ) ) {
							$success = 'popup-created';

						} else {
							$success = 'popup-edited';

						}
						wp_redirect( admin_url( 'admin.php?page=user-registration-settings&tab=misc&section=popups&success=' . $success ) );
					}

					$settings = $this->get_add_new_popup_settings();
					break;
				case 'role-based-redirection':
					$settings = $this->get_role_based_redirection_settings();

					break;

				case 'ur_settings_redirection_after_login':
					$redirection_settings = new User_Registration_Settings_Redirection_After_Login();
					$settings = $redirection_settings->get_settings();
					break;

				case 'ur_settings_redirection_after_registration':
					$redirection_settings = new User_Registration_Settings_Redirection_After_Registration();
					$settings = $redirection_settings->get_settings();
					break;

				case 'ur_settings_redirection_after_logout':
					$redirection_settings = new User_Registration_Settings_Redirection_After_Logout();
					$settings = $redirection_settings->get_settings();
					break;
			}

			return $settings;
		}

		/**
		 * Return settings to save function
		 */
		public function add_misc_save_settings( $settings ) {
			global $current_section;

			$is_custom_option = false;
			$option_name = '';
			$option_value = array();
			$redirect_type = $this->get_redirect_type();

			foreach ( $redirect_type as $type ) {

				if ( $current_section === 'ur_settings_' . $type->id ) {
					$is_custom_option = true;
					$option_name = 'ur_pro_settings_' . $type->id;
					$option_value = apply_filters( $option_name, $type->id, $option_value );
				}
			}

			// Check current section and handle save action accordingly.
			if ( 'add-new-popup' === $current_section ) {
				$settings = $this->get_add_new_popup_settings();
			} elseif ( 'role-based-redirection' === $current_section ) {
				$settings = $this->get_role_based_redirection_settings();
			}
			$settings = isset( $settings ) ? $settings : $this->get_settings();

			if ( ! $is_custom_option ) {
				return $settings;
			} else {
				update_option( $option_name, $option_value );
				return array();
			}
		}

		public function get_redirect_type() {
			return $this->redirect_type;
		}

		/**
		 * Change tinymce editor settings.
		 *
		 * @param  array $settings All settings.
		 * @return mixed
		 */
		public function user_registration_pro_editor_settings( $settings ) {

			// Check if the tab is of user registration pro addon and handle text editor separately.
			if ( isset( $_GET['tab'] ) && 'user-registration-pro' === $_GET['tab'] ) {
				$settings['media_buttons'] = false;
				$settings['textarea_rows'] = 4;
			}
			return $settings;
		}

		/**
		 * Get Add New Popup Settings.
		 *
		 * @return array.
		 */
		public function get_add_new_popup_settings() {
			$all_forms = ur_get_all_user_registration_form();

			$popup_id = isset( $_REQUEST['edit-popup'] ) ? $_REQUEST['edit-popup'] : '';

			$args   = array(
				'post_type'   => 'ur_pro_popup',
				'post_status' => array( 'publish', 'trash' ),
			);
			$popups = new WP_Query( $args );

			foreach ( $popups->posts as  $item ) {

				if ( $popup_id == $item->ID ) {
					$popup_content = json_decode( $item->post_content );
				}
			}

			$popup_type = array(
				'registration' => 'Registration',
				'login'        => 'Login',
			);

			$header_title = '';
			if ( isset( $popup_content ) ) {
				$header_title = sprintf( __( '%s', 'user-registration' ), ucfirst( $popup_content->popup_title ) );
			} else {
				$header_title = __( 'Add new Popup', 'user-registration' );
			}

			$settings = apply_filters(
				'user_registration_get_add_new_popup_settings',
				array(
					'title'          => esc_html( $header_title ),
					'back_link'      => esc_url( admin_url( 'admin.php?page=user-registration-settings&tab=misc&section=popups' ) ),
					'back_link_text' => __( 'Back to all Popups', 'user-registration' ),
					'sections'       => array(
						'edit_popup_display_settings' => array(
							'title'    => __( 'Display Popup', 'user-registration' ),
							'type'     => 'card',
							'desc'     => '',
							'settings' => array(
								array(
									'title'    => __( 'Enable this popup', 'user-registration' ),
									'desc'     => __( 'Enable', 'user-registration' ),
									'id'       => 'user_registration_pro_enable_popup',
									'type'     => 'checkbox',
									'desc_tip' => __( 'Check to enable popup.', 'user-registration' ),
									'css'      => 'min-width: 350px;',
									'default'  => isset( $popup_content ) && 1 == $popup_content->popup_status ? 'yes' : 'no',
								),
								array(
									'title'    => __( 'Select popup type', 'user-registration' ),
									'desc'     => __( 'Select either the popup is registration or login type.', 'user-registration' ),
									'id'       => 'user_registration_pro_popup_type',
									'type'     => 'select',
									'class'    => 'ur-enhanced-select user-registration-pro-select-popup-type',
									'css'      => 'min-width: 350px;',
									'desc_tip' => true,
									'options'  => $popup_type,
									'default'  => isset( $popup_content ) ? $popup_content->popup_type : array_values( $popup_type )[0],
								),
							),
						),
						'edit_popup_content'          => array(
							'title'    => __( 'Popup Content', 'user-registration' ),
							'type'     => 'card',
							'desc'     => '',
							'settings' => array(
								array(
									'title'    => __( 'Popup Name', 'user-registration' ),
									'desc'     => __( 'Enter the title of popup.', 'user-registration' ),
									'id'       => 'user_registration_pro_popup_title',
									'type'     => 'text',
									'css'      => 'min-width: 350px;',
									'desc_tip' => true,
									'default'  => isset( $popup_content ) ? $popup_content->popup_title : '',
								),
								array(
									'title'    => __( 'Popup Header Content', 'user-registration' ),
									'desc'     => __( 'Here you can put header content.', 'user-registration' ),
									'id'       => 'user_registration_pro_popup_header_content',
									'type'     => 'tinymce',
									'default'  => isset( $popup_content ) ? $popup_content->popup_header : '',
									'css'      => 'min-width: 350px;',
									'desc_tip' => true,
								),
								array(
									'title'     => __( 'Select form', 'user-registration' ),
									'desc'      => __( 'Select which registration form to render in popup.', 'user-registration' ),
									'id'        => 'user_registration_pro_popup_registration_form',
									'type'      => 'select',
									'row_class' => 'single-registration-select',
									'class'     => 'ur-enhanced-select user-registration-pro-select-registration-form',
									'css'       => 'min-width: 350px;',
									'desc_tip'  => true,
									'options'   => $all_forms,
									'default'   => isset( $popup_content->form ) ? $popup_content->form : array_values( $all_forms )[0],
								),
								array(
									'title'    => __( 'Popup Footer Content', 'user-registration' ),
									'desc'     => __( 'Here you can put footer content.', 'user-registration' ),
									'id'       => 'user_registration_pro_popup_footer_content',
									'type'     => 'tinymce',
									'default'  => isset( $popup_content ) ? $popup_content->popup_footer : '',
									'css'      => 'min-width: 350px;',
									'desc_tip' => true,
								),
							),
						),
						'edit_popup_appearance'       => array(
							'title'    => __( 'Popup Appearance', 'user-registration' ),
							'type'     => 'card',
							'desc'     => '',
							'settings' => array(
								array(
									'title'    => __( 'Select Popup Size', 'user-registration' ),
									'desc'     => __( 'Select which size of popup you want.', 'user-registration' ),
									'id'       => 'user_registration_pro_popup_size',
									'type'     => 'select',
									'class'    => 'ur-enhanced-select',
									'css'      => 'min-width: 350px;',
									'desc_tip' => true,
									'options'  => array(
										'default'     => 'Default',
										'large'       => 'Large',
										'extra_large' => 'Extra Large',
									),
									'default'  => isset( $popup_content->popup_size ) ? $popup_content->popup_size : 'default',
								),
							),
						),
					),
				)
			);

			return apply_filters( 'user_registration_get_add_new_popup_settings_' . $this->id, $settings );
		}

		/**
		 * Role based Redirection Settings.
		 *
		 * @since 3.0.0
		 * @return array.
		 */
		public function get_role_based_redirection_settings() {

			$settings = apply_filters(
				'user_registration_role_based_redirection_settings',
				array(
					'title' => __( 'Role Based Redirection', 'user-registration' ),
					'sections' => array(
						'role_based_redirection_settings'       => array(
							'title'    => __( 'Configure Role based Redirection', 'user-registration' ),
							'type'     => 'card',
							'desc'     => '',
							'settings' => array(
								array(
									'title'    => __( 'Enable Role based Redirection', 'user-registration' ),
									'desc'     => __( 'Handles role based redirection to a specific page after login or registration.', 'user-registration' ),
									'id'       => 'user_registration_pro_role_based_redirection',
									'type'     => 'checkbox',
									'css'      => 'min-width: 350px;',
									'default'  => 'no',
								),
								array(
									'type' => 'role_redirect_settings',
									'id' => 'user_registration_pro_role_based_redirection_settings',
								),
							),
						),
					),
				)
			);
			return apply_filters( 'user_registration_get_role_based_redirection_settings_' . $this->id, $settings );
		}

		/**
		 * Add Role Mapping table inside role based redirection settings page.
		 *
		 * @param [type] $settings
		 * @param [type] $option
		 */
		public function ur_role_based_redirection_mapping_table( $settings, $option ) {
			$settings .= '<tr valign="top">';
			$settings .= '<td class="ur_emails_wrapper" colspan="2">';
			$settings .= '<table class="ur_emails widefat" cellspacing="0">';
			$settings .= '<thead>';
			$settings .= '<tr>';

			$columns = apply_filters(
				'user_registration_role_redirect_setting_columns',
				array(
					'name'    => __( 'Redirection Type', 'user-registration' ),
					'actions' => __( 'Configure', 'user-registration' ),
				)
			);
			foreach ( $columns as $key => $column ) {
				$settings .= '<th style="padding-left:15px" class="ur-email-settings-table-' . esc_attr( $key ) . '">' . esc_html( $column ) . '</th>';
			}
			$settings .= '</tr>';
			$settings .= '</thead>';
			$settings .= '<tbody>';
			$redirect_type = $this->get_redirect_type();
			foreach ( $redirect_type as $type ) {
				$settings .= '<tr><td class="ur-email-settings-table">';
				$settings .= '<a href="' . admin_url( 'admin.php?page=user-registration-settings&tab=misc&section=ur_settings_' . $type->id . '' ) .
												'">' . __( $type->title, 'user-registration' ) . '</a>';
				$settings .= ur_help_tip( __( $type->description, 'user-registration' ) );
				$settings .= '</td>';
				$settings .= '<td class="ur-email-settings-table">';
				$settings .= '<a class="button tips" data-tip="' . esc_attr__( 'Configure', 'user-registration' ) . '" href="' . admin_url( 'admin.php?page=user-registration-settings&tab=misc&section=ur_settings_' . $type->id . '' ) . '"><span class="dashicons dashicons-admin-generic"></span> </a>';
				$settings .= '</td>';
				$settings .= '</tr>';
			}

			$settings .= '</tbody>';
			$settings .= '</table>';
			$settings .= '</td>';
			$settings .= '</tr>';

			return $settings;
		}

			/**
			 * Filter Notice for pro tab.
			 *
			 * @return bool
			 */
		public function filter_notice() {
			global $current_tab;

			if ( 'user-registration-pro' === $current_tab ) {
				return false;
			}

			return true;
		}

		/**
		 * Filter submit button label for certain tabs and sections.
		 *
		 * @param  string $label Label
		 * @return string        Label
		 */
		public function filter_label( $label ) {
			global $current_tab;
			global $current_section;

			if ( 'user-registration-pro' === $current_tab && 'add-new-popup' === $current_section ) {

				if ( ! isset( $_REQUEST['edit-popup'] ) ) {
					return __( 'Add Popup', 'user-registration' );
				} else {
					return __( 'Update Popup', 'user-registration' );
				}
			}

			return $label;
		}

		/**
		 * Save all custom options.
		 *
		 * @param string $section_id section ID.
		 * @param array  $option_value Option value
		 * @return array
		 */
		public function save_custom_options( $section_id, $option_value = array() ) {
			switch ( $section_id ) {
				case 'redirection_after_login':
				case 'redirection_after_logout':
				case 'redirection_after_registration':
					foreach ( ur_get_default_admin_roles() as $key => $value ) {
						$option_value[ $key ] = isset( $_POST[ $key ] ) ? wp_unslash( absint( $_POST[ $key ] ) ) : '';
					}
					break;
			}
			return $option_value;
		}
	}
	endif;
return new User_Registration_Pro_Settings();

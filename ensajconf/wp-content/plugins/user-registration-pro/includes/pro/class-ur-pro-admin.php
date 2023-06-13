<?php
/**
 * Admin class
 *
 * User_Registration_Pro Admin
 *
 * @package User_Registration_Pro
 * @since  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'User_Registration_Pro_Admin' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @since 1.0.0
	 */
	class User_Registration_Pro_Admin {

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_menu', array( $this, 'dashboard_menu' ), 14 );
			add_action( 'user_registration_auto_generate_password', array( $this, 'user_registration_pro_auto_generate_password' ) );
			add_filter( 'user_registration_success_params', array( $this, 'user_registration_after_register_mail' ), 10, 4 );

			add_filter( 'user_registration_email_classes', array( $this, 'get_emails' ), 10, 1 );
			// Frontend message settings.
			add_filter( 'user_registration_frontend_messages_settings', array( $this, 'add_auto_generated_password_frontend_message' ) );
			add_action( 'admin_init', array( $this, 'actions' ) );
			add_action( 'admin_print_scripts', array( $this, 'hide_unrelated_notices' ) );
			add_action( 'admin_head-nav-menus.php', array( $this, 'add_nav_menu_meta_boxes' ) );
			add_filter( 'user_registration_frontend_messages_settings', array( $this, 'add_email_suggestion_error_message' ) );

			// Set Email Templates settings in form builder.
			add_action( 'user_registration_after_form_settings', array( $this, 'render_pro_section' ) );
			add_filter( 'user_registration_form_settings_save', array( $this, 'save_pro_settings' ), 10, 2 );
			add_action( 'user_registration_after_form_settings_save', array( $this, 'save_pro_form_settings' ), 10, 1 );

			// Add admin settings.
			add_filter( 'user_registration_general_settings', array( $this, 'ur_pro_add_general_settings' ) );
			add_filter( 'user_registration_login_options_settings', array( $this, 'ur_pro_add_login_options_settings' ) );
			add_filter( 'user_registration_login_options', array( $this, 'add_admin_approval_after_email_confirmation_login_option' ) );
			add_filter( 'user_registration_advanced_settings', array( $this, 'ur_pro_add_advanced_settings' ) );

			add_action( 'user_registration_after_addons_description', array( $this, 'ur_pro_add_addons_page_footer' ) );
			add_action( 'admin_init', array( $this, 'addon_actions' ) );
			add_action( 'admin_init', array( $this, 'handle_backward_compatibility' ) );
			// auto populate setting in advance settings.
			$fields = user_registration_pro_auto_populate_supported_fields();
			foreach ( $fields as $field ) {
				add_filter( $field . '_custom_advance_settings', array( $this, 'ur_pro_auto_populate_advance_setting' ), 10, 3 );
			}

			add_filter( 'user_registration_multi_select2_field_advance_settings', array( $this, 'ur_pro_auto_populate_advance_setting' ), 10, 3 );
			add_filter( 'user_registration_timepicker_field_advance_settings', array( $this, 'ur_pro_auto_populate_advance_setting' ), 10, 3 );
			add_filter( 'user_registration_phone_field_advance_settings', array( $this, 'ur_pro_auto_populate_advance_setting' ), 10, 3 );
			add_filter( 'user_registration_select2_field_advance_settings', array( $this, 'ur_pro_auto_populate_advance_setting' ), 10, 3 );
			// Validate as unique field in advance settings.
			$field_type = array( 'nickname', 'display_name', 'first_name', 'last_name', 'text', 'user_url' );
			foreach ( $field_type as $field ) {
				add_filter( $field . '_custom_advance_settings', array( $this, 'ur_pro_validate_as_unique' ), 10, 3 );
			}
			add_filter( 'user_registration_phone_field_advance_settings', array( $this, 'ur_pro_validate_as_unique' ), 10, 3 );
			add_filter( 'user_registration_field_options_general_settings', array( $this, 'add_form_field_tooltip_options' ), 10, 2 );
			// Validate field as unique when admin update the user profile from admin users table.
			add_action( 'user_registration_after_admin_save_profile_validation', array( $this, 'validate_unique_field_profile_update_by_admin' ), 10, 2 );
			add_action( 'user_profile_update_errors', array( $this, 'check_unique_fields' ), 10, 3 );

			add_action( 'user_registration_custom_export_template', array( $this, 'display_custom_fields_options' ), 10, 1 );
			// Restrict copy,cut and paste on confirm email and confirm password fields.
			$restricted_fields = array( 'user_confirm_email', 'user_confirm_password' );
			foreach ( $restricted_fields as $field ) {
				add_filter( $field . '_custom_advance_settings', array( $this, 'ur_pro_restrict_copy_paste' ), 10, 3 );
			}
			add_action(
				'user_registration_after_admin_save_profile_validation',
				array( $this, 'user_registration_pro_sync_external_fields_after_admin_save_profile_validation' ),
				10,
				2
			);
		}

		 /**
		  * Sync External Field when update user from admin side.
		  *
		  * @param int   $user_id User ID.
		  * @param array $profile Form Details.
		  */
		public function user_registration_pro_sync_external_fields_after_admin_save_profile_validation( $user_id, $profile ) {

			if ( isset( $_POST['ur_user_user_status'] ) && 1 != $_POST['ur_user_user_status'] ) {
				return;
			}

			$form_id = ur_get_form_id_by_userid( $user_id );

			$valid_form_data = array();

			foreach ( $_POST as $post_key => $post_data ) {

				$pos = strpos( $post_key, 'user_registration_' );

				if ( false !== $pos ) {
					$new_string = substr_replace( $post_key, '', $pos, strlen( 'user_registration_' ) );

					if ( ! empty( $new_string ) ) {
						$valid_form_data[ $new_string ]               = new stdClass();
						$valid_form_data[ $new_string ]->value        = $post_data;
						$valid_form_data[ $new_string ]->field_type   = $profile[ $post_key ]['type'];
						$valid_form_data[ $new_string ]->label        = $profile[ $post_key ]['label'];
						$valid_form_data[ $new_string ]->field_name   = $new_string;
						$valid_form_data[ $new_string ]->extra_params = array(
							'field_key' => $profile[ $post_key ]['field_key'],
							'label'     => $profile[ $post_key ]['label'],
						);
					}
				} else {
					$key        = 'email' === $post_key ? 'user_email' : $post_key;
					$field_data = 'user_registration_' . $key;
					$data       = isset( $profile[ $field_data ] ) ? $profile[ $field_data ] : array();

					if ( ! empty( $data ) ) {
						$valid_form_data[ $key ]               = new stdClass();
						$valid_form_data[ $key ]->value        = $post_data;
						$valid_form_data[ $key ]->field_type   = $profile[ $field_data ]['type'];
						$valid_form_data[ $key ]->label        = $profile[ $field_data ]['label'];
						$valid_form_data[ $key ]->field_name   = $key;
						$valid_form_data[ $key ]->extra_params = array(
							'field_key' => $profile[ $field_data ]['field_key'],
							'label'     => $profile[ $field_data ]['label'],
						);
					}
				}
			}

			if ( count( $valid_form_data ) < 1 ) {
				return;
			}
			user_registration_pro_sync_external_field( $valid_form_data, $form_id, $user_id );

		}

		/**
		 * Handles Backward Compatibility for exits users who already whitelisted domain..
		 *
		 * @since 3.2.1
		 */
		public function handle_backward_compatibility() {

			$is_already_compatible = get_option( 'user_registration_pro_whitelist_compatibility', false );

			if ( ! $is_already_compatible ) {
				$ur_pro_whitelist_option = get_option( 'user_registration_pro_domain_restriction_settings','' );

				if ( ! empty( $ur_pro_whitelist_option ) ) {
					$this->handle_backward_compatibility_for_individual_form( $ur_pro_whitelist_option );
				}
			}
		}

		/**
		 * Handle backward compatibility for individual form
		 *
		 * @param mixed $ur_pro_whitelist_option Previous Domain Data..
		 */
		public function handle_backward_compatibility_for_individual_form( $ur_pro_whitelist_option ) {

			$registration = get_posts(
				array(
					'post_type' => 'user_registration',
				)
			);

			foreach ( $registration as $form ) {
				update_post_meta( $form->ID, 'user_registration_form_setting_enable_whitelist_domain', true );
				update_post_meta( $form->ID, 'user_registration_form_setting_whitelist_domain', 'allowed' );
				$whitelist = array_map( 'trim', explode( PHP_EOL, $ur_pro_whitelist_option ) );
				update_post_meta( $form->ID, 'user_registration_form_setting_domain_restriction_settings', implode( ",", $whitelist ) );
			}

			update_option( 'user_registration_pro_whitelist_compatibility', true );
		}

		/**
		 * Add Combine two login option.
		 *
		 * @param  array $options Other login options.
		 * @return  array
		 */
		public function add_admin_approval_after_email_confirmation_login_option( $options ) {
			$options['admin_approval_after_email_confirmation'] = esc_html__( 'Admin approval after email confirmation', 'user-registration' );
			return $options;
		}

		/**
		 * Enqueue scripts
		 *
		 * @since 1.0.0
		 */
		public function enqueue_scripts() {
			global $wpdb;
			$min = ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) ? '.min' : '';
			wp_register_style( 'user-registration-pro-admin-style', UR()->plugin_url() . '/assets/css/user-registration-pro-admin.css', array( 'flatpickr' ), UR_VERSION );

			if ( isset( $_GET['page'] ) && ( 'user-registration-settings' === $_GET['page'] || 'user-registration-dashboard' === $_GET['page'] || 'user-registration-addons' === $_GET['page'] ) ) {

				wp_register_script(
					'user-registration-pro-dashboard',
					UR()->plugin_url() . '/assets/js/pro/admin/user-registration-pro-dashboard-script' . $min . '.js',
					array(
						'jquery',
						'flatpickr',
						'chartjs',
					),
					UR_VERSION
				);

				wp_enqueue_script( 'user-registration-pro-dashboard' );
				wp_enqueue_style( 'user-registration-pro-admin-style' );

				wp_localize_script(
					'user-registration-pro-dashboard',
					'user_registration_pro_dashboard_script_data',
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
					)
				);
			}

			if ( isset( $_GET['page'] ) && ( 'add-new-registration' === $_GET['page'] || 'user-registration-addons' === $_GET['page'] || 'user-registration-settings' === $_GET['page'] ) ) {
				wp_register_script(
					'user-registration-pro-admin',
					UR()->plugin_url() . '/assets/js/pro/admin/user-registration-pro-admin-script' . $min . '.js',
					array(
						'jquery',
						'flatpickr',
						'chartjs',
					),
					UR_VERSION,
					true
				);

				wp_enqueue_script( 'user-registration-pro-admin' );
				wp_enqueue_style( 'user-registration-pro-admin-style' );
				wp_register_script(
					'user-registration-pro-addon-admin',
					UR()->plugin_url() . '/assets/js/pro/admin/user-registration-pro-addon-admin-script' . $min . '.js',
					array(
						'jquery',
					),
					UR_VERSION
				);

				wp_enqueue_script( 'user-registration-pro-addon-admin' );
				wp_localize_script(
					'user-registration-pro-admin',
					'user_registration_pro_admin_script_data',
					array(
						'ajax_url'                       => admin_url( 'admin-ajax.php' ),
						'ur_pro_external_fields_mapping_output' => $this->ur_pro_external_fields_mapping_output(),
						'ur_pro_form_fields'             => $this->get_forms_all_fields_data(),
						'ur_pro_db_tables'               => user_registration_get_all_db_tables(),
						'ur_pro_install_extension'       => wp_create_nonce( 'ur_pro_install_extension_nonce' ),
						'ur_pro_get_db_columns_by_table' => wp_create_nonce( 'ur_pro_get_db_columns_by_table_nonce' ),
						'ur_pro_get_form_fields_by_form_id' => wp_create_nonce( 'ur_pro_get_form_fields_by_form_id_nonce' ),
						'ur_pro_extension_installed_failed_text' => __( 'Installation Failed !!', 'user-registration' ),
						'ur_pro_db_prefix'               => $wpdb->prefix,
					)
				);
			}
		}

		/**
		 * Render External field mapping section.
		 */
		public function ur_pro_external_fields_mapping_output() {
			global $wpdb;
			$form_id                = isset( $_GET['edit-registration'] ) ? absint( $_GET['edit-registration'] ) : 0;
			$get_all_fields         = user_registration_pro_get_conditional_fields_by_form_id( $form_id, '' );
			$external_fields_mapped = $this->get_external_field_mapping_list( $form_id, $get_all_fields );

			if ( $external_fields_mapped ) {
				return $external_fields_mapped;
			} else {
				$output  = '<div class="ur-pro-fields-mapping-container">';
				$output .= '<div class="ur-pro-external-fields-mapping-container">';

				$output .= '<div class="ur-pro-field-mapping-wrap" >';
				$output .= '<div class="ur-pro-mapping-warning-wrap" ><br/>';
				$output .= '<div class="ur-pro-mapping-db-table-selection" >';
				$output .= '<label>' . esc_html__( 'Select table where you want to store data of selected fields.', 'user-registration' ) . '</label>';

				$output .= '<select class="ur-pro-db-table-section" name="ur-pro-db-table-section">';
				$output .= '<option value="usermeta_table">Usermeta Table</option>';
				$output .= '<option value="external_table">External Table</option>';
				$output .= '</select>';
				$output .= '</div>';

				$output .= '</div><br/>';

				$output .= '<div class="ur-pro-external-field-table-column-selection">';
				$output .= '<div class="ur-pro-mapping-warning-notice" >';
				$output .= '<span style="color:red;font-weight:bold" >' . esc_html__( 'Please make sure all fields are valid before saving the form.', 'user-registration' ) . '</span>';

				$output    .= '</div><br/>';
				$get_tables = user_registration_get_all_db_tables();
				if ( ! empty( $get_tables ) ) {
					// $selected_table = $wpdb->prefix . 'usermeta';
					$output .= '<label class="user_registration_db_table_label">' . esc_html__( 'Table Name', 'user-registration' ) . ' <span class="user-registration-help-tip" data-tip="' . esc_html__( "Select plugin's table name where data will be stored.", 'user-registration' ) . '"></span></label><select name="user_registration_db_table" class="ur_pro_db_table">';
					$output .= '<option value="">-- Select Table Name --</option>';
					foreach ( $get_tables as $key => $table_name ) {
						// $selected = $selected_table === $table_name ? 'selected="selected"' : '';
						$output .= '<option value="' . esc_attr( $table_name ) . '"> ' . $table_name . ' </option>';
					}
					$output .= '</select><br/>';

					$output .= '<label class="user_registration_user_id_db_column_label">' . esc_html__( 'Column for User ID', 'user-registration' ) . ' <span class="user-registration-help-tip" data-tip="' . esc_html__( "Select plugin's table user id column name where user id will be stored.", 'user-registration' ) . '"></span> </label><select name="user_registration_user_id_db_column" class="ur_pro_user_id_db_column">';
					$output .= '<option value="">-- Select Column for User ID --</option>';
					$output .= '</select><br/>';

					$output .= '<label class="user_registration_field_key_db_column_label"> ' . esc_html__( 'Column for Field Key', 'user-registration' ) . ' <span class="user-registration-help-tip" data-tip="' . esc_html__( "Select plugin's table field key column name where field key will be stored.", 'user-registration' ) . '"></span> </label><select name="user_registration_field_key_db_column" class="ur_pro_field_key_db_column">';
					$output .= '<option value="">-- Select Column for Field Key --</option>';
					$output .= '</select><br/>';

					$output .= '<label class="user_registration_field_value_db_column_label">  ' . esc_html__( 'Column for Field Value', 'user-registration' ) . ' <span class="user-registration-help-tip" data-tip="' . esc_html__( "Select plugin's table field value column name where field value will be stored.", 'user-registration' ) . '"></span> </label><select name="user_registration_field_value_db_column" class="ur_pro_field_value_db_column">';
					$output .= '<option value="">-- Select Column for Field Value --</option>';
					$output .= '</select><br/>';

				}
				$output .= '</div>';
				$output .= '<ul class="ur-pro-field-mapping-box" data-last-key="1">';

				$data_key = 1;
				$output  .= '<li class="ur-pro-external-field-map-group">';
				$output  .= '<div class="ur-pro-external-field-map-form-group" style="text-align:center;" >';
				$output  .= '<b>' . esc_html__( 'Form Fields', 'user-registration' ) . '</b>';
				$output  .= '</div>';
				$output  .= '<div class="ur-pro-operator"></div>';
				$output  .= '<div class="ur-pro-value">';
				$output  .= '<b>' . esc_html__( 'External Field Keys', 'user-registration' ) . '</b>';
				$output  .= '</div>';
				$output  .= '</li>';
				$output  .= '<li class="ur-pro-external-field-map-group" data-key="' . $data_key . '">';
				$output  .= '<div class="ur-pro-external-field-map-form-group">';
				$output  .= '<select class="ur-pro-fields ur-pro-field-map-select" name="ur_pro_external_map_form_fields[' . $data_key . ']">';
				$output  .= '<option value="">' . esc_html__( '-- Select Field --', 'user-registration' ) . '</option>';

				foreach ( $get_all_fields as $ind_field_key => $ind_field_value ) {
					$output .= '<option value="' . esc_attr__( $ind_field_key, 'user-registration' ) . '" data-type="' . esc_attr__( $ind_field_value['field_key'], 'user-registration' ) . '"> ' . $ind_field_value['label'] . ' </option>';
				}
				$output .= '</select></div>';
				$output .= '<div class="ur-pro-operator"> <i class="dashicons dashicons-arrow-right-alt"></i> </div>';
				$output .= '<div class="ur-pro-value">';
				$output .= '<input name="user_registration_form_value[' . $data_key . ']" class="ur_pro_external_field_name" placeholder="Enter Field Key" type="text" />';
				$output .= '</div>';
				$output .= '<span class="add">';
				$output .= '<i class="dashicons dashicons-plus"></i>';
				$output .= '</span>';
				$output .= '<span class="remove">';
				$output .= '<i class="dashicons dashicons-minus"></i>';
				$output .= '</span></li>';
				$output .= '</ul>';
				$output .= '</div>';
				$output .= '</div>';
				$output .= '</div>';
				return $output;
			}
		}

		/**
		 *  Get All form fields of the individual form in Form Settings.
		 *
		 * @return array
		 */
		public function get_forms_all_fields_data() {
			$form_id        = isset( $_GET['edit-registration'] ) ? absint( $_GET['edit-registration'] ) : 0;
			$get_all_fields = user_registration_pro_get_conditional_fields_by_form_id( $form_id, '' );
			return array(
				'all_form_fields' => $get_all_fields,
			);
		}

		/**
		 * Fetch Already mapped external fields.
		 *
		 * @param int   $form_id Form_id.
		 * @param array $get_all_fields All fields.
		 */
		public function get_external_field_mapping_list( $form_id, $get_all_fields ) {
			global $wpdb;
			$field_mapping_settings = maybe_unserialize( get_post_meta( $form_id, 'user_registration_pro_external_fields_mapping', true ) );

			if ( ! empty( $field_mapping_settings ) ) {
				$output  = '<div class="ur-pro-fields-mapping-container">';
				$output .= '<div class="ur-pro-external-fields-mapping-container">';

				$output .= '<div class="ur-pro-field-mapping-wrap" >';
				$output .= '<div class="ur-pro-mapping-warning-wrap" ><br/>';
				$output .= '<div class="ur-pro-mapping-db-table-selection" >';
				$output .= '<label>' . esc_html__( 'Select table where you want to store data of selected fields.', 'user-registration' ) . '</label>';

				$output .= '<select class="ur-pro-db-table-section" name="ur-pro-db-table-section">';

				$usermeta_table          = $wpdb->prefix . 'usermeta';
				$selected_db_table       = isset( $field_mapping_settings[0]['db_table'] ) ? $field_mapping_settings[0]['db_table'] : $usermeta_table;
				$selected_meta_table     = $usermeta_table === $selected_db_table ? 'selected="selected"' : '';
				$selected_external_table = $usermeta_table !== $selected_db_table ? 'selected="selected"' : '';

				$output .= '<option value="usermeta_table" ' . $selected_meta_table . '>Usermeta Table</option>';
				$output .= '<option value="external_table" ' . $selected_external_table . '>External Table</option>';
				$output .= '</select>';
				$output .= '</div>';

				$output .= '</div><br/>';

				$output .= '<div class="ur-pro-external-field-table-column-selection">';
				$output .= '<div class="ur-pro-mapping-warning-notice" >';
				$output .= '<span style="color:red;font-weight:bold" > ' . esc_html__( 'Please make sure all fields are valid before saving the form.', 'user-registration' ) . '</span> </div> <br/>';

				$get_tables = user_registration_get_all_db_tables();
				if ( ! empty( $get_tables ) ) {
					$output .= '<label class="user_registration_db_table_label">' . esc_html__( 'Table Name', 'user-registration' ) . ' <span class="user-registration-help-tip" data-tip="' . esc_html__( "Select plugin's table name where data will be stored.", 'user-registration' ) . '"></span></label><select name="user_registration_db_table" class="ur_pro_db_table">';
					$output .= '<option value="">-- Select Table Name --</option>';
					foreach ( $get_tables as $key => $table_name ) {
						$selected = $selected_db_table === $table_name ? 'selected="selected"' : '';
						$output  .= '<option value="' . esc_attr( $table_name ) . '" ' . $selected . '> ' . $table_name . ' </option>';
					}
					$output .= '</select><br/>';

					$get_columns                    = user_registration_get_columns_by_table( $selected_db_table );
					$selected_user_id_db_column     = isset( $field_mapping_settings[0]['user_id_db_column'] ) ? $field_mapping_settings[0]['user_id_db_column'] : '';
					$selected_field_key_db_column   = isset( $field_mapping_settings[0]['field_key_db_column'] ) ? $field_mapping_settings[0]['field_key_db_column'] : '';
					$selected_field_value_db_column = isset( $field_mapping_settings[0]['field_value_db_column'] ) ? $field_mapping_settings[0]['field_value_db_column'] : '';

					$output .= '<label class="user_registration_user_id_db_column_label">  ' . esc_html__( 'Column for User ID', 'user-registration' ) . ' <span class="user-registration-help-tip" data-tip="' . esc_html__( "Select plugin's table user id column name where user id will be stored.", 'user-registration' ) . '"></span> </label><select name="user_registration_user_id_db_column" class="ur_pro_user_id_db_column">';
					$output .= '<option value="">-- Select Column for User ID --</option>';
					foreach ( $get_columns as $key => $column_name ) {
						$selected = $selected_user_id_db_column === $column_name ? 'selected="selected"' : '';
						$output  .= '<option value="' . esc_attr( $column_name ) . '" ' . $selected . '> ' . $column_name . ' </option>';
					}
					$output .= '</select><br/>';

					$output .= '<label class="user_registration_field_key_db_column_label">  ' . esc_html__( 'Column for Field Key', 'user-registration' ) . ' <span class="user-registration-help-tip" data-tip="' . esc_html__( "Select plugin's table field key column name where field key will be stored.", 'user-registration' ) . '"></span> </label><select name="user_registration_field_key_db_column" class="ur_pro_field_key_db_column">';
					$output .= '<option value="">-- Select Column for Field Key --</option>';
					foreach ( $get_columns as $key => $column_name ) {
						$selected = $selected_field_key_db_column === $column_name ? 'selected="selected"' : '';
						$output  .= '<option value="' . esc_attr( $column_name ) . '" ' . $selected . '> ' . $column_name . ' </option>';
					}
					$output .= '</select><br/>';

					$output .= '<label class="user_registration_field_value_db_column_label">  ' . esc_html__( 'Column for Field Value', 'user-registration' ) . ' <span class="user-registration-help-tip" data-tip="' . esc_html__( "Select plugin's table field value column name where field value will be stored.", 'user-registration' ) . '"></span> </label><select name="user_registration_field_value_db_column" class="ur_pro_field_value_db_column">';
					$output .= '<option value="">-- Select Column for Field Value --</option>';
					foreach ( $get_columns as $key => $column_name ) {
						$selected = $selected_field_value_db_column === $column_name ? 'selected="selected"' : '';
						$output  .= '<option value="' . esc_attr( $column_name ) . '" ' . $selected . '> ' . $column_name . ' </option>';
					}
					$output .= '</select><br/>';

				}
				$output .= '</div>';

				$row_id = 1;

				if ( isset( $field_mapping_settings[0]['mapped_fields'] ) ) {

					$data_key = 1;

					foreach ( $field_mapping_settings[0]['mapped_fields'] as $fields_row ) {
						$output .= '<ul class="ur-pro-field-mapping-box" data-last-key="' . count( $fields_row ) . '">';
						$output .= '<li class="ur-pro-external-field-map-group">';
						$output .= '<div class="ur-pro-external-field-map-form-group" style="text-align:center;" >';
						$output .= '<b>' . esc_html__( 'Form Fields', 'user-registration' ) . '</b>';
						$output .= '</div>';
						$output .= '<div class="ur-pro-operator"></div>';
						$output .= '<div class="ur-pro-value">';
						$output .= '<b>' . esc_html__( 'External Field Keys', 'user-registration' ) . '</b>';
						$output .= '</div>';
						$output .= '</li>';
						foreach ( $fields_row as $key => $mapping_row ) {
							$output .= '<li class="ur-pro-external-field-map-group" data-key="' . $data_key . '">';
							$output .= '<div class="ur-pro-external-field-map-form-group">';
							$output .= '<select class="ur-pro-fields ur-pro-field-map-select" name="ur_pro_external_map_form_fields[' . $data_key . ']">';
							$output .= '<option value="">' . esc_html__( '-- Select --', 'user-registration' ) . '</option>';

							foreach ( $get_all_fields as $ind_field_key => $ind_field_value ) {
								$selectedField = $mapping_row['ur_field'] == $ind_field_key ? 'selected="selected"' : '';
								$output       .= '<option value="' . esc_attr__( $ind_field_key, 'user-registration' ) . '" data-type="' . esc_attr__( $ind_field_value['field_key'], 'user-registration' ) . '" ' . $selectedField . '> ' . $ind_field_value['label'] . ' </option>';
							}
							$output .= '</select></div>';
							$output .= '<div class="ur-pro-operator"> <i class="dashicons dashicons-arrow-right-alt"></i> </div>';
							$output .= '<div class="ur-pro-value">';
							$output .= '<input name="user_registration_form_value[' . $data_key . ']" value="' . esc_attr( $mapping_row['external_field'] ) . '" class="ur_pro_external_field_name" type="text" />';
							$output .= '</div>';
							$output .= '<span class="add">';
							$output .= '<i class="dashicons dashicons-plus"></i>';
							$output .= '</span>';
							$output .= '<span class="remove">';
							$output .= '<i class="dashicons dashicons-minus"></i>';
							$output .= '</span></li>';
							$data_key++;
						}
						$output .= '</ul>';
					}
				} else {
					$output .= '<ul class="ur-pro-field-mapping-box" data-last-key="1">';

					$data_key = 1;
					$output  .= '<li class="ur-pro-external-field-map-group">';
					$output  .= '<div class="ur-pro-external-field-map-form-group" style="text-align:center;" >';
					$output  .= '<b>' . esc_html__( 'Form Fields', 'user-registration' ) . '</b>';
					$output  .= '</div>';
					$output  .= '<div class="ur-pro-operator"></div>';
					$output  .= '<div class="ur-pro-value">';
					$output  .= '<b>' . esc_html__( 'External Field Keys', 'user-registration' ) . '</b>';
					$output  .= '</div>';
					$output  .= '</li>';
					$output  .= '<li class="ur-pro-external-field-map-group" data-key="' . $data_key . '">';
					$output  .= '<div class="ur-pro-external-field-map-form-group">';
					$output  .= '<select class="ur-pro-fields ur-pro-field-map-select" name="ur_pro_external_map_form_fields[' . $data_key . ']">';
					$output  .= '<option value="">' . esc_html__( '-- Select Field --', 'user-registration' ) . '</option>';

					foreach ( $get_all_fields as $ind_field_key => $ind_field_value ) {
						$output .= '<option value="' . esc_attr__( $ind_field_key, 'user-registration' ) . '" data-type="' . esc_attr__( $ind_field_value['field_key'], 'user-registration' ) . '"> ' . $ind_field_value['label'] . ' </option>';
					}
					$output .= '</select></div>';
					$output .= '<div class="ur-pro-operator"> <i class="dashicons dashicons-arrow-right-alt"></i> </div>';
					$output .= '<div class="ur-pro-value">';
					$output .= '<input name="user_registration_form_value[' . $data_key . ']" class="ur_pro_external_field_name" placeholder="Enter Field Key" type="text" />';
					$output .= '</div>';
					$output .= '<span class="add">';
					$output .= '<i class="dashicons dashicons-plus"></i>';
					$output .= '</span>';
					$output .= '<span class="remove">';
					$output .= '<i class="dashicons dashicons-minus"></i>';
					$output .= '</span></li>';
					$output .= '</ul>';
				}

				$output .= '</div>';
				$output .= '</div>';
				$output .= '</div>';
				return $output;
			}
		}

		/**
		 * Get all emails triggered.
		 *
		 * @return array $emails List of all emails.
		 */
		public function get_emails( $emails ) {
			$emails['User_Registration_Settings_Generated_Password_Email']       = include dirname( __FILE__ ) . '/admin/settings/emails/class-ur-settings-generated-password-email.php';
			$emails['User_Registration_Settings_Delete_Account_Email']           = include dirname( __FILE__ ) . '/admin/settings/emails/class-ur-settings-delete-account-email.php';
			$emails['User_Registration_Settings_Delete_Account_Admin_Email']     = include dirname( __FILE__ ) . '/admin/settings/emails/class-ur-settings-delete-account-admin-email.php';
			$emails['User_Registration_Settings_Prevent_Concurrent_Login_Email'] = include dirname( __FILE__ ) . '/admin/settings/emails/class-ur-settings-prevent-concurrent-login-email.php';
			$emails['User_Registration_Settings_Email_Verified_Admin_Email']     = include dirname( __FILE__ ) . '/admin/settings/emails/class-ur-settings-email-verified-admin-email.php';

			return $emails;
		}

		/**
		 * Include auto generated password success message into frontend messages.
		 *
		 * @param array $settings Frontend messages settings array.
		 */
		public function add_auto_generated_password_frontend_message( $settings ) {

			$auto_password_generation = array(
				array(
					'title'    => __( 'Auto generated password success message', 'user-registration' ),
					'desc'     => __( 'Enter the text message after user is registered.', 'user-registration' ),
					'id'       => 'user_registration_pro_auto_password_generation_message',
					'type'     => 'textarea',
					'desc_tip' => true,
					'css'      => 'min-width: 350px; min-height: 100px;',
					'default'  => __( 'An email with a password to access your account has been sent to your email.', 'user-registration' ),
				),
				array(
					'title'    => __( 'Email verified admin approval pending', 'user-registration' ),
					'desc'     => __( 'Enter the text message after email successfully verified but admin approval is pending.', 'user-registration' ),
					'id'       => 'user_registration_pro_email_verified_admin_approval_await_message',
					'type'     => 'textarea',
					'desc_tip' => true,
					'css'      => 'min-width: 350px; min-height: 100px;',
					'default'  => __( 'Email successfully verified. But Admin has to approve you to give access to login. Please contact to your administrator for your approval.', 'user-registration' ),
				),
			);

			$settings['sections']['frontend_success_messages_settings']['settings'] = array_merge( $settings['sections']['frontend_success_messages_settings']['settings'], $auto_password_generation );

			return $settings;
		}

		/**
		 * Include email suggestion message into frontend messages.
		 *
		 * @param array $settings error messages settings array.
		 */
		public function add_email_suggestion_error_message( $settings ) {

			$email_suggestion_message = array(
				array(
					'title'    => __( 'Email Suggestion', 'user-registration' ),
					'desc'     => __( 'Enter the message for valid email suggestion.', 'user-registration' ),
					'id'       => 'user_registration_form_submission_email_suggestion',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => __( 'Did you mean {suggestion}?', 'user-registration' ),
				),
			);

			$settings['sections']['frontend_error_message_messages_settings']['settings'] = array_merge( array_slice( $settings['sections']['frontend_error_message_messages_settings']['settings'], 0, 3 ), $email_suggestion_message, array_slice( $settings['sections']['frontend_error_message_messages_settings']['settings'], 3 ) );
			return $settings;
		}

		/**
		 * Generate a random password with length provided by the user.
		 *
		 * @since 1.0.0
		 */
		public function user_registration_pro_auto_generate_password( $form_id ) {
			$password_length = ur_get_single_post_meta( $form_id, 'user_registration_pro_auto_generated_password_length' );
			$user_pass       = trim( wp_generate_password( $password_length, true, true ) );
			add_filter(
				'user_registration_auto_generated_password',
				function ( $msg ) use ( $user_pass ) {
					return $user_pass;
				}
			);

			add_filter(
				'user_registration_required_form_fields',
				function ( $required_fields ) {
					$index = array_search( 'user_pass', $required_fields );
					unset( $required_fields[ $index ] );
					return $required_fields;
				}
			);
		}

		/**
		 * Process and submit entry to provider.
		 *
		 * @param array   $valid_form_data Form data submitted
		 * @param integer $form_id ID of the form.
		 * @param int     $user_id ID of the user
		 */
		public function user_registration_after_register_mail( $success_params, $valid_form_data, $form_id, $user_id ) {
			$enable_auto_password_generation = ur_get_single_post_meta( $form_id, 'user_registration_pro_auto_password_activate' );

			if ( 'yes' === $enable_auto_password_generation || '1' === $enable_auto_password_generation ) {
				$this->send_auto_generated_password_email( $user_id, $form_id, $valid_form_data );
				$success_params['auto_password_generation_success_message'] = get_option( 'user_registration_pro_auto_password_generation_message', esc_html( 'An email with a password to access your account has been sent to your email.' ) );
			}
			return $success_params;
		}

		/**
		 * Send mail with auto generated password.
		 *
		 * @param int $user_id ID of the user
		 */
		private function send_auto_generated_password_email( $user_id, $form_id, $form_data ) {

			include dirname( __FILE__ ) . '/admin/settings/emails/class-ur-settings-generated-password-email.php';

			$user                           = get_user_by( 'ID', $user_id );
			$username                       = $user->data->user_login;
			$email                          = $user->data->user_email;
			$user_pass                      = apply_filters( 'user_registration_auto_generated_password', 'user_pass' );
			list( $name_value, $data_html ) = ur_parse_name_values_for_smart_tags( $user_id, $form_id, $form_data );

			$values = array(
				'username'   => $username,
				'email'      => $email,
				'all_fields' => $data_html,
			);

			$header  = 'From: ' . UR_Emailer::ur_sender_name() . ' <' . UR_Emailer::ur_sender_email() . ">\r\n";
			$header .= 'Reply-To: ' . UR_Emailer::ur_sender_email() . "\r\n";
			$header .= "Content-Type: text/html\r\n; charset=UTF-8";

			$subject = get_option( 'user_registration_pro_auto_generated_password_email_subject', 'Your password for logging into {{blog_info}}' );

			$settings                  = new User_Registration_Settings_Generated_Password_Email();
			$message                   = $settings->user_registration_get_auto_generated_password_email();
			$message                   = get_option( 'user_registration_pro_auto_generated_password_email_content', $message );
			$form_id                   = ur_get_form_id_by_userid( $user_id );
			list( $message, $subject ) = user_registration_email_content_overrider( $form_id, $settings, $message, $subject );

			$message = UR_Emailer::parse_smart_tags( $message, $values, $name_value );
			$subject = UR_Emailer::parse_smart_tags( $subject, $values, $name_value );

			// Get selected email template id for specific form.
			$template_id = ur_get_single_post_meta( $form_id, 'user_registration_select_email_template' );

			if ( 'yes' === get_option( 'user_registration_pro_enable_auto_generated_password_email', 'yes' ) ) {
				UR_Emailer::user_registration_process_and_send_email( $email, $subject, $message, $header, '', $template_id );
			}
		}

		/**
		 * Popups admin actions.
		 */
		public function actions() {
			global $user_registration_pro_popup_table_list;
			$user_registration_pro_popup_table_list = new User_Registration_Pro_Popup_Table_List();
		}

		/**
		 * Table list output.
		 */
		public static function user_registration_pro_popup_list_table_output() {

			global $user_registration_pro_popup_table_list;
			$user_registration_pro_popup_table_list->process_actions();
			$user_registration_pro_popup_table_list->display_page();
		}

		/**
		 * Hide Notices From WPList table for Popup list table section.
		 * For Clear appearance.
		 *
		 * @return void
		 */
		public function hide_unrelated_notices() {
			global $wp_filter;

			// Return on other than user registraion builder page.
			if ( empty( $_REQUEST['page'] ) || ( 'user-registration-settings' !== $_REQUEST['page'] || empty( $_REQUEST['tab'] ) || 'user-registration-pro' !== $_REQUEST['tab'] ) && 'user-registration-dashboard' !== $_REQUEST['page'] ) {
				return;
			}

			foreach ( array( 'user_admin_notices', 'admin_notices', 'all_admin_notices' ) as $wp_notice ) {
				if ( ! empty( $wp_filter[ $wp_notice ]->callbacks ) && is_array( $wp_filter[ $wp_notice ]->callbacks ) ) {
					foreach ( $wp_filter[ $wp_notice ]->callbacks as $priority => $hooks ) {
						foreach ( $hooks as $name => $arr ) {
							// Remove all notices except user registration plugins notices.
							if ( ! strstr( $name, 'user_registration_' ) ) {
								unset( $wp_filter[ $wp_notice ]->callbacks[ $priority ][ $name ] );
							}
						}
					}
				}
			}
		}

		/**
		 * Add custom nav meta box.
		 *
		 * Adapted from http://www.johnmorrisonline.com/how-to-add-a-fully-functional-custom-meta-box-to-wordpress-navigation-menus/.
		 */
		public function add_nav_menu_meta_boxes() {
			$args = array(
				'post_type'   => 'ur_pro_popup',
				'post_status' => array( 'publish' ),
			);

			$popups             = new WP_Query( $args );
			$active_popup_count = 0;

			// Check if there is at least one active popup.
			if ( ! empty( $popups->posts ) ) {
				foreach ( $popups->posts as $popup ) {
					$popup_content = json_decode( $popup->post_content );

					if ( '1' === $popup_content->popup_status ) {
						$active_popup_count++;
					}
				}
			}

			if ( $active_popup_count > 0 ) {
				add_meta_box(
					'user_registration_pro_popup_nav_link',
					__( 'User Registration Pro Popup', 'user-registration' ),
					array(
						$this,
						'nav_menu_links',
					),
					'nav-menus',
					'side',
					'low'
				);
			}
		}

		/**
		 * Output menu links.
		 */
		public function nav_menu_links() {
			// Get items from account menu.
			$menus   = array();
			$post_id = array();
			$args    = array(
				'post_type'     => 'ur_pro_popup',
				'post_status'   => array( 'publish' ),
				'__post_not_in' => $post_id,
			);

			$popups = new WP_Query( $args );

			foreach ( $popups->posts as $popup ) {
				$post_id[]     = $popup->ID;
				$popup_content = json_decode( $popup->post_content );

				if ( '1' === $popup_content->popup_status ) {
					$menus[ 'user-registration-modal-link-' . $popup->ID ] = sprintf( __( '%s', 'user-registration' ), $popup_content->popup_title );
				}
			}

			?>
			<div id="posttype-user-registration-modal" class="posttypediv">
				<div id="tabs-panel-user-registration-modal" class="tabs-panel tabs-panel-active">
					<ul id="user-registration-modal-checklist" class="categorychecklist form-no-clear">
					<?php
					$i = - 1;
					foreach ( $menus as $key => $value ) :
						?>
							<li>
								<label class="menu-item-title">
									<input type="checkbox" class="menu-item-checkbox"
										   name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-object-id]"
										   value="<?php echo esc_attr( $i ); ?>"/> <?php echo esc_html( $value ); ?>
								</label>
								<input type="hidden" class="menu-item-type"
									   name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-type]" value="post"/>
								<input type="hidden" class="menu-item-title"
									   name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-title]"
									   value="<?php echo esc_html( $value ); ?>"/>
								<input type="hidden" class="menu-item-url"
									   name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-url]"
									   value="<?php echo esc_url( '#user-registration-modal' ); ?>"/>
								<input type="hidden" class="menu-item-classes"
									   name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-classes]"
									   value="user-registration-modal-link <?php echo $key; ?>"/>
							</li>
							<?php
							$i --;
							endforeach;
					?>
					</ul>
				</div>
				<p class="button-controls">
					<span class="list-controls">
					<a href="<?php echo admin_url( 'nav-menus.php?page-tab=all&selectall=1#posttype-user-registration-modal' ); ?>"
					   class="select-all"><?php _e( 'Select all', 'user-registration' ); ?></a>
					</span>
					<span class="add-to-menu">
					<input type="submit" class="button-secondary submit-add-to-menu right"
						   value="<?php esc_attr_e( 'Add to menu', 'user-registration' ); ?>"
						   name="add-post-type-menu-item" id="submit-posttype-user-registration-modal">
					<span class="spinner"></span>
					</span>
				</p>
			</div>
				<?php
		}

		/**
		 * Add dashboard menu item.
		 */
		public function dashboard_menu() {
			add_submenu_page(
				'user-registration',
				__( 'User Registration Dashboard', 'user-registration' ),
				__( 'Dashboard', 'user-registration' ),
				'manage_user_registration',
				'user-registration-dashboard',
				array(
					$this,
					'dashboard_page',
				),
				-1
			);
		}

		/*
		*  Init the dashboard page.
		*/
		public function dashboard_page() {
			// User_Registration_Pro_Dashboard_Analytics::output();
			include_once UR_ABSPATH . 'templates/pro/dashboard.php';
		}


		/**
		 * Render Pro Section
		 *
		 * @since 1.0.7
		 * @param  int $form_id Form Id.
		 * @return void
		 */
		public function render_pro_section( $form_id = 0 ) {

			echo '<div id="pro-settings" ><h3>' . esc_html__( 'Extras', 'user-registration' ) . '</h3>';
			$arguments = $this->get_pro_settings( $form_id );

			foreach ( $arguments as $args ) {
				user_registration_form_field( $args['id'], $args, get_post_meta( $form_id, $args['id'], true ) );
			}

			echo '</div>';

		}

		public function get_pro_settings( $form_id ) {
			$arguments = array(
				'form_id'      => $form_id,

				'setting_data' => array(
					array(
						'type'              => 'checkbox',
						'label'             => __( 'Enable Keyboard Friendly Form', 'user-registration' ),
						'description'       => '',
						'required'          => false,
						'id'                => 'user_registration_keyboard_friendly_form',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_keyboard_friendly_form', 'no' ),
						'tip'               => __( 'Fill the form easily using keyboard quick keys.', 'user-registration' ),
					),
					array(
						'type'              => 'checkbox',
						'label'             => __( 'Enable Reset Button', 'user-registration' ),
						'description'       => '',
						'required'          => false,
						'id'                => 'user_registration_form_setting_enable_reset_button',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_form_setting_enable_reset_button', 'yes' ),
						'tip'               => __( 'To reset the default values of user in registration form', 'user-registration' ),
					),
					array(
						'type'              => 'text',
						'label'             => __( 'Form Reset Button Label', 'user-registration' ),
						'description'       => '',
						'required'          => false,
						'id'                => 'user_registration_form_setting_form_reset_label',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_form_setting_form_reset_label', 'Reset' ),
						'tip'               => __( 'Set label for the reset button.', 'user-registration' ),
					),
					array(
						'type'              => 'text',
						'label'             => __( 'Form Reset Button Custom Class', 'user-registration' ),
						'description'       => '',
						'required'          => false,
						'id'                => 'user_registration_form_setting_form_reset_class',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_form_setting_form_reset_class', '' ),
						'tip'               => __( 'Custom css class to embed in the reset button. You can enter multiple classes seperated with space.', 'user-registration' ),
					),
					array(
						'type'              => 'checkbox',
						'label'             => __( 'Enable Form Field Icon', 'user-registration' ),
						'description'       => '',
						'required'          => false,
						'id'                => 'user_registration_enable_field_icon',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_enable_field_icon', 'yes' ),
						'tip'               => __( 'To show the field icon in user registration form', 'user-registration' ),
					),
					array(
						'type'              => 'checkbox',
						'label'             => __( 'Activate Auto Generated Password', 'user-registration' ),
						'tip'               => __( 'Enable auto generated password', 'user-registration' ),
						'id'                => 'user_registration_pro_auto_password_activate',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_pro_auto_password_activate', 'yes' ),
					),
					array(
						'type'    => 'number',
						'label'   => __( 'Password Length', 'user-registration' ),
						'tip'     => __( 'The length of password you want to generate.', 'user-registration' ),
						'id'      => 'user_registration_pro_auto_generated_password_length',
						'default' => ur_get_single_post_meta( $form_id, 'user_registration_pro_auto_generated_password_length', 10 ),
					),
					array(
						'type'              => 'checkbox',
						'label'             => __( 'Activate Spam Protection By Honeypot', 'user-registration' ),
						'tip'               => __( 'Select forms where you want to enable this feature.', 'user-registration' ),
						'id'                => 'user_registration_pro_spam_protection_by_honeypot_enable',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_pro_spam_protection_by_honeypot_enable', 'yes' ),
					),
					array(
						'type'              => 'checkbox',
						'label'             => __( 'Enable Whitelist/Blacklist Domain', 'user-registration' ),
						'description'       => '',
						'required'          => false,
						'id'                => 'user_registration_form_setting_enable_whitelist_domain',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_form_setting_enable_whitelist_domain', false ),
						'tip'               => __( 'Enable to whitelist or blacklist email domain.', 'user-registration' ),
					),
					array(
						'label'             => __( 'Whitelist/Blacklist Domain', 'user-registration' ),
						'description'       => '',
						'id'                => 'user_registration_form_setting_whitelist_domain',
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_form_setting_whitelist_domain', 'allowed' ),
						'type'              => 'select',
						'class'             => array( 'ur-enhanced-select' ),
						'custom_attributes' => array(),
						'input_class'       => array(),
						'required'          => false,
						'options'  => array(
							'allowed'  => esc_html__( "Allowed Domains", "user-registration" ),
							'denied'   => esc_html__( "Denied Domains", "user-registration" ),
						),
						'tip'               => __( 'Choose Allowed/Denied to Whitelist/Blacklist domains respectively.', 'user-registration' ),
					),
					array(
						'label'       => __( 'Domains Entries', 'user-registration' ),
						'description' => __( 'Multiple domain must be separated by comma.', 'user-registration' ),
						'id'          => 'user_registration_form_setting_domain_restriction_settings',
						'placeholder' => 'for eg. gmail.com, outlook.com',
						'default'     =>  ur_get_single_post_meta( $form_id, 'user_registration_form_setting_domain_restriction_settings', '' ),
						'type'        => 'textarea',
						'rows'        => 8,
						'cols'        => 40,
						'css'         => 'min-width: 350px; min-height: 100px;',
						'tip'         => __( 'Add the domain lists to whitelist/blacklist them.', 'user-registration' ),
					),
					array(
						'type'              => 'checkbox',
						'label'             => __( 'Enable form field mapping with external fields', 'user-registration' ),
						'description'       => '',
						'required'          => false,
						'id'                => 'user_registration_enable_external_fields_mapping',
						'class'             => array( 'ur-enhanced-select' ),
						'input_class'       => array(),
						'custom_attributes' => array(),
						'default'           => ur_get_single_post_meta( $form_id, 'user_registration_enable_external_fields_mapping', 'no' ),
						'tip'               => __( 'To enable mapping the external fields with user registration fields', 'user-registration' ),
					),
				),
			);
			$arguments = apply_filters( 'user_registration_get_pro_settings', $arguments );
			return $arguments['setting_data'];
		}

		/**
		 * Save Pro Form Settings
		 *
		 * @param  array   $settings Settings.
		 * @param  integer $form_id Form Id.
		 * @return array $settings
		 */
		public function save_pro_settings( $settings, $form_id = 0 ) {

			$pro_setting = $this->get_pro_settings( $form_id );
			$settings    = array_merge( $settings, $pro_setting );

			return $settings;
		}

		/**
		 * Save External field mappingsettings.
		 *
		 * @param array $post Post.
		 * @return void.
		 */
		public function save_pro_form_settings( $post ) {
			$form_id                          = absint( $post['form_id'] );
			$ur_pro_external_mapping_settings = isset( $post['ur_pro_external_mapping_settings'] ) ? wp_unslash( $post['ur_pro_external_mapping_settings'] ) : array();

			// conditional user role settings save.
			if ( ! empty( $ur_pro_external_mapping_settings ) ) {
				update_post_meta( $form_id, 'user_registration_pro_external_fields_mapping', $ur_pro_external_mapping_settings );
			}
		}

		/**
		 * Add to general settings of User Registration.
		 *
		 * @param array $general_settings General settings array from Core.
		 */
		public function ur_pro_add_general_settings( $general_settings ) {

			// Add new settings to my account section.
			$my_account_options = $general_settings['sections']['my_account_options']['settings'];
			$my_account_options = array_merge(
				$my_account_options,
				array(
					array(
						'title'    => __( 'Delete Account Action ', 'user-registration' ),
						'desc'     => __( 'Allow your users to delete their account from their account page directly or after password confirmation.', 'user-registration' ),
						'id'       => 'user_registration_pro_general_setting_delete_account',
						'default'  => 'disable',
						'type'     => 'select',
						'class'    => 'ur-enhanced-select',
						'css'      => 'min-width: 350px;',
						'desc_tip' => true,
						'options'  => array(
							'disable'         => __( 'Disable', 'user-registration' ),
							'direct_delete'   => __( 'Direct Delete', 'user-registration' ),
							'prompt_password' => __( 'Prompt password popup before delete account.', 'user-registration' ),
						),
					),
				)
			);

			$general_settings['sections']['my_account_options']['settings'] = $my_account_options;

			return $general_settings;
		}

		/**
		 * Add to advanced settings of User Registration.
		 *
		 * @param array $advanced_settings Advanced settings array from Core.
		 */
		public function ur_pro_add_advanced_settings( $advanced_settings ) {
			// Add new settings to advanced options.
			$advanced_options = $advanced_settings['sections']['advanced_settings']['settings'];

			foreach($advanced_options as $option_index => $option){
				if($option['id'] === 'user_registration_allow_usage_tracking'){
					$advanced_options[$option_index]['desc'] = sprintf(__( 'Help us improve the plugin\'s features by sharing %snon-sensitive plugin data%s with us.', 'user-registration' ),'<a href="https://docs.wpeverest.com/user-registration/docs/miscellaneous-settings/#1-toc-title" target="_blank">', '</a>');
				}
			}

			$advanced_options = array_merge(
				$advanced_options,
				array(
					array(
						'title'    => __( 'POST Submission URL', 'user-registration' ),
						'desc'     => __( 'This option lets you send form data to a custom URL of your choice', 'user-registration' ),
						'id'       => 'user_registration_pro_general_post_submission_settings',
						'type'     => 'text',
						'desc_tip' => true,
						'css'      => 'min-width: 350px;',
					),
					array(
						'title'    => __( 'POST Submission Method', 'user-registration' ),
						'desc'     => __( 'This option lets you choose how you want the form submitted data request to be sent.', 'user-registration' ),
						'id'       => 'user_registration_pro_general_setting_post_submission',
						'default'  => 'disable',
						'type'     => 'select',
						'class'    => 'ur-enhanced-select',
						'css'      => 'min-width: 350px;',
						'desc_tip' => true,
						'options'  => array(
							'post'      => __( 'POST', 'user-registration' ),
							'post_json' => __( 'POST(JSON)', 'user-registration' ),
							'get'       => __( 'GET', 'user-registration' ),
						),
					),
				)
			);

			$advanced_settings['sections']['advanced_settings']['settings'] = $advanced_options;

			return $advanced_settings;
		}

		/**
		 * Add to general settings of User Registration.
		 *
		 * @param array $login_option Login option settings array from Core.
		 */
		public function ur_pro_add_login_options_settings( $login_option ) {

			// Add new settings to general options.
			$login_options_settings = $login_option['sections']['login_options_settings']['settings'];
			$login_options_settings = array_merge(
				$login_options_settings,
				array(
					array(
						'title'    => __( 'Enable Login Icon Field', 'user-registration' ),
						'desc'     => __( 'This option lets you to enable icon field in login form', 'user-registration' ),
						'id'       => 'user_registration_pro_general_setting_login_form',
						'type'     => 'checkbox',
						'desc_tip' => __( 'Check to field to enable icon field in login form', 'user-registration' ),
						'css'      => 'min-width: 350px;',
						'default'  => 'no',
					),
					array(
						'title'    => __( 'Prevent Active Login', 'user-registration' ),
						'desc'     => __( 'Enable Prevent Concurrent Login', 'user-registration' ),
						'id'       => 'user_registration_pro_general_setting_prevent_active_login',
						'type'     => 'checkbox',
						'desc_tip' => __( 'Check this option to prevent the active logins.', 'user-registration' ),
						'css'      => 'min-width: 350px;',
						'default'  => 'no',

					),
					array(
						'title'    => __( 'Maxmium Active Login', 'user-registration' ),
						'desc'     => __( 'This option lets you to choose the number of active logins a user account have.', 'user-registration' ),
						'id'       => 'user_registration_pro_general_setting_limited_login',
						'type'     => 'number',
						'desc_tip' => true,
						'class'    => 'ur-active-login',
						'default'  => 5,
					),
					array(
						'title'    => __( 'Enable Redirect Back to Previous Page', 'user-registration' ),
						'desc'     => __( 'This option lets you to enable redirect back to previous page after login', 'user-registration' ),
						'id'       => 'user_registration_pro_general_setting_redirect_back_to_previous_page',
						'type'     => 'checkbox',
						'desc_tip' => __( 'Check to field to enable redirect back to previous page after login', 'user-registration' ),
						'css'      => 'min-width: 350px;',
						'default'  => 'no',
					),
				)
			);

			$login_option['sections']['login_options_settings']['settings'] = $login_options_settings;

			return $login_option;
		}

		/**
		 * Add activate, deactivate and install addon button below addon card in addons page.
		 *
		 * @param object $addon Addons details.
		 * @since 3.0.1
		 */
		public function ur_pro_add_addons_page_footer( $addon ) {
			$license_plan = ur_get_license_plan();

			?>
			<?php if ( in_array( trim( $license_plan ), $addon->plan, true ) ) : ?>
					<div class="action-buttons">
						<?php if ( is_plugin_active( $addon->slug . '/' . $addon->slug . '.php' ) ) : ?>
							<?php
								/* translators: %s: Add-on title */
								$aria_label  = sprintf( esc_html__( 'Deactivate %s now', 'user-registration' ), $addon->title );
								$plugin_file = plugin_basename( $addon->slug . '/' . $addon->slug . '.php' );
								$url         = wp_nonce_url(
									add_query_arg(
										array(
											'page'   => 'user-registration-addons',
											'action' => 'deactivate',
											'plugin' => $plugin_file,
										),
										admin_url( 'admin.php' )
									),
									'deactivate-plugin_' . $plugin_file
								);
							?>
							<a class="button button-danger deactivate-now" href="<?php echo esc_url_raw( $url ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>"><?php esc_html_e( 'Deactivate', 'user-registration' ); ?></a>
						<?php elseif ( file_exists( WP_PLUGIN_DIR . '/' . $addon->slug . '/' . $addon->slug . '.php' ) ) : ?>
							<?php
								/* translators: %s: Add-on title */
								$aria_label  = sprintf( esc_html__( 'Activate %s now', 'user-registration' ), $addon->title );
								$plugin_file = plugin_basename( $addon->slug . '/' . $addon->slug . '.php' );
								$url         = wp_nonce_url(
									add_query_arg(
										array(
											'page'   => 'user-registration-addons',
											'action' => 'activate',
											'plugin' => $plugin_file,
										),
										admin_url( 'admin.php' )
									),
									'activate-plugin_' . $plugin_file
								);
							?>
							<a class="button button-primary activate-now" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>"><?php esc_html_e( 'Activate', 'user-registration' ); ?></a>
						<?php else : ?>
							<?php
							/* translators: %s: Add-on title */
							$aria_label = sprintf( esc_html__( 'Install %s now', 'user-registration' ), $addon->title );
							?>
							<a href="#" class="button install-now" data-slug="<?php echo esc_attr( $addon->slug ); ?>" data-name="<?php echo esc_attr( $addon->name ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>"><?php esc_html_e( 'Install Addon', 'user-registration' ); ?></a>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<div class="action-buttons upgrade-plan">
						<a class="button upgrade-now" href="https://wpeverest.com/wordpress-plugins/user-registration/pricing/?utm_source=addons-page&utm_medium=upgrade-button&utm_campaign=evf-upgrade-to-pro" target="_blank"><?php esc_html_e( 'Upgrade Plan', 'user-registration' ); ?></a>
					</div>
				<?php endif; ?>
				<?php
		}

		/**
		 * Handle redirects after addon activate/deactivate.
		 *
		 * @since 3.0.1
		 */
		public function addon_actions() {

			if ( isset( $_GET['page'], $_REQUEST['action'] ) && 'user-registration-addons' === $_GET['page'] && 'user-registration-addons-refresh' !== $_GET['action'] ) {
				$action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
				$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : false;

				if ( $plugin && in_array( $action, array( 'activate', 'deactivate' ), true ) ) {

					if ( 'activate' === $action ) {
						if ( ! current_user_can( 'activate_plugin', $plugin ) ) {
							wp_die( esc_html__( 'Sorry, you are not allowed to activate this plugin.', 'user-registration' ) );
						}

						check_admin_referer( 'activate-plugin_' . $plugin );

						activate_plugin( $plugin );
					} elseif ( 'deactivate' === $action ) {
						if ( ! current_user_can( 'deactivate_plugins' ) ) {
							wp_die( esc_html__( 'Sorry, you are not allowed to deactivate plugins for this site.', 'user-registration' ) );
						}

						check_admin_referer( 'deactivate-plugin_' . $plugin );

						deactivate_plugins( $plugin );
					}
				}

				// Redirect to the add-ons page.
				wp_safe_redirect( admin_url( 'admin.php?page=user-registration-addons' ) );
				exit;
			}
		}

		/**
		 * Add query string setting in advance setting
		 *
		 * @param string $field_id Identifier for the field to which advance settings are to be added.
		 * @param string $field_class Class for the setting.
		 * @param array  $fields List of settings to be appended in advance settings of fields.
		 *
		 * @since 3.0.1
		 */
		public function ur_pro_auto_populate_advance_setting( $fields, $field_id, $field_class ) {

			$custom_setting = array(

				'enable_prepopulate' => array(
					'label'    => __( 'Allow field to be populated dynamically', 'user-registration' ),
					'data-id'  => $field_id . '_enable_prepopulate',
					'name'     => $field_id . '[enable_prepopulate]',
					'class'    => $field_class . ' ur-settings-field-prepopulate',
					'type'     => 'select',
					'required' => false,
					'default'  => 'false',
					'options'  => array(
						'true'  => 'Yes',
						'false' => 'No',
					),
					'tip'      => __( 'Enable this option to allow field to be populated dynamically', 'user-registration' ),
				),
				'parameter_name'     => array(
					'label'       => __( 'Parameter Name', 'user-registration' ),
					'data-id'     => $field_id . '_parameter_name',
					'name'        => $field_id . '[parameter_name]',
					'class'       => $field_class . ' ur-settings-parameter_name',
					'type'        => 'text',
					'required'    => false,
					'default'     => '',
					'placeholder' => __( 'Enter parameter name', 'user-registration' ),
					'tip'         => __( 'Name of the parameter to populate the field.', 'user-registration' ),
				),
			);

			$fields = array_merge( $fields, $custom_setting );
			return $fields;
		}

		/**
		 * Validate unique field setting.
		 *
		 * @param array  $fields list of fields.
		 * @param int    $field_id Field ID.
		 * @param string $field_class Field class.
		 *
		 * @since 3.0.8
		 */
		public function ur_pro_validate_as_unique( $fields, $field_id, $field_class ) {
			$unique_field_setting = array(
				'validate_unique'    => array(
					'label'    => __( 'Validate as unique', 'user-registration' ),
					'data-id'  => $field_id . '_validate_unique',
					'name'     => $field_id . '[validate_unique]',
					'class'    => $field_class . ' ur-settings-field-validate-unique',
					'type'     => 'select',
					'required' => false,
					'default'  => 'false',
					'options'  => array(
						'true'  => 'Yes',
						'false' => 'No',
					),
					'tip'      => __( 'Limit user input to unique values only. It will require that a value entered in the field is unique and doesn\'t previously exist for this field in the entry database.', 'user-registration' ),
				),
				'validation_message' => array(
					'label'    => __( 'Validation message for duplicate', 'user-registration' ),
					'data-id'  => $field_id . '_validation_message',
					'name'     => $field_id . '[validation_message]',
					'class'    => $field_class . ' ur-settings-validation_message',
					'type'     => 'text',
					'required' => false,
					'default'  => __( 'This field value need to be unique.', 'user-registration' ),
					'tip'      => __( 'Message that shows when validation as unique fails on form submission.', 'user-registration' ),
				),
			);
			$fields               = array_merge( $fields, $unique_field_setting );
			return $fields;
		}

		/**
		 * Restrict copy/paste field setting.
		 *
		 * @param array  $fields list of fields.
		 * @param int    $field_id Field ID.
		 * @param string $field_class Field class.
		 *
		 * @since 3.0.8
		 */
		public function ur_pro_restrict_copy_paste( $fields, $field_id, $field_class ) {
			$restrict_copy_settings = array(
				'disable_copy_paste' => array(
					'label'    => __( 'Restrict copy/cut/paste', 'user-registration' ),
					'data-id'  => $field_id . '_restrict_copy_paste',
					'name'     => $field_id . '[restrict_copy_paste]',
					'class'    => $field_class . ' ur-settings-field-restrict_copy_paste',
					'type'     => 'select',
					'required' => false,
					'default'  => 'false',
					'options'  => array(
						'true'  => 'Yes',
						'false' => 'No',
					),
					'tip'      => __( 'Restrict copy/cut/paste on this field', 'user-registration' ),
				),
			);

			$fields = array_merge( $fields, $restrict_copy_settings );
			return $fields;
		}

		/**
		 * Add Enable Tooltip option in general field settings.
		 *
		 * @param array $setting General Setting array.
		 * @param int   $field_id Form Id.
		 * @return array.
		 */
		public function add_form_field_tooltip_options( $setting, $field_id ) {
			$custom_options = array(
				'tooltip'         => array(
					'setting_id'  => 'tooltip',
					'type'        => 'select',
					'label'       => __( 'Enable Tooltip', 'user-registration' ),
					'name'        => 'ur_general_setting[tooltip]',
					'placeholder' => '',
					'required'    => true,
					'options'     => array(
						'no'  => __( 'No', 'user-registration' ),
						'yes' => __( 'Yes', 'user-registration' ),
					),
					'tip'         => __( 'Show tooltip icon beside field label.', 'user-registration' ),
				),
				'tooltip_message' => array(
					'setting_id'  => 'tooltip-message',
					'type'        => 'textarea',
					'label'       => __( 'Tooltip Message', 'user-registration' ),
					'name'        => 'ur_general_setting[tooltip_message]',
					'placeholder' => 'Placeholder',
					'required'    => true,
					'tip'         => __( 'The Message to display when user hovers over tooltip icon.', 'user-registration' ),
				),
			);

			$setting = array_merge( $setting, $custom_options );

			return $setting;
		}

		/**
		 * Validate field as profile save from admin.
		 *
		 * @param int   $user_id User Id.
		 * @param array $profile form data.
		 */
		public function validate_unique_field_profile_update_by_admin( $user_id, $profile ) {
			$single_field = array();
			$field_name   = '';
			$label        = '';
			$message      = '';
			$duplicate    = '';
			$form_id      = get_user_meta( $user_id, 'ur_form_id', true );
			if ( isset( $_POST ) ) {
				$form_data = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				foreach ( $form_data as $key => $value ) {
					if ( 'url' === $key ) {
						$key = 'user_' . $key;
					}
					$field_key                  = str_replace( 'user_registration_', '', $key );
					$single_field[ $field_key ] = $value;
				}
			}

			foreach ( $profile as $key_name => $field_item ) {
				foreach ( $field_item as $key => $field_value ) {
					if ( isset( $key ) && 'validate_unique' === $key ) {
						if ( 'true' === $field_value ) {
							$field_name = str_replace( 'user_registration_', '', $key_name );
							$message    = $field_item['validate_message'];
							$label      = $field_item['label'];
							if ( in_array( $field_name, array_keys( $single_field ), true ) ) {
								$duplicate = ur_validate_unique_field(
									array(
										'ur_form_id' => $form_id,
										'search'     => $single_field[ $field_name ],
										'field_name' => $field_name,
									)
								);
							}

							if ( ! empty( $duplicate ) && ! in_array( $user_id, $duplicate ) ) {
								set_transient( 'ur_unique_field_name', $label, 60 );
								set_transient( 'ur_unique_error_msg', $message, 60 );
								add_filter( 'update_user_metadata', array( $this, 'prevent_update_meta_value' ), 10, 4 );
							}
						}
					}
				}
			}
		}

		/** Show errors message.
		 *
		 * @param object $errors WP_Error.
		 * @param bool   $update user update.
		 * @param object $user user.
		 */
		public function check_unique_fields( $errors, $update, $user ) {
			$name    = get_transient( 'ur_unique_field_name' );
			$message = get_transient( 'ur_unique_error_msg' );
			if ( ! empty( $name ) && ! empty( $message ) ) {
				/* translators: %s - validation message */
				$errors->add( $name, sprintf( __( '<strong>%1$1s:</strong> %2$2s', 'user-registration' ), $name, $message ) );

			}
			delete_transient( 'ur_unique_field_name' );
			delete_transient( 'ur_unique_error_msg' );

		}

		/** Prevent update meta value.
		 *
		 * @param bool   $check check.
		 * @param int    $object_id Id.
		 * @param string $meta_key meta key.
		 * @param mixed  $meta_value meta value.
		 */
		public function prevent_update_meta_value( $check, $object_id, $meta_key, $meta_value ) {
			if ( $meta_key ) {
				return false;
			}
			return $check;
		}

		/**
		 * Outputs Custom Fields Selection area to the Export User page.
		 *
		 * @param int $form_id
		 * @return void
		 */
		public function display_custom_fields_options( $form_id = '' ) {

			echo '<div class="ur-export-custom-fields">
					<p>Select Fields to Export</p>
					<div class="ur-form-fields-container">
						<input type="checkbox" name="csv-export-all-fields" class="ur-all-fields-option" checked>&nbsp;Select All<hr />';

			$fields_dict = UR()->form->get_form_fields(
				$form_id,
				array(
					'content_only' => true,
					'hide_fields'  => true,
				)
			);

			foreach ( $fields_dict as $field_id => $field_label ) {
				echo '<input type="checkbox" name="csv-export-custom-fields[]" class="ur-custom-fields-input" value="' .
				esc_attr( $field_id ) . '" checked>&nbsp;' . esc_html( $field_label ) . '<br />';
			}

			echo '</div></div><br/>';
			echo '<input type="hidden" name="all_fields_dict" class="ur_export_csv_fields_dict" value="' . esc_attr( wp_json_encode( $fields_dict ) ) . '"/>';
		}
	}
}

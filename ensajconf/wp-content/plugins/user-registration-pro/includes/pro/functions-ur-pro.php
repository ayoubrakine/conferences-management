<?php
/**
 * PRO Functions and Hooks
 *
 * @package User Registration Pro
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'user_registration_validate_email_whitelist', 'user_registration_pro_validate_email', 10, 4 );
add_action( 'user_registration_after_register_user_action', 'ur_send_form_data_to_custom_url', 10, 3 );
add_action( 'init', 'user_registration_force_logout' );

if ( 'yes' === get_option( 'user_registration_pro_general_setting_prevent_active_login', 'no' ) && ! is_admin() ) {
	// User can be authenticated with the provided password.
	add_filter( 'wp_authenticate_user', 'ur_prevent_concurrent_logins', 10, 2 );
}

if ( ! function_exists( ' user_registration_pro_sync_external_field' ) ) {
	/**
	 * While registration save external field meta with mapped user registration field value.
	 *
	 * @param array $valid_form_data Form Data.
	 * @param int   $form_id Form ID.
	 * @param int   $user_id User ID.
	 */
	function user_registration_pro_sync_external_field( $valid_form_data, $form_id, $user_id ) {

		global $wpdb;

		$field_mapping_settings = maybe_unserialize( get_post_meta( $form_id, 'user_registration_pro_external_fields_mapping', true ) );

		if ( ! empty( $field_mapping_settings ) ) {

			$usermeta_table                 = $wpdb->prefix . 'usermeta';
			$selected_db_table              = isset( $field_mapping_settings[0]['db_table'] ) ? $field_mapping_settings[0]['db_table'] : $usermeta_table;
			$selected_user_id_db_column     = isset( $field_mapping_settings[0]['user_id_db_column'] ) ? $field_mapping_settings[0]['user_id_db_column'] : '';
			$selected_field_key_db_column   = isset( $field_mapping_settings[0]['field_key_db_column'] ) ? $field_mapping_settings[0]['field_key_db_column'] : '';
			$selected_field_value_db_column = isset( $field_mapping_settings[0]['field_value_db_column'] ) ? $field_mapping_settings[0]['field_value_db_column'] : '';

			$is_valid_db_tables_and_columns = false;

			if ( $usermeta_table !== $selected_db_table ) {

				if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $selected_db_table ) ) ) === $selected_db_table ) {
					$is_valid_db_tables_and_columns = true;
				}
			}

			if ( isset( $field_mapping_settings[0]['mapped_fields'] ) ) {

				foreach ( $field_mapping_settings[0]['mapped_fields'] as $fields_row ) {

					foreach ( $fields_row as $key => $mapping_row ) {
						if ( isset( $valid_form_data[ $mapping_row['ur_field'] ] ) ) {
							if ( $usermeta_table === $selected_db_table ) {
								update_user_meta( $user_id, $mapping_row['external_field'], $valid_form_data[ $mapping_row['ur_field'] ]->value );
							} elseif ( $is_valid_db_tables_and_columns && ! empty( $selected_user_id_db_column ) && ! empty( $selected_field_key_db_column ) && ! empty( $selected_field_value_db_column ) ) {

								$element_prepared = $wpdb->prepare(
									"SELECT $selected_field_key_db_column FROM $selected_db_table WHERE $selected_user_id_db_column=%d AND $selected_field_key_db_column=%s",
									array( $user_id, $mapping_row['external_field'] )
								);
								$field_key_db     = $wpdb->get_var( $element_prepared );

								$value = is_array( $valid_form_data[ $mapping_row['ur_field'] ]->value ) ? maybe_serialize( $valid_form_data[ $mapping_row['ur_field'] ]->value ) : $valid_form_data[ $mapping_row['ur_field'] ]->value;

								if ( $field_key_db === $mapping_row['external_field'] ) {

									$result = $wpdb->update(
										$selected_db_table,
										array(
											$selected_field_value_db_column => $value,
										),
										array(
											$selected_user_id_db_column     => $user_id,
											$selected_field_key_db_column   => $mapping_row['external_field']
										)
									);
								} else {
									$result = $wpdb->insert(
										$selected_db_table,
										array(
											$selected_user_id_db_column     => $user_id,
											$selected_field_key_db_column   => $mapping_row['external_field'],
											$selected_field_value_db_column => $value,
										)
									);
								}

								if ( is_wp_error( $result ) || ! $result ) {
									// $error_string = $result->get_error_message(); //phpcs:ignore;
									ur_get_logger()->critical( print_r( 'Mismatch Columns', true ) );
								}
							}
						}
					}
				}
			}
		}
	}
}

if ( ! function_exists( 'user_registration_pro_validate_email' ) ) {

	/**
	 * Validate user entered email against whitelisted email domain
	 *
	 * @since 1.0.0
	 * @param email  $user_email email entered by user.
	 * @param string $filter_hook Filter for validation error message.
	 */
	function user_registration_pro_validate_email( $user_email, $filter_hook, $field, $form_id ) {
		$enable_domain_settings   = ur_get_single_post_meta( $form_id, 'user_registration_form_setting_enable_whitelist_domain', false );
		$domain_settings          = ur_get_single_post_meta( $form_id, 'user_registration_form_setting_whitelist_domain', 'allowed' );
		$whitelist_domain_entries = ur_get_single_post_meta( $form_id, 'user_registration_form_setting_domain_restriction_settings', '' );

		if ( ur_string_to_bool( $enable_domain_settings ) ) {
			if ( ! empty( $whitelist_domain_entries ) ) {
				$whitelist         = array_map( 'trim', explode( ',', $whitelist_domain_entries ) );
				$email             = explode( '@', $user_email );
				$blacklisted_email = '';

				if ( 'allowed' === $domain_settings ) {
					if ( ! in_array( $email[1], $whitelist ) ) {
						$blacklisted_email = $email[1];
					}
				} else {
					if ( in_array( $email[1], $whitelist ) ) {
						$blacklisted_email = $email[1];
					}
				}

				if ( ! empty( $blacklisted_email ) ) {
					$message = sprintf(
						/* translators: %s - Restricted domain. */
						__( 'The email domain %s is restricted. Please try another email address.', 'user-registration' ),
						$blacklisted_email
					);

					if ( '' !== $filter_hook ) {
						add_filter(
							$filter_hook,
							function ( $msg ) use ( $message ) {
								return $message;
							}
						);
					} else {
						// Check if ajax fom submission on edit profile is on.
						if ( 'yes' === get_option( 'user_registration_ajax_form_submission_on_edit_profile', 'no' ) ) {
							wp_send_json_error(
								array(
									'message' => $message,
								)
							);
						} else {
							ur_add_notice( $message, 'error' );
						}
					}
				}
			}
		}
	}

	/**
	 * Handles all settings action.
	 *
	 * @return bool.
	 */
	function user_registration_pro_popup_settings_handler() {

		if ( ! empty( $_POST ) ) {

			// Nonce Check.
			if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'user-registration-settings' ) ) { // phpcs:ignore
				die( esc_html__( 'Action failed. Please refresh the page and retry.', 'user-registration' ) );
			}

			$popup_data = wp_unslash( $_POST );

			// Update the popups for add new functionality.
			if ( ( isset( $popup_data['user_registration_pro_popup_title'] ) && ! empty( $popup_data['user_registration_pro_popup_title'] ) ) || ( isset( $_REQUEST['edit-popup'] ) && ! empty( $_REQUEST['edit-popup'] ) ) ) {
				$active       = isset( $popup_data['user_registration_pro_enable_popup'] ) ? $popup_data['user_registration_pro_enable_popup'] : '';
				$popup_type   = isset( $popup_data['user_registration_pro_popup_type'] ) ? $popup_data['user_registration_pro_popup_type'] : '';
				$popup_title  = isset( $popup_data['user_registration_pro_popup_title'] ) ? $popup_data['user_registration_pro_popup_title'] : '';
				$popup_header = isset( $popup_data['user_registration_pro_popup_header_content'] ) ? $popup_data['user_registration_pro_popup_header_content'] : '';
				$form         = isset( $popup_data['user_registration_pro_popup_registration_form'] ) ? $popup_data['user_registration_pro_popup_registration_form'] : '';
				$popup_footer = isset( $popup_data['user_registration_pro_popup_footer_content'] ) ? $popup_data['user_registration_pro_popup_footer_content'] : '';
				$popup_size   = isset( $popup_data['user_registration_pro_popup_size'] ) ? $popup_data['user_registration_pro_popup_size'] : 'default';

				$post_data = array(
					'popup_type'   => $popup_type,
					'popup_title'  => $popup_title,
					'popup_status' => $active,
					'popup_header' => $popup_header,
					'popup_footer' => $popup_footer,
					'popup_size'   => $popup_size,
				);

				if ( 'registration' === $popup_type ) {
					$post_data['form'] = $form;
				}

				$post_data = array(
					'post_type'      => 'ur_pro_popup',
					'post_title'     => ur_clean( $popup_title ),
					'post_content'   => wp_json_encode( $post_data, JSON_UNESCAPED_UNICODE ),
					'post_status'    => 'publish',
					'comment_status' => 'closed',   // if you prefer.
					'ping_status'    => 'closed',      // if you prefer.
				);

				if ( isset( $_REQUEST['edit-popup'] ) ) {
					$post_data['ID'] = wp_unslash( intval( $_REQUEST['edit-popup'] ) );
					$post_id         = wp_update_post( wp_slash( $post_data ), true );
					update_option( 'ur-popup-edited', true );
				} else {
					$post_id = wp_insert_post( wp_slash( $post_data ), true );
					update_option( 'ur-popup-created', true );
				}
				return true;
			}
		}

	}
}

add_filter( 'user_registration_add_form_field_data', 'user_registration_form_honeypot_field_filter', 10, 2 );

if ( ! function_exists( 'user_registration_form_honeypot_field_filter' ) ) {
	/**
	 * Add honeypot field data to form data.
	 *
	 * @since 1.0.0
	 * @param array $form_data_array Form data parsed form form's post content.
	 * @param int   $form_id ID of the form.
	 */
	function user_registration_form_honeypot_field_filter( $form_data_array, $form_id ) {

		$enable_spam_protection = ur_get_single_post_meta( $form_id, 'user_registration_pro_spam_protection_by_honeypot_enable' );

		if ( 'yes' === $enable_spam_protection || '1' === $enable_spam_protection ) {
			$honeypot = (object) array(
				'field_key'       => 'honeypot',
				'general_setting' => (object) array(
					'label'       => 'Honeypot',
					'description' => '',
					'field_name'  => 'honeypot',
					'placeholder' => '',
					'required'    => 'no',
					'hide_label'  => 'no',
				),
			);
			array_push( $form_data_array, $honeypot );
		}
		return $form_data_array;
	}
}

add_action( 'user_registration_validate_honeypot_container', 'user_registration_validate_honeypot_container', 10, 4 );

if ( ! function_exists( 'user_registration_validate_honeypot_container' ) ) {

	/**
	 * Validate user honeypot to check if the field is filled with spams.
	 *
	 * @since 1.0.0
	 * @param object $data Data entered by the user.
	 * @param array  $filter_hook Filter for validation error message.
	 * @param int    $form_id ID of the form.
	 * @param array  $form_data_array All fields form data entered by user.
	 */
	function user_registration_validate_honeypot_container( $data, $filter_hook, $form_id, $form_data_array ) {
		$value = isset( $data->value ) ? $data->value : '';

		if ( '' !== $value ) {

			$form_data = array();

			foreach ( $form_data_array as $single_field_data ) {
					$form_data[ $single_field_data->field_name ] = $single_field_data->value;
			}

			// Log the spam entry.
			$logger = ur_get_logger();
			$logger->notice( sprintf( 'Spam entry for Form ID %d Response: %s', absint( $form_id ), print_r( $form_data, true ) ), array( 'source' => 'honeypot' ) );

			add_filter(
				$filter_hook,
				function ( $msg ) {
					return esc_html__( 'Registration Error. Your Registration has been blocked by Spam Protection.', 'user-registration' );
				}
			);
		}
	}
}

if ( ! function_exists( 'user_registration_pro_dasboard_card' ) ) {

	/**
	 * User Registration dashboard card.
	 *
	 * @param string $title Dashboard card title.
	 * @param string $body_class Dashboard card body class.
	 * @param html   $body Dashboard card body.
	 *
	 * @since 1.0.0
	 */
	function user_registration_pro_dasboard_card( $title, $body_class, $body ) {

		$card  = '';
		$card .= '<div class="user-registration-card ur-mb-6">';

		if ( '' !== $title ) {
			$card .= '<div class="user-registration-card__header">';
			$card .= '<h3 class="user-registration-card__title">' . esc_html( $title ) . '</h3>';
			$card .= '</div>';
		}

		$card .= '<div class="user-registration-card__body ' . esc_attr( $body_class ) . '">' . $body . '</div>';
		$card .= '</div>';

		return $card;
	}
}

if ( ! function_exists( 'user_registration_pro_approval_status_registration_overview_report' ) ) {

	/**
	 * Builds User Status card template based on form selected.
	 *
	 * @param int    $form_id ID of selected form.
	 * @param array  $overview Array of user datas at different settings.
	 * @param string $label Label for status card.
	 * @param string $approval_status Specific approval status for specific status cards .
	 * @param string $link_text View lists of specific approval status link text.
	 */
	function user_registration_pro_approval_status_registration_overview_report( $form_id, $overview, $label, $approval_status, $link_text ) {
		$ur_specific_form_user = '&ur_user_approval_status=' . $approval_status;

		if ( 'all' !== $form_id ) {
			$ur_specific_form_user .= '&ur_specific_form_user=' . $form_id;
		}

		$admin_url                          = admin_url( '', 'admin' ) . 'users.php?s&action=-1&new_role' . $ur_specific_form_user . '&ur_user_filter_action=Filter&paged=1&action2=-1&new_role2&ur_user_approval_status2&ur_specific_form_user2';
		$status_registration_overview_card  = '';
		$status_registration_overview_card .= '<div class="ur-col-lg-3 ur-col-md-6">';

		$body  = '';
		$body .= '<div class="ur-row ur-align-items-center">';
		$body .= '<div class="ur-col">';

		$body .= '<h4 class="ur-text-muted ur-mt-0">' . esc_html( $label ) . '</h4>';
		$body .= '<span class="ur-h2 ur-mr-1">' . esc_html( $overview ) . '</span>';
		$body .= '</div>';
		$body .= '<div class="ur-col-auto">';
		$body .= '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="23" y1="11" x2="17" y2="11"></line></svg>';
		$body .= '</div>';
		$body .= '<div class="ur-col-12">';
		$body .= '<a class="ur-d-flex ur-mb-0 ur-mt-2" href="' . esc_url( $admin_url ) . ' ">' . esc_html( $link_text ) . '</a>';
		$body .= '</div>';
		$body .= '</div>';

		$status_registration_overview_card .= user_registration_pro_dasboard_card( '', '', $body );

		$status_registration_overview_card .= '</div>';

		return $status_registration_overview_card;

	}
}

if ( ! function_exists( 'ur_exclude_fields_in_post_submssion' ) ) {

	/**
	 * Get the user registration form fields to exclude in post submission.
	 *
	 * @return array
	 */
	function ur_exclude_fields_in_post_submssion() {
		$fields_to_exclude  = array(
			'user_pass',
			'user_confirm_password',
			'password',
		);
		 $fields_to_exclude = apply_filters( 'ur_exclude_fields_in_post_submssion', $fields_to_exclude );
		return $fields_to_exclude;
	}
}

if ( ! function_exists( 'ur_send_form_data_to_custom_url' ) ) {
	/**
	 * Send form data to custom url after registration hook.
	 *
	 * @param  array $valid_form_data Form filled data.
	 * @param  int   $form_id         Form ID.
	 * @param  int   $user_id         User ID.
	 * @return void
	 */
	function ur_send_form_data_to_custom_url( $valid_form_data, $form_id, $user_id ) {

		$valid_form_data   = isset( $valid_form_data ) ? $valid_form_data : array();
		$fields_to_exclude = ur_exclude_fields_in_post_submssion();

		foreach ( $fields_to_exclude as $key => $value ) {

			if ( isset( $valid_form_data[ $value ] ) ) {
				unset( $valid_form_data[ $value ] );
			}
		}

		if ( null !== get_option( 'user_registration_pro_general_post_submission_settings' ) ) {
			$url          = get_option( 'user_registration_pro_general_post_submission_settings' );
			$single_field = array();
			foreach ( $valid_form_data as $data ) {
				$single_field[ $data->field_name ] = isset( $data->value ) ? $data->value : '';
			}

			if ( 'post_json' === get_option( 'user_registration_pro_general_setting_post_submission', array() ) ) {
				$headers = array( 'Content-Type' => 'application/json; charset=utf-8' );
				wp_remote_post(
					$url,
					array(
						'body'    => json_encode( $single_field ),
						'headers' => $headers,
					)
				);

			} elseif ( 'get' === get_option( 'user_registration_pro_general_setting_post_submission', array() ) ) {
				$url = $url . '?' . http_build_query( $single_field );
				wp_remote_get( $url );
			} else {
				wp_remote_post( $url, array( 'body' => $single_field ) );
			}
		}

	}
}

if ( ! function_exists( 'ur_prevent_concurrent_logins' ) ) {

	/**
	 * Validate if the maximum active logins limit reached.
	 *
	 * @param object $user User Object/WPError.
	 *
	 * @since  3.0.0
	 *
	 * @return object User object or error object.
	 */
	function ur_prevent_concurrent_logins( $user ) {

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$pass                = ! empty( $_POST['password'] ) ? $_POST['password'] : '';
		$ur_max_active_login = intval( get_option( 'user_registration_pro_general_setting_limited_login' ) );
		$user_id             = $user->ID;

		// Get current user's session.
		$sessions = WP_Session_Tokens::get_instance( $user_id );

		// Get all his active WordPress sessions
		$all_sessions = $sessions->get_all();
		$count        = count( $all_sessions );

		if ( $count >= $ur_max_active_login && wp_check_password( $pass, $user->user_pass, $user->ID ) ) {
			$user_id    = $user->ID;
			$user_email = $user->user_email;

			// Error message.
			$error_message = sprintf(
				'<strong>' .
							/* translators: %s Logout link */
				__( 'ERROR:', 'user-registration' ) . '</strong>' . __( 'Maximum no. of active logins found for this account. Please logout from another device to continue. %s', 'user-registration' ),
				"<a href='javascript:void(0)' class='user-registartion-force-logout' data-user-id='" . $user_id . "' data-email='" . $user_email . "'>" . __( 'Force Logout?', 'user-registration' ) . '</a>'
			);

			return new WP_Error( 'user_registration_error_message', $error_message );
		}

		return $user;
	}
}


if ( ! function_exists( 'user_registration_force_logout' ) ) {
	/**
	 * Destroy the session of user.
	 *
	 * @since  3.0.0
	 */
	function user_registration_force_logout() {

		if ( ! empty( $_GET['force-logout'] ) ) {
			$user_id  = intval( $_GET['force-logout'] );
			$sessions = WP_Session_Tokens::get_instance( $user_id );
			$sessions->destroy_all();
			wp_redirect( ur_get_page_permalink( 'myaccount' ) );
			exit;
		}
	}
}

if ( ! function_exists( 'user_registration_get_all_db_tables' ) ) {
	/**
	 * Get All Database Table List.
	 */
	function user_registration_get_all_db_tables() {
		global $wpdb;
		$results    = $wpdb->get_results( 'SHOW TABLES;', ARRAY_N );
		$tables     = array();
		$tables[''] = __( '-- Select Table Name --', 'user-registration' );
		foreach ( $results as $result ) {
			$tables[ $result[0] ] = $result[0];
		}
		return $tables;
	}
}

if ( ! function_exists( 'user_registration_get_columns_by_table' ) ) {

	/**
	 * Get list of Columns for specific table.
	 *
	 * @param string $table Table Name.
	 */
	function user_registration_get_columns_by_table( $table ) {
		global $wpdb;
		$column_list = array();
		if ( ! empty( $table ) && '0' != $table ) {
			$columns = $wpdb->get_results( 'SHOW COLUMNS FROM ' . $table ); //phpcs:ignore

			foreach ( $columns as $key => $column ) {
				$column_list[$column->Field] = $column->Field; //phpcs:ignore;
			}
		}
		return $column_list;
	}
}

if ( ! function_exists( 'ur_pro_get_form_fields' ) ) {
	/**
	 * Get form fields.
	 *
	 * @param int $form_id Registration Form ID.
	 * @return array|WP_Error
	 */
	function ur_pro_get_form_fields( $form_id ) {
		$form   = get_post( $form_id );
		$fields = array();

		if ( $form && 'user_registration' === $form->post_type ) {
			$form_field_array = json_decode( $form->post_content );

			if ( $form_field_array ) {

				foreach ( $form_field_array as $post_content_row ) {
					foreach ( $post_content_row as $post_content_grid ) {
						foreach ( $post_content_grid as $field ) {
							if ( isset( $field->field_key ) && ! in_array( $field->field_key, ur_pro_get_excluded_fields() ) ) {
								$field_name        = isset( $field->general_setting->field_name ) ? $field->general_setting->field_name : '';
								$field_label       = isset( $field->general_setting->label ) ? $field->general_setting->label : '';
								$field_description = isset( $field->general_setting->description ) ? $field->general_setting->description : '';
								$placeholder       = isset( $field->general_setting->placeholder ) ? $field->general_setting->placeholder : '';
								$options           = isset( $field->general_setting->options ) ? $field->general_setting->options : array();
								$field_key         = isset( $field->field_key ) ? ( $field->field_key ) : '';
								$field_type        = isset( $field->field_key ) ? ur_get_field_type( $field_key ) : '';
								$required          = isset( $field->general_setting->required ) ? $field->general_setting->required : '';
								$required          = 'yes' == $required ? true : false;
								$enable_cl         = isset( $field->advance_setting->enable_conditional_logic ) && ( '1' === $field->advance_setting->enable_conditional_logic || 'on' === $field->advance_setting->enable_conditional_logic ) ? true : false;
								$cl_map            = isset( $field->advance_setting->cl_map ) ? $field->advance_setting->cl_map : '';
								$custom_attributes = isset( $field->general_setting->custom_attributes ) ? $field->general_setting->custom_attributes : array();
								$default           = '';

								if ( isset( $field->general_setting->default_value ) ) {
									$default = $field->general_setting->default_value;
								} elseif ( isset( $field->advance_setting->default_value ) ) {
									$default = $field->advance_setting->default_value;
								}

								if ( empty( $field_label ) ) {
									$field_label_array = explode( '_', $field_name );
									$field_label       = join( ' ', array_map( 'ucwords', $field_label_array ) );
								}

								if ( ! empty( $field_name ) ) {
									$extra_params = array();

									switch ( $field_key ) {

										case 'radio':
										case 'select':
											$advanced_options        = isset( $field->advance_setting->options ) ? $field->advance_setting->options : '';
											$advanced_options        = explode( ',', $advanced_options );
											$extra_params['options'] = ! empty( $options ) ? $options : $advanced_options;
											$extra_params['options'] = array_map( 'trim', $extra_params['options'] );

											$extra_params['options'] = array_combine( $extra_params['options'], $extra_params['options'] );

											break;

										case 'checkbox':
											$advanced_options        = isset( $field->advance_setting->choices ) ? $field->advance_setting->choices : '';
											$advanced_options        = explode( ',', $advanced_options );
											$extra_params['options'] = ! empty( $options ) ? $options : $advanced_options;
											$extra_params['options'] = array_map( 'trim', $extra_params['options'] );

											$extra_params['options'] = array_combine( $extra_params['options'], $extra_params['options'] );

											break;

										case 'date':
											$date_format       = isset( $field->advance_setting->date_format ) ? $field->advance_setting->date_format : '';
											$min_date          = isset( $field->advance_setting->min_date ) ? str_replace( '/', '-', $field->advance_setting->min_date ) : '';
											$max_date          = isset( $field->advance_setting->max_date ) ? str_replace( '/', '-', $field->advance_setting->max_date ) : '';
											$set_current_date  = isset( $field->advance_setting->set_current_date ) ? $field->advance_setting->set_current_date : '';
											$enable_date_range = isset( $field->advance_setting->enable_date_range ) ? $field->advance_setting->enable_date_range : '';
											$extra_params['custom_attributes']['data-date-format'] = $date_format;

											if ( isset( $field->advance_setting->enable_min_max ) && 'true' === $field->advance_setting->enable_min_max ) {
												$extra_params['custom_attributes']['data-min-date'] = '' !== $min_date ? date_i18n( $date_format, strtotime( $min_date ) ) : '';
												$extra_params['custom_attributes']['data-max-date'] = '' !== $max_date ? date_i18n( $date_format, strtotime( $max_date ) ) : '';
											}
											$extra_params['custom_attributes']['data-default-date'] = $set_current_date;
											$extra_params['custom_attributes']['data-mode']         = $enable_date_range;
											break;

										case 'country':
											$class_name              = ur_load_form_field_class( $field_key );
											$extra_params['options'] = $class_name::get_instance()->get_selected_countries( $form_id, $field_name );
											break;

										case 'file':
											$extra_params['max_files'] = isset( $field->general_setting->max_files ) ? $field->general_setting->max_files : '';
											break;

										case 'phone':
											$extra_params['phone_format'] = isset( $field->general_setting->phone_format ) ? $field->general_setting->phone_format : '';
											break;
										case 'learndash_course':
											$extra_params['learndash_field_type'] = isset( $field->general_setting->learndash_field_type ) ? $field->general_setting->learndash_field_type : '';
											if ( isset( $field->advance_setting->enroll_type ) ) {
												if ( 'courses' === $field->advance_setting->enroll_type ) {
													$extra_params['options'] = function_exists( 'get_courses_list' ) ? get_courses_list() : array();
												} else {
													$extra_params['options'] = function_exists( 'get_groups_list' ) ? get_groups_list() : array();
												}
											}
											break;

										default:
											break;
									}

									$extra_params['default'] = $default;

									$fields[ 'user_registration_' . $field_name ] = array(
										'label'       => ur_string_translation( $form_id, 'user_registration_' . $field_name . '_label', $field_label ),
										'description' => ur_string_translation( $form_id, 'user_registration_' . $field_name . '_description', $field_description ),
										'type'        => $field_type,
										'placeholder' => ur_string_translation( $form_id, 'user_registration_' . $field_name . '_placeholder', $placeholder ),
										'field_key'   => $field_key,
										'required'    => $required,
									);

									if ( true === $enable_cl ) {
										$fields[ 'user_registration_' . $field_name ]['enable_conditional_logic'] = $enable_cl;
										$fields[ 'user_registration_' . $field_name ]['cl_map']                   = $cl_map;
									}

									if ( count( $custom_attributes ) > 0 ) {
										$extra_params['custom_attributes'] = $custom_attributes;
									}

									if ( isset( $fields[ 'user_registration_' . $field_name ] ) && count( $extra_params ) > 0 ) {
										$fields[ 'user_registration_' . $field_name ] = array_merge( $fields[ 'user_registration_' . $field_name ], $extra_params );
									}
									$filter_data = array(
										'fields'     => $fields,
										'field'      => $field,
										'field_name' => $field_name,
									);

									$filtered_data_array = apply_filters( 'user_registration_profile_account_filter_' . $field_key, $filter_data, $form_id );
									if ( isset( $filtered_data_array['fields'] ) ) {
										$fields = $filtered_data_array['fields'];
									}
								}// End if().
							}
						}// End foreach().
					}// End foreach().
				}// End foreach().
			}
		} else {
			return new WP_Error( 'form-not-found', __( 'Form not found!', 'user-registration' ) );
		}

		return apply_filters( 'user_registration_pro_form_field_list', $fields );
	}
}

if ( ! function_exists( 'ur_pro_get_excluded_fields' ) ) {
	/**
	 * Get Excluded fields.
	 */
	function ur_pro_get_excluded_fields() {
		$excluded_fields = array(
			'user_confirm_password',
			'user_confirm_email',
			'section_title',
		);

		return apply_filters( 'user_registration_pro_excluded_fields', $excluded_fields );
	}
}

if ( ! function_exists( 'user_registration_pro_auto_populate_supported_fields' ) ) {
	/**
	 * Get fields for which auto populate is supported
	 *
	 * @return array
	 */
	function user_registration_pro_auto_populate_supported_fields() {

		$fields = array(
			'display_name',
			'checkbox',
			'country',
			'date',
			'description',
			'email',
			'first_name',
			'last_name',
			'nickname',
			'number',
			'password',
			'radio',
			'select',
			'text',
			'textarea',
			'user_email',
			'user_login',
			'user_url',
			'invite_code',
		);

		return apply_filters(
			'user_registration_auto_populate_fields',
			$fields
		);
	}
}


if ( ! function_exists( 'user_registration_pro_profile_details_form_fields' ) ) {

	/**
	 * Get the user registration form fields to include in view profile.
	 *
	 * @param int   $form_id Id of the form through which user was registered.
	 * @param array $fields_to_include Fields to include.
	 * @return array
	 */
	function user_registration_pro_profile_details_form_fields( $form_id, $fields_to_include = array() ) {

		$post_content_array = ( $form_id ) ? UR()->form->get_form( $form_id, array( 'content_only' => true ) ) : array();

		$form_field_data_array = array();
		foreach ( $post_content_array as $row_index => $row ) {
			foreach ( $row as $grid_index => $grid ) {
				foreach ( $grid as $field_index => $field ) {
					if ( isset( $field->general_setting->field_name ) ) {
						$form_field_data_array[ $field->general_setting->field_name ] = array(
							'field_key' => $field->field_key,
							'label'     => $field->general_setting->label,
						);

						if ( in_array( $field->field_key, $fields_to_include ) ) {
							$form_field_data_array[ $field->general_setting->field_name ] = array(
								'field_key' => $field->field_key,
								'label'     => $field->general_setting->label,
							);
						}
					}
				}
			}
		}

		return $form_field_data_array;
	}
}

if ( ! function_exists( 'user_registration_pro_profile_details_form_field_datas' ) ) {

	/**
	 * Get the user registration form fields data for fields included in view profile.
	 *
	 * @param int   $form_id Id of the form through which user was registered.
	 * @param array $user_data All the datas of the user.
	 * @param array $form_field_data_array All the fields to be included in profile details page.
	 * @param array $field_to_include Field to include.
	 * @return array
	 */
	function user_registration_pro_profile_details_form_field_datas( $form_id, $user_data, $form_field_data_array, $field_to_include = array() ) {

		$user_data_to_show = array();
		foreach ( $user_data as $key => $value ) {

			if ( ! empty( $field_to_include ) && ! in_array( $key, $field_to_include ) ) {
				continue;
			}

			if ( isset( $form_field_data_array[ $key ] ) && '' !== $value ) {

				$user_data_to_show[ $key ] = array(
					'field_key' => $form_field_data_array[ $key ]['field_key'],
					'label'     => $form_field_data_array[ $key ]['label'],
					'value'     => $value,
				);

			}

			$fields_to_exclude = array_merge( ur_exclude_profile_details_fields(), apply_filters( 'user_registration_pro_excluded_fields_in_view_details_page', array( 'profile_picture', 'privacy_policy', 'password' ) ) );

			if ( isset( $user_data_to_show[ $key ]['field_key'] ) ) {
				if ( 'file' === $user_data_to_show[ $key ]['field_key'] && '' !== $user_data_to_show[ $key ]['value'] ) {
					$upload_data = array();
					$file_data   = explode( ',', $value );

					foreach ( $file_data as $attachment_key => $attachment_id ) {
						$file      = isset( $attachment_id ) ? wp_get_attachment_url( $attachment_id ) : '';
						$file_link = '<a href="' . esc_url( $file ) . '" target="_blank" >' . esc_html( basename( get_attached_file( $attachment_id ) ) ) . '</a>';
						array_push( $upload_data, $file_link );
					}
					// Check if value contains array.
					if ( is_array( $upload_data ) ) {
						$value = implode( ',', $upload_data );
					}

					$user_data_to_show[ $key ]['value'] = $value;
				}

				// For Country Field.
				if ( 'country' === $user_data_to_show[ $key ]['field_key'] && '' !== $user_data_to_show[ $key ]['value'] ) {
					$country_class                      = ur_load_form_field_class( $user_data_to_show[ $key ]['field_key'] );
					$countries                          = $country_class::get_instance()->get_country();
					$user_data_to_show[ $key ]['value'] = isset( $countries[ $value ] ) ? $countries[ $value ] : $value;
				}

				// For checkbox and multiselect field.
				if ( ( 'checkbox' === $user_data_to_show[ $key ]['field_key'] || 'multi_select2' === $user_data_to_show[ $key ]['field_key'] ) && '' !== $user_data_to_show[ $key ]['value'] ) {
					$user_data_to_show[ $key ]['value'] = is_array( $user_data_to_show[ $key ]['value'] ) ? implode( ',', $user_data_to_show[ $key ]['value'] ) : $user_data_to_show[ $key ]['value'];
				}

				if ( in_array( $key, $fields_to_exclude ) ) {
					unset( $user_data_to_show[ $key ] );
				}
			}
		}

		return $user_data_to_show;
	}
}

if ( ! function_exists( 'user_registration_pro_profile_details_form_keys_to_include' ) ) {

	/**
	 * Get the user registration form fields keys of fields to include in view profile.
	 *
	 * @param array $fields_to_include Field to include.
	 * @param array $form_field_data_array All the fields to be included in profile details page.
	 * @return array
	 */
	function user_registration_pro_profile_details_form_keys_to_include( $fields_to_include, $form_field_data_array ) {
		$fields_keys_to_include = array();

		foreach ( $form_field_data_array as $field_id => $field_data ) {
			if ( in_array( $field_data['field_key'], $fields_to_include ) || in_array( $field_id, $fields_to_include ) ) {
				array_push( $fields_keys_to_include, $field_id );
			}
		}
		return $fields_keys_to_include;
	}
}

/**
 * UR Validate Unique Field.
 */
if ( ! function_exists( 'ur_validate_unique_field' ) ) {
	/**
	 *  Validate unique field value.
	 *
	 * @param array $args search args.
	 * @return array $ids ids.
	 */
	function ur_validate_unique_field( $args ) {
		global $wpdb;
		$args    = wp_parse_args(
			$args,
			array(
				'limit'      => 10,
				'ur_form_id' => 0,
				'offset'     => 0,
				'order'      => 'DESC',
				'orderby'    => 'ID',
				'meta_query' => array(
					'key'   => 'ur_form_id',
					'value' => $args['ur_form_id'],
				),
			)
		);
		$query   = array();
		$query[] = "SELECT DISTINCT {$wpdb->prefix}usermeta.user_id FROM {$wpdb->prefix}usermeta INNER JOIN {$wpdb->prefix}users WHERE {$wpdb->prefix}usermeta.user_id = {$wpdb->prefix}users.ID";

		if ( ! empty( $args['search'] ) ) {
			if ( 'user_url' === $args['field_name'] ) {
				$query[] = $wpdb->prepare( 'AND user_url = %s', $args['search'] );
			} elseif ( 'display_name' === $args['field_name'] ) {
				$query[] = $wpdb->prepare( 'AND display_name = %s', $args['search'] );
			} else {
				$query[] = $wpdb->prepare( 'AND meta_value = %s', $args['search'] );
			}
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( implode( ' ', $query ), ARRAY_A );
		$ids     = wp_list_pluck( $results, 'user_id' );
		return $ids;
	}
}

if ( ! function_exists( 'ur_pro_add_bulk_options' ) ) {
	/**
	 * Bulk Add Options.
	 *
	 * @param array  $general_setting General Setting.
	 * @param string $id ID.
	 * @return array
	 */
	function ur_pro_add_bulk_options( $general_setting, $id ) {
		$fields = array( 'user_registration_checkbox', 'user_registration_select', 'user_registration_radio' );

		if ( in_array( $id, $fields, true ) ) {
			$general_setting['options'] = array_merge( $general_setting['options'], array( 'add_bulk_options' => sprintf( '<a href="#" class="ur-toggle-bulk-options after-label-description" data-bulk-options-label="%s" data-bulk-options-tip="%s" data-bulk-options-button="%s">%s</a>', esc_attr__( 'Add Bulk Options', 'user-registration' ), esc_attr__( 'To add multiple options at once, press enter key after each option.', 'user-registration' ), esc_attr__( 'Add New Options', 'user-registration' ), esc_html__( 'Bulk Add', 'user-registration' ) ) ) );
		}

		return $general_setting;
	}
}

add_filter( 'user_registration_field_options_general_settings', 'ur_pro_add_bulk_options', 10, 2 );



if ( ! function_exists( 'ur_get_all_form_fields' ) ) {

	/**
	 * Get all the form Fields.
	 *
	 * @param array $strip_fields Stripe Fields.
	 * @return array $form_field_lists Form Field List .
	 */
	function ur_get_all_form_fields( $strip_fields ) {
		$all_forms        = ur_get_all_user_registration_form();
		$form_field_lists = array();

		foreach ( $all_forms as $form_id => $form_label ) {
			$post                                   = get_post( $form_id );
			$post_content                           = isset( $post->post_content ) ? $post->post_content : '';
			$post_content_array                     = isset( $post_content ) ? json_decode( $post_content ) : array();
			$specific_form_field_list               = array();
			$specific_form_field_list['form_label'] = $form_label;
			if ( is_array( $post_content_array ) || is_object( $post_content_array ) ) {
				foreach ( $post_content_array as $post_content_row ) {
					foreach ( $post_content_row as $post_content_grid ) {
						foreach ( $post_content_grid as $field ) {
							if ( isset( $field->field_key ) && isset( $field->general_setting->field_name ) ) {
								if ( in_array( $field->field_key, $strip_fields, true ) ) {
									continue;
								}
								$specific_form_field_list['field_list'][ $field->general_setting->field_name ] = $field->general_setting->label;
								$specific_form_field_list['field_key'][ $field->general_setting->field_name ]  = $field->field_key;
							}
						}
					}
				}
			}
			array_push( $form_field_lists, $specific_form_field_list );
		}

		return $form_field_lists;
	}
}

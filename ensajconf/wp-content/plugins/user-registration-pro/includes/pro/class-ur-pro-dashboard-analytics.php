<?php
/**
 * Admin class
 *
 * User_Registration_Pro Admin
 *
 * @package User_Registration_Pro_Dashboard_Analytics.
 * @since  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'User_Registration_Pro_Dashboard_Analytics' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @since 1.0.0
	 */
	class User_Registration_Pro_Dashboard_Analytics {

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 */
		public function __construct() {}

		/**
		 * Handles output of the addons page in admin.
		 *
		 * @param int    $form_id ID of selected form.
		 * @param string $selected_date Date selected by the user.
		 */
		public function output( $form_id, $selected_date ) {
			$overview  = $this->registration_overview( $form_id, $selected_date );
			$response  = '';
			$response .= '<div class="ur-row">';
			$response .= $this->user_registration_pro_new_registration_overview_report( $overview );
			$response .= user_registration_pro_approval_status_registration_overview_report( $form_id, $overview['weekly_data']['approved_users'],  __('Approved Users', 'user-registration'), 'approved', __('View Approved Users', 'user-registration') );
			$response .= user_registration_pro_approval_status_registration_overview_report( $form_id, $overview['weekly_data']['pending_users'],  __('Pending Users', 'user-registration'), 'pending', __('View Pending Users', 'user-registration') );
			$response .= user_registration_pro_approval_status_registration_overview_report( $form_id, $overview['weekly_data']['denied_users'],  __('Denied Users', 'user-registration'), 'denied', __('View Denied Users', 'user-registration') );
			$response .= '</div>';
			if ( 'all' === $form_id ) {
				$response .= '<div class="ur-row">';
				$response .= '<div class="ur-col-lg-3 ur-col-md-4">';
				$response .= $this->user_registration_pro_specific_form_registration_overview();
				$response .= '</div>';
				$response .= '<div class="ur-col-lg-9 ur-col-md-8">';
				$response .= $this->user_registration_pro_registration_overview_report();
				$response .= '</div>';
				$response .= '</div>';
			} else {
				$response .= $this->user_registration_pro_registration_overview_report();
			}

			$response .= '</div>';

			return array(
				'message'     => $response,
				'user_report' => $overview,
			);
		}

		/**
		 * Builds Total Overview card template.
		 *
		 * @param array $overview Array of user datas at different settings.
		 */
		public function user_registration_pro_total_overview_report( $form_id, $overview ) {
			$body  = '';
			$body .= '<div class="ur-row ur-no-gutter ur-mb-2">';
			$body .= '<div class="ur-col-6 ur-border-bottom ur-border-right">';
			if ( 'all' === $form_id ) {
				$body .= '<h4> Total Form Visits:</h4><div class="ur-h2 ur-mb-2">' . esc_html( $overview['total_form_visits'] ) . '</div>';
			} else {
				$body .= '<h4> Total Form Visits:</h4><div class="ur-h2 ur-mb-2">' . esc_html( $overview['specific_form_visits'][ $form_id ] ) . '</div>';
			}
			$body .= '</div>';
			$body .= '<div class="ur-col-6 ur-border-bottom">';
			$body .= '<h4> Total Registration:</h4><div class="ur-h2 ur-mb-2">' . esc_html( $overview['total_registration'] ) . '</div>';
			$body .= '</div>';

			if ( 'all' === $form_id ) {
				$body .= '<div class="ur-col-6 ur-border-right">';
				$body .= '<h4> Form Registration:</h4><div class="ur-h2 ur-mb-2">' . esc_html( $overview['total_overview']['total_form_registration'] ). '</div>';
				$body .= '</div>';
				$body .= '<div class="ur-col-6">';
				$body .= '<h4> Social Registration:</h4><div class="ur-h2 ur-mb-2">' . esc_html( $overview['total_overview']['total_social_registration'] ) . '</div>';
				$body .= '</div>';
			}

			$body .= '</div>';
			$body .= '<div class="user-registration-card ur-bg-light ur-border-0 ur-mb-2">';
			$body .= '<div class="user-registration-card__body">';
			$body .= '<div class="ur-row">';
			$body .= '<div class="ur-col">';
			$body .= '<h4 class="ur-mt-0"> Approved Users: </h4><span class="ur-h2">' . esc_html( $overview['total_overview']['approved_users'] ) . '</span>';
			$body .= '</div>';
			$body .= '</div>';
			$body .= '</div>';
			$body .= '</div>';
			$body .= '<a class="user-registration-card ur-bg-light ur-border-0 ur-mb-2" href="' . admin_url( '', 'admin' ) . 'users.php?s&action=-1&new_role&ur_user_approval_status=pending&ur_user_filter_action=Filter&paged=1&action2=-1&new_role2&ur_user_approval_status2" target="_blank">';
			$body .= '<div class="user-registration-card__body">';
			$body .= '<div class="ur-row ur-align-items-center">';
			$body .= '<div class="ur-col">';
			$body .= '<h4 class="ur-mt-0"> Pending Users:</h4><span class="ur-h2">' . esc_html( $overview['total_overview']['pending_users'] ) . '</span>';
			$body .= '</div>';
			$body .= '<div class="ur-col-auto"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg></div>';
			$body .= '</div>';
			$body .= '</div>';
			$body .= '</a>';
			$body .= '<div class="user-registration-card ur-bg-light ur-border-0 ur-mb-0">';
			$body .= '<div class="user-registration-card__body">';
			$body .= '<div class="ur-row">';
			$body .= '<div class="ur-col">';
			$body .= '<h4 class="ur-mt-0"> Denied Users:</h4><span class="ur-h2">' . esc_html( $overview['total_overview']['denied_users'] ) . '</span>';
			$body .= '</div>';
			$body .= '</div>';
			$body .= '</div>';
			$body .= '</div>';

			$total_overview_card = user_registration_pro_dasboard_card( __('Total Overview', 'user-registration'), '', $body );
			return $total_overview_card;
		}

		/**
		 * Builds New Registration Overview card template.
		 *
		 * @param array $overview Array of user datas at different settings.
		 */
		public function user_registration_pro_new_registration_overview_report( $overview ) {
			$body        = '';
			$body       .= '<div class="ur-row ur-align-items-center">';
			$body       .= '<div class="ur-col">';
			$body       .= '<h4 class="ur-text-muted ur-mt-0">' . esc_html__( 'Total Registration', 'user-registration' ) . '</h4>';
			$body       .= '<span class="ur-h2 ur-mr-1">' . esc_html( $overview['weekly_data']['new_registration'] ) . '</span>';
			$batch_class = ( 0 > $overview['new_registration_comparision_percentage'] ) ? 'user-registration-badge--danger-subtle' : 'user-registration-badge--success-subtle';
			$operator    = ( 0 > $overview['new_registration_comparision_percentage'] ) ? '' : '+';
			$body       .= '<span class="user-registration-badge ' . esc_attr( $batch_class ) . '">' . $operator . esc_html( $overview['new_registration_comparision_percentage'] ) . '%</span>';
			$body       .= '</div>';
			$body       .= '<div class="ur-col-auto">';
			$body       .= '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user-plus"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>';
			$body       .= '</div>';
			$body       .= '<div class="ur-col-12">';
			$body       .= '<p class="ur-d-flex ur-mb-0 ur-mt-2">Over previous ' . esc_html( $overview['weekly_data']['date_difference'] ) . '</p>';
			$body       .= '</div>';
			$body       .= '</div>';

			$new_registration_overview_card  = '';
			$new_registration_overview_card .= '<div class="ur-col-lg-3 ur-col-md-6">';
			$new_registration_overview_card .= user_registration_pro_dasboard_card( '', '', $body );
			$new_registration_overview_card .= '</div>';
			return $new_registration_overview_card;

		}

		/**
		 * Builds chart card template for overall user registration.
		 */
		public function user_registration_pro_registration_overview_report() {
			$body = '
			<canvas id="user-registration-pro-registration-overview-chart-report-area">Your browser does not support the canvas element.</canvas>
			<div class="user-registration-pro-registration-overview-chart-report-legends ur-border-top ur-mt-3"></div>
			';

			$registration_overview_report_card  = '';
			$registration_overview_report_card .= user_registration_pro_dasboard_card( __( 'Registration Overview', 'user-registration' ), 'user-registration-total-registration-chart', $body );
			return $registration_overview_report_card;

		}

		/**
		 * Builds chart card template for user registered with specific form.
		 */
		public function user_registration_pro_specific_form_registration_overview() {
			$body = '
			<canvas id="user-registration-pro-specific-form-registration-overview-chart-report-area">Your browser does not support the canvas element.</canvas>
			<div class="user-registration-pro-specific-form-registration-overview-chart-report-legends ur-border-top ur-mt-3"></div>
			';

			$specific_form_registration_overview_card = user_registration_pro_dasboard_card( __( 'Registration Source', 'user-registration' ), 'user-registration-specific-registration-chart', $body );
			return $specific_form_registration_overview_card;

		}


		/**
		 * Calculates overall user registration datas for display in dashboard.
		 *
		 * @param int    $form_id ID of selected form.
		 * @param string $selected_date Date selected by the user.
		 */
		public function registration_overview( $form_id, $selected_date ) {
			global $wpdb;
			$approved_users              = 0;
			$pending_users               = 0;
			$denied_users                = 0;
			$total_form_registration     = 0;
			$total_social_registration   = 0;
			$new_registration_percentage = 0;

			if ( 'all' === $form_id ) {
				$form_users = get_users(
					array(
						'meta_key' => 'ur_form_id',
					)
				);

				$social_user_results = $wpdb->get_results(
					"SELECT {$wpdb->prefix}users.ID, {$wpdb->prefix}usermeta.meta_key FROM {$wpdb->prefix}users, {$wpdb->prefix}usermeta WHERE {$wpdb->prefix}users.ID = {$wpdb->prefix}usermeta.user_id AND {$wpdb->prefix}usermeta.meta_key LIKE 'user_registration_social_connect_%_username'",
					ARRAY_N
				);

				$total_form_registration   = count( $form_users );
				$total_social_registration = count( $social_user_results );

				// Check if social registration is synced to any of the forms and add count.
				foreach ( $social_user_results as $user ) {
					$user_form = get_user_meta( $user[0], 'ur_form_id', true );

					if ( isset( $user_form ) || (int) $form_id === (int) $user_form ) {
						$total_form_registration--;
					}
				}
				$total_registration = $total_form_registration + $total_social_registration;
			} else {
				$form_users = get_users(
					array(
						'meta_key'   => 'ur_form_id',
						'meta_value' => $form_id,
					)
				);

				$total_registration = count( $form_users );

			}

			foreach ( $form_users as  $user ) {
				$user_form = get_user_meta( $user->ID, 'ur_form_id', true );

				if ( $user_form ) {
					$user_approval_status_list = $this->user_registration_pro_approval_status_list( $user->ID );
					$approved_users            = $approved_users + $user_approval_status_list['approved_users'];
					$pending_users             = $pending_users + $user_approval_status_list['pending_users'];
					$denied_users              = $denied_users + $user_approval_status_list['denied_users'];
				}
			}
			$date_range_data                    = $this->user_registration_pro_user_list_by_date( $form_id, $selected_date );
			$comparision_data                   = $this->user_registration_pro_comparision_report( $form_id, $selected_date );
			$specific_form_registration_details = $this->user_registration_pro_specific_form_datas();

			if ( $total_registration !== 0 ) {
				$new_registration_percentage = round( ( $date_range_data['new_registration'] / $total_registration ) * 100 );
			}

			$new_registration_comparision_percentage = $this->user_registration_pro_calculate_percentage( $date_range_data['new_registration'], $comparision_data['new_registration'] );

			$date_range_data['new_registration_percentage'] = $new_registration_percentage;

			$report = array(
				'total_registration'                      => $total_registration,
				'total_overview'                          => array(
					'total_form_registration'   => $total_form_registration,
					'total_social_registration' => $total_social_registration,
					'approved_users'            => $approved_users,
					'pending_users'             => $pending_users,
					'denied_users'              => $denied_users,
				),
				'weekly_data'                             => $date_range_data,
				'new_registration_comparision_percentage' => $new_registration_comparision_percentage,
				'specific_form_registration'              => $specific_form_registration_details,
			);

			return $report;
		}

		/**
		 * Calculates overall user registration datas for display in dashboard based on date provided.
		 *
		 * @param int    $form_id ID of selected form.
		 * @param string $selected_date Date selected by the user.
		 */
		public function user_registration_pro_user_list_by_date( $form_id, $selected_date ) {
			// Get last week date.
			if ( strpos( $selected_date, 'to' ) !== false ) {
				list( $date_range_start, $date_range_end ) = explode( 'to', $selected_date );
				$start_date                                = strtotime( $date_range_start );
				$end_date                                  = strtotime( $date_range_end );
				$incrementor                               = DAY_IN_SECONDS;
			} else {
				$end_date = strtotime( 'now' );
				if ( 'Day' === $selected_date ) {
					$start_date  = strtotime( 'now' ) - DAY_IN_SECONDS;
					$incrementor = HOUR_IN_SECONDS;
				} elseif ( 'Month' === $selected_date ) {
					$start_date  = strtotime( 'now' ) - MONTH_IN_SECONDS;
					$incrementor = DAY_IN_SECONDS;
				} else {
					$start_date  = strtotime( 'now' ) - WEEK_IN_SECONDS;
					$incrementor = DAY_IN_SECONDS;
				}
			}

			return $this->user_regsitartion_date_range_data( $form_id, $selected_date, $start_date, $end_date, $incrementor );
		}

		/**
		 * Calculates user status datas for display in dashboard.
		 *
		 * @param int $user ID of the user.
		 */
		public function user_registration_pro_approval_status_list( $user ) {
			$approved_users    = 0;
			$pending_users     = 0;
			$denied_users      = 0;
			$user_status       = get_user_meta( $user, 'ur_user_status', true );
			$user_email_status = get_user_meta( $user, 'ur_confirm_email', true );

			if ( '' === $user_status && '' === $user_email_status ) {
				$approved_users++;
			} elseif ( '' !== $user_status && '' === $user_email_status ) {
				if ( 1 == $user_status ) {
					$approved_users++;
				} elseif ( 0 == $user_status ) {
					$pending_users++;
				} else {
					$denied_users++;
				}
			} elseif ( ( '' === $user_status && '' !== $user_email_status ) || ( '' !== $user_status && '' !== $user_email_status ) ) {
				if ( 1 == $user_email_status[0] ) {
					$approved_users++;
				} elseif ( 0 == $user_email_status[0] ) {
					$pending_users++;
				}
			}

			return array(
				'approved_users' => $approved_users,
				'pending_users'  => $pending_users,
				'denied_users'   => $denied_users,
			);
		}

		/**
		 * Calculates overall user registration datas for specific form to display in dashboard.
		 */
		public function user_registration_pro_specific_form_datas() {
			global $wpdb;
			$forms                      = ur_get_all_user_registration_form();
			$specific_form_registration = [];

			foreach ( $forms as $form_id => $form_title ) {

				$args = array(
					'meta_key'   => 'ur_form_id',
					'meta_value' => $form_id,
				);

				$users                                     = get_users( $args );
				$specific_form_registration[ $form_title ] = count( $users );
			}

			return $specific_form_registration;
		}

		/**
		 * Calculates overall user registration comparision datas for display in dashboard based on date provided.
		 *
		 * @param int    $form_id ID of selected form.
		 * @param string $selected_date Date selected by the user.
		 */
		public function user_registration_pro_comparision_report( $form_id, $selected_date ) {
			global $wpdb;
			// Get last week date.
			if ( strpos( $selected_date, 'to' ) !== false ) {
				list( $date_range_start, $date_range_end ) = explode( 'to', $selected_date );
				$date_difference                           = human_time_diff( strtotime( $date_range_start ), strtotime( $date_range_end ) );
				$start_date                                = strtotime( ".$date_range_start - $date_difference." );
				$end_date                                  = strtotime( ". $date_range_end - $date_difference." );
				$incrementor                               = DAY_IN_SECONDS;
			} else {
				$end_date = strtotime( 'now' );
				if ( 'Day' === $selected_date ) {
					$start_date  = strtotime( 'now' ) - DAY_IN_SECONDS * 2;
					$end_date    = strtotime( 'now' ) - DAY_IN_SECONDS;
					$incrementor = HOUR_IN_SECONDS;
				} elseif ( 'Month' === $selected_date ) {
					$start_date  = strtotime( 'now' ) - MONTH_IN_SECONDS * 2;
					$end_date    = strtotime( 'now' ) - MONTH_IN_SECONDS;
					$incrementor = DAY_IN_SECONDS;
				} else {
					$start_date  = strtotime( 'now' ) - WEEK_IN_SECONDS * 2;
					$end_date    = strtotime( 'now' ) - WEEK_IN_SECONDS;
					$incrementor = DAY_IN_SECONDS;
				}
			}

			return $this->user_regsitartion_date_range_data( $form_id, $selected_date, $start_date, $end_date, $incrementor );
		}

		/**
		 * Calculates date range user registration datas for display in dashboard based on date provided.
		 *
		 * @param int    $form_id ID of selected form.
		 * @param string $selected_date Date selected by the user.
		 * @param string $start_date Start date selected by the user.
		 * @param string $end_date End date selected by the user.
		 * @param string $incrementor Time incrementor.
		 */
		public function user_regsitartion_date_range_data( $form_id, $selected_date, $start_date, $end_date, $incrementor ) {
			global $wpdb;
			$new_registration = 0;
			$approved_users   = 0;
			$pending_users    = 0;
			$denied_users     = 0;
			$weekly_data      = array();

			for ( $i = $start_date; $i <= $end_date;  $i = $i + $incrementor ) {

				if ( 'Day' === $selected_date ) {
					$date = date( 'Y-m-d h', $i );
				} else {
					$date = date( 'Y-m-d', $i );
				}

				if ( 'all' === $form_id ) {
					// Query for users based on the user registered date
					$users = $wpdb->get_results(
						"SELECT {$wpdb->prefix}users.ID, {$wpdb->prefix}usermeta.meta_key FROM {$wpdb->prefix}users, {$wpdb->prefix}usermeta WHERE {$wpdb->prefix}users.ID = {$wpdb->prefix}usermeta.user_id AND {$wpdb->prefix}usermeta.meta_key = 'ur_form_id' AND {$wpdb->prefix}users.user_registered LIKE '{$date}%' ",
						ARRAY_N
					);
				} else {
					$users = $wpdb->get_results(
						"SELECT {$wpdb->prefix}users.ID FROM {$wpdb->prefix}users, {$wpdb->prefix}usermeta WHERE {$wpdb->prefix}users.ID = {$wpdb->prefix}usermeta.user_id AND {$wpdb->prefix}usermeta.meta_key = 'ur_form_id' AND {$wpdb->prefix}usermeta.meta_value = '{$form_id}' AND {$wpdb->prefix}users.user_registered LIKE '{$date}%' ",
						ARRAY_N
					);
				}

				// Query for users based on the user registered date and form_id
				$approved_users_in_a_day = 0;
				$pending_users_in_a_day  = 0;
				$denied_users_in_a_day   = 0;

				foreach ( $users as $user ) {
					$user_data = get_userdata( $user[0] );

					if ( 'Day' === $selected_date ) {
						$user_registered_date = date( 'Y-m-d h', strtotime( $user_data->data->user_registered ) );
					} else {
						$user_registered_date = date( 'Y-m-d', strtotime( $user_data->data->user_registered ) );
					}

					if ( $date === $user_registered_date ) {
						$user_approval_status_list = $this->user_registration_pro_approval_status_list( $user[0] );
						$approved_users_in_a_day   = $approved_users_in_a_day + $user_approval_status_list['approved_users'];
						$pending_users_in_a_day    = $pending_users_in_a_day + $user_approval_status_list['pending_users'];
						$denied_users_in_a_day     = $denied_users_in_a_day + $user_approval_status_list['denied_users'];
					}
				}

					$new_registration_in_a_day = count( $users );
					$new_registration          = $new_registration + $new_registration_in_a_day;
					$approved_users            = $approved_users + $approved_users_in_a_day;
					$pending_users             = $pending_users + $pending_users_in_a_day;
					$denied_users              = $denied_users + $denied_users_in_a_day;

				if ( 'Day' === $selected_date ) {
					$date = date( 'h A', $i );
				}

					$weekly_data[ $date ] = array(
						'new_registration_in_a_day' => $new_registration_in_a_day,
						'approved_users_in_a_day'   => $approved_users_in_a_day,
						'pending_users_in_a_day'    => $pending_users_in_a_day,
						'denied_users_in_a_day'     => $denied_users_in_a_day,
					);

			}

			$approved_users_percentage = 0;
			$pending_users_percentage  = 0;
			$denied_users_percentage   = 0;

			$date_difference = human_time_diff( $start_date, $end_date );

			if ( $new_registration !== 0 ) {
				$approved_users_percentage = round( ( $approved_users / $new_registration ) * 100 );
				$pending_users_percentage  = round( ( $pending_users / $new_registration ) * 100 );
				$denied_users_percentage   = round( ( $denied_users / $new_registration ) * 100 );
			}

			return array(
				'new_registration'          => $new_registration,
				'approved_users'            => $approved_users,
				'approved_users_percentage' => $approved_users_percentage,
				'pending_users'             => $pending_users,
				'pending_users_percentage'  => $pending_users_percentage,
				'denied_users'              => $denied_users,
				'denied_users_percentage'   => $denied_users_percentage,
				'date_difference'           => $date_difference,
				'daily_data'                => $weekly_data,
			);
		}

		/**
		 * Calculates Growth percentage for display in dashboard based on date provided.
		 *
		 * @param int $current_data New user registration data at date selected by the user.
		 * @param int $comparision_data Previous user registration data at date difference of the date selected by the user.
		 */
		public function user_registration_pro_calculate_percentage( $current_data, $comparision_data ) {
			$comparision_percentage = 0;

			if ( $comparision_data !== 0 ) {
				$comparision_percentage = round( ( ( $current_data - $comparision_data ) / $comparision_data ) * 100 );
			} else {
				$comparision_percentage = $current_data * 100;
			}
			return $comparision_percentage;
		}
	}
}

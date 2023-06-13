<?php
/**
 * User Registration Pro Popup
 *
 * Shows user registration popup form
 *
 * @author  WPEverest
 * @package UserRegistrationPro/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$all_forms = ur_get_all_user_registration_form();
?>
<div class="wrap user-registration-pro-dashboard">
	<div class="ur-container-full">
		<div class="user-registration-pro-dashboard__header">
			<div class="ur-d-flex ur-align-items-center ur-mb-2">
				<h3 class="wp-heading-inline">User Registration Overview</h3>
				<div class="major-publishing-actions wp-clearfix ur-ml-auto">
					<div class="publishing-action">
						<select class="user-registration-pro-dashboard-select-form">
							<option value="all">All Forms</option>
							<?php
							foreach ( $all_forms as $form_id => $form_title ) {
								?>
									<option value="<?php echo esc_attr( $form_id ); ?>">
									<?php echo esc_html( $form_title ); ?>
									</option>
								<?php
							}
							?>
						</select>
						<div class="subsubsub ur-d-flex ur-mt-0">
							<div class="user-registration-button-group ur-mr-1">
								<a class="button button-tertiary user-registration-pro-date-switcher user-registration-pro-dashboard-day-report">Day</a>
								<a class="button button-tertiary user-registration-pro-date-switcher user-registration-pro-dashboard-week-report is-active">Week</a>
								<a class="button button-tertiary user-registration-pro-date-switcher user-registration-pro-dashboard-month-report">Month</a>
							</div>
							<div class="ur-mr-1">
								<input type="hidden" class="user-registration-pro-date-checker" />
								<input data-id="date-range-selector" type="text" class="input-text  user-registration-pro-date-range-selector" name="date-range-selector" placeholder="Select date range" id="date-range-selector"/>
							</div>
						</div>
					</div><!-- END .publishing-action -->
				</div><!-- END .major-publishing-actions -->
			</div>
		</div>
		<div class="user-registration-pro-dashboard__body">
		</div>
		<div class="user-registration-pro-dashboard__footer">
		</div>
	</div>
</div>
<?php

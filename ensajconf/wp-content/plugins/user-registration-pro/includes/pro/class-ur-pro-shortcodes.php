<?php
/**
 * User Registration Pro Shortcodes.
 *
 * @class    User_Registration_Pro_Shortcodes
 * @version  1.0.0
 * @package  UserRegistrationPro/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User_Registration_Pro_Shortcodes Class
 */
class User_Registration_Pro_Shortcodes {

	/**
	 * Multipart parts if any.
	 *
	 * @var bool
	 */
	public static $parts = false;

	/**
	 * Init Shortcodes.
	 */
	public static function init() {
		$shortcodes = array(
			'user_registration_popup'                => __CLASS__ . '::popup',
			'user_registration_view_profile_details' => __CLASS__ . '::view_profile_details',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}
	}

	/**
	 * Shortcode Wrapper.
	 *
	 * @param string[] $function Callback function.
	 * @param array    $atts Attributes supplied in shortcode.
	 * @param array    $wrapper Modal wrapper.
	 *
	 * @return string
	 */
	public static function shortcode_wrapper(
		$function,
		$atts = array(),
		$wrapper = array(
			'class'  => 'user-registration-modal',
			'before' => null,
			'after'  => null,
		)
	) {
		ob_start();

		echo empty( $wrapper['before'] ) ? '<div id="user-registration" class="' . esc_attr( $wrapper['class'] ) . '">' : wp_kses_post( $wrapper['before'] );
		call_user_func( $function, $atts );
		echo empty( $wrapper['after'] ) ? '</div>' : wp_kses_post( $wrapper['after'] );

		return ob_get_clean();
	}

	/**
	 * User Registration Pro Popup shortcode.
	 *
	 * @param array $atts Attributes supplied in shortcode.
	 */
	public static function popup( $atts ) {

		if ( empty( $atts ) || ! isset( $atts['id'] ) ) {
			return '';
		}

		ob_start();
		self::render_popup( $atts );
		return ob_get_clean();
	}

	/**
	 * User Registration Pro View Profile Details shortcode.
	 *
	 * @param array $atts Attributes supplied in shortcode.
	 */
	public static function view_profile_details( $atts ) {

		ob_start();
		self::render_profile_details( $atts );
		return ob_get_clean();
	}

	/**
	 * Output for popup.
	 *
	 * @since 1.0.1 Recaptcha only
	 * @param array $attributes Attributes supplied in shortcode.
	 */
	public static function render_popup( $attributes ) {
		$popup_id = $attributes['id'];
		$post     = get_post( $popup_id );

		if ( isset( $post ) && isset( $post->post_content ) ) {
			$popup_content = json_decode( $post->post_content );

			$popup_status = isset( $popup_content->popup_status ) ? $popup_content->popup_status : '';

			if ( $popup_status ) {
				$current_user_capability = apply_filters( 'ur_registration_user_capability', 'create_users' );

				if ( ( is_user_logged_in() && current_user_can( $current_user_capability ) ) || ! is_user_logged_in() ) {
					$display = 'display:block;';

					include UR_ABSPATH . 'templates/pro/popup-registration.php';
				}
			}
		} else {
			echo '<h2>' . esc_html__( 'Popup not found', 'user-registration' ) . '</h2>';
		}
	}

	/**
	 * Render profile details.
	 *
	 * @param array $atts Attributes supplied in shortcode.
	 */
	public static function render_profile_details( $atts ) {
		$user = wp_get_current_user();

		if( is_user_logged_in( $user ) ) {
			$user_id = get_current_user_id();

			wp_enqueue_style( 'user-registration-pro-frontend-style' );

			$user_extra_fields     = ur_get_user_extra_fields( $user_id );
			$user_data = (array) get_userdata( $user_id )->data;
			$user_data['first_name'] = get_user_meta( $user_id, 'first_name', true );
			$user_data['last_name'] = get_user_meta( $user_id, 'last_name', true );
			$user_data['description'] = get_user_meta( $user_id, 'description', true );
			$user_data['nickname'] = get_user_meta( $user_id, 'nickname', true );
			$user_data             = array_merge( $user_data, $user_extra_fields );

			$form_id               = ur_get_form_id_by_userid( $user_id );
			$form_field_data_array = user_registration_pro_profile_details_form_fields( $form_id );
			$user_data_to_show     = user_registration_pro_profile_details_form_field_datas( $form_id, $user_data, $form_field_data_array );
			$show_profile_picture  = get_option( 'user_registration_disable_profile_picture', true );
			include UR_ABSPATH . 'templates/pro/user-registration-pro-view-user.php';
		} else {
			echo apply_filters( 'ur_register_pre_view_profile_message', '<p class="alert" id="ur_register_pre_form_message">' . esc_html__( 'Sorry, you are not allowed to access this page. Please login to view this page.', 'user-registration' ) . '</p>' );
		}
	}
}

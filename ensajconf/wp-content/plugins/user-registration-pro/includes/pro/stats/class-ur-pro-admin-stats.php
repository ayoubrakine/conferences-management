<?php
/**
 * Class
 *
 * User_Registration_Pro_Admin_Stats
 *
 * @package User_Registration_Pro
 * @since  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'User_Registration_Pro_Admin_Stats' ) ) {

	/**
	 * User_Registration_Pro_Admin_Stats class.
	 */
	class User_Registration_Pro_Admin_Stats {
		/**
		 * Remote URl Constant.
		 */
		const REMOTE_URL = 'https://stats.wpeverest.com/wp-json/tgreporting/v1/process-premium/';

		/**
		 * Constructor of the class.
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'on_update_request' ), 4 );
			add_action( 'activated_plugin', array( $this, 'on_addon_activate' ), 20, 2 ); // Hook on plugin activation ( Our UR addons activation ).
			add_action( 'upgrader_process_complete', array( $this, 'on_addon_update' ), 20, 2 ); // Hook on plugin activation ( Our UR addons activation ).
		}

		/**
		 * Get product license key.
		 */
		public function get_base_product_license() {
			return get_option( 'user-registration_license_key' );
		}

		/**
		 * Get Pro addon file.
		 */
		public function get_base_product() {
			return 'user-registration-pro/user-registration.php';
		}

		/**
		 * Get all addons.
		 */
		public function get_addons() {
			$addon_json  = file_get_contents( UR()->plugin_path() . '/assets/extensions-json/sections/all_extensions.json' );
			$addon_lists = array();

			if ( ur_is_json( $addon_json ) ) {
				$addon_lists = json_decode( $addon_json, true );
			}
			return isset( $addon_lists['products'] ) ? $addon_lists['products'] : array();
		}

		/**
		 * Get all addons List.
		 *
		 * @param array $custom_list_of_addons addon list.
		 */
		public function get_addon_lists( $custom_list_of_addons = array() ) {

			if ( count( $custom_list_of_addons ) < 1 ) {

				$our_addons    = $this->get_addons();
				$product_lists = wp_list_pluck( $our_addons, 'slug' );

			} else {
				$product_lists = $custom_list_of_addons;
			}

			$active_plugins = get_option( 'active_plugins', array() );

			$addons_data = array(
				$this->get_base_product() => array(
					'product_name'    => __( 'User Registration Pro', 'user-registration' ),
					'product_version' => UR()->version,
					'product_meta'    => array( 'license_key' => $this->get_base_product_license() ),
					'product_type'    => 'plugin',
					'product_slug'    => $this->get_base_product(),
					'is_premium'      => 1
				)
			);

			foreach ( $active_plugins as $plugin ) {
				$plugin_array = explode( '/', $plugin );
				$plugin_item  = isset( $plugin_array[0] ) ? $plugin_array[0] : '';

				if ( in_array( $plugin_item, $product_lists, true ) ) {
					$addon_file      = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin;
					$addon_file_data = get_plugin_data( $addon_file );

					$addons_data[ $plugin ] = array(
						'product_name'    => isset( $addon_file_data['Name'] ) ? trim( $addon_file_data['Name'] ) : '',
						'product_version' => isset( $addon_file_data['Version'] ) ? trim( $addon_file_data['Version'] ) : '',
						'product_type'    => 'plugin',
						'product_slug'    => $plugin,
						'is_premium'      => 1
					);
				}
			}

			return $addons_data;
		}

		/**
		 * Send Request for old users before 3.2.0.
		 */
		public function on_update_request() {

			$license_key = $this->get_base_product_license();

			if($license_key==''){
				return;
			}
			$update_only_before_version = '3.2.0';
			$one_time_requested         = get_option( 'user_registration_pro_stats_one_time_requested', false );

			if ( $one_time_requested ) {
				return;
			}

			if ( ! defined( 'IFRAME_REQUEST' ) && version_compare( get_option( 'user_registration_version' ), $update_only_before_version, '<' ) ) {
				$this->call_api();
				update_option( 'user_registration_pro_stats_one_time_requested', true );
			}
		}

		/**
		 * Send Request on addon activated.
		 *
		 * @param string $plugin Plugin.
		 * @param mixed  $network_wide Network.
		 */
		public function on_addon_activate( $plugin, $network_wide ) {
			$plugin_array = explode( '/', $plugin );
			$plugin_item  = isset( $plugin_array[0] ) ? $plugin_array[0] : '';

			if ( '' === $plugin_item ) {
				return;
			}
			$our_addons  = $this->get_addons();
			$addon_lists = wp_list_pluck( $our_addons, 'slug' );

			if ( ! in_array( $plugin_item, $addon_lists, true ) ) {
				return;
			}
			$this->call_api();
		}

		/**
		 * Send Request on addon update.
		 *
		 * @param \WP_Upgrader $upgrader Upgrader.
		 * @param array        $hooks_extra Hook.
		 */
		public function on_addon_update( \WP_Upgrader $upgrader, $hooks_extra ) {
			$action = isset( $hooks_extra['action'] ) ? $hooks_extra['action'] : '';

			if ( 'update' !== $action ) {
				return;
			}
			$type = isset( $hooks_extra['type'] ) ? $hooks_extra['type'] : '';

			if ( 'plugin' !== $type ) {
				return;
			}

			$update_plugins = isset( $hooks_extra['plugins'] ) ? $hooks_extra['plugins'] : array();
			$update_plugin  = isset( $hooks_extra['plugin'] ) ? $hooks_extra['plugin'] : '';
			$bulk           = isset( $hooks_extra['bulk'] ) ? (bool) $hooks_extra['bulk'] : false;

			if ( '' !== $update_plugin ) {
				array_push( $update_plugins, $update_plugin );
			}

			$addons          = $this->get_addons();
			$addon_lists     = wp_list_pluck( $addons, 'slug' );
			$our_addon_files = array();

			foreach ( $update_plugins as $plugin ) {
				$plugin_array = explode( '/', $plugin );
				$plugin_item  = isset( $plugin_array[0] ) ? $plugin_array[0] : '';

				if ( in_array( $plugin_item, $addon_lists, true ) ) {
					$our_addon_files[] = $plugin_item;
				}
			}

			if ( 1 > count( $our_addon_files ) ) {
				return;
			}

			if ( $bulk ) {
				$this->call_api();
			} else {
				$this->call_api( $our_addon_files );
			}
		}

		/**
		 * Call API.
		 *
		 * @param array $custom_list_of_addons Custom addon List.
		 */
		public function call_api( $custom_list_of_addons = array() ) {
			$data                 = array();
			$data['product_data'] = $this->get_addon_lists( $custom_list_of_addons );
			$data['admin_email']  = get_bloginfo( 'admin_email' );
			$data['website_url']  = get_bloginfo( 'url' );
			$data['wp_version']   = get_bloginfo( 'version' );
			$data['base_product'] = $this->get_base_product();

			$this->send_request( self::REMOTE_URL, $data );
		}

		/**
		 * Send Request to API.
		 *
		 * @param string $url URL.
		 * @param array  $data Data.
		 */
		public function send_request( $url, $data ) {
			$headers = array(
				'user-agent' => 'UserRegistration/' . UR()->version . '; ' . get_bloginfo( 'url' ),
			);

			$response = wp_remote_post(
				$url,
				array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => $headers,
					'body'        => array( 'premium_data' => $data )
				)
			);
			return json_decode( wp_remote_retrieve_body( $response ), true );
		}
	}
}

new User_Registration_Pro_Admin_Stats();

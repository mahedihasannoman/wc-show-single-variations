<?php
/**
 * Plugin Name: WC Show Single Variations
 * Plugin URI:  https://www.braintum.com
 * Description: WC Show Single Variations is an addon plugin for Woocommerce. It allows you to shows variable products on store catalog as an individual product.
 * Version:     1.0.0
 * Author:      Md. Mahedi Hasan
 * Author URI:  https://www.braintum.com
 * Donate link: https://www.braintum.com
 * License:     GPLv2+
 * Text Domain: wc-show-single-variations
 * Domain Path: /i18n/languages/
 * Tested up to: 5.9
 * WC requires at least: 3.0.0
 * WC tested up to: 4.0.0
 */

/**
 * Copyright (c) 2019 Braintum (email : mahedi@braintum.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WC_Show_Single_Variations {
	/**
	 * @var string
	 */
	public static $version = "1.0.0";

	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 *
	 * @var $this
	 */
	protected static $instance = null;

	/**
	 * @return WC_Show_Single_Variations|null
	 * @since 1.0.0
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Cloning is forbidden.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'wc-show-single-variations' ), '1.0.0' );
	}

	/**
	 * Universalizing instances of this class is forbidden.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'wc-show-single-variations' ), '1.0.0' );
	}

	/**
	 * Determines if the wc active.
	 *
	 * @return bool
	 * @since 1.0.0
	 *
	 */
	public function is_wc_active() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		return is_plugin_active( 'woocommerce/woocommerce.php' ) == true;
	}

	/**
	 * @since 1.0.0
	 * WC_Show_Single_Variations constructor.
	 */
	public function __construct() {
		$this->define_constants();
		add_action( 'init', array( $this, 'localization_setup' ) );
		add_action( 'plugins_loaded', array( $this, 'includes' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		$this->init_hooks();
	}

	/**
	 * Define Constants.
	 */
	private function define_constants() {
		define( 'WCSV_PLUGIN_FILE', __FILE__ );
		define( 'WCSV_ABSPATH', dirname( WCSV_PLUGIN_FILE ) );
		define( 'WCSV_URL', plugins_url( '', WCSV_PLUGIN_FILE ) );
		define( 'WCSV_ASSETS_URL', WCSV_URL . '/assets' );
	}


	/**
	 * Load text domain
	 *
	 * @since 1.0.0
	 */
	public function localization_setup() {
		load_plugin_textdomain( 'wc-show-single-variations', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}


	/**
	 * include files.
	 * @since 1.0.0
	 */
	public function includes() {
		if ( is_admin() ) {
			//classes
			require_once( WCSV_ABSPATH . '/includes/admin/class-settings.php' );
			require_once( WCSV_ABSPATH . '/includes/admin/class-admin.php' );
		}
		require_once( WCSV_ABSPATH . '/includes/class-frontend.php' );


	}

	/**
	 * Displays any admin notices added
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function admin_notices() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}
		if ( ! $this->is_wc_active() ):
			$installed_plugins = get_plugins();
			$slug              = 'woocommerce';

			$activate_url = add_query_arg( array(
				'action'   => 'activate',
				'plugin'   => urlencode( "$slug/$slug.php" ),
				'_wpnonce' => urlencode( wp_create_nonce( "activate-plugin_$slug/$slug.php" ) ),
			), self_admin_url( 'plugins.php' ) );
			$message      = __( 'Ops! <strong>WC Show Single Variations</strong> not working because you need to activate the <strong> WooCommerce </strong> plugin first.', 'wc-show-single-variations' );
			$message      .= sprintf( '<a href="%s"> %s </a>', $activate_url, esc_html__( 'Activate WooCommerce', 'wc-show-single-variations' ) );

			echo '<div class="error"><p>' . $message . '</p></div>';
		endif;
	}


	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */

	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
	}

	public function admin_assets() {
		wp_enqueue_style( 'admin-style', plugins_url( 'assets/css/admin.css', __FILE__ ), null, time() );
		wp_enqueue_script( 'admin-script', plugins_url( 'assets/js/admin_scripts.js', __FILE__ ), array( 'jquery' ), time(), true );
	}


}

/**
 * @return WC_Show_Single_Variations
 * @since 1.0.0
 */
function wc_show_single_variations() {
	return WC_Show_Single_Variations::instance();
}

//fire plugin
wc_show_single_variations();
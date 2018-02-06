<?php

/*
Plugin Name: Kadence Importer
Description: Choose the demo and click import. Easy Kadence Themes demo site Imports. 
Version: 2.0.3
Author: Kadence Themes
Author URI: http://kadencethemes.com/
License: GPLv2 or later
Text Domain: kadence-importer
*/

// Block direct access to the main plugin file.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Main plugin class with initialization tasks.
 */
class Kadence_Importer {
	/**
	 * Constructor for this class.
	 */
	public function __construct() {
		/**
		 * Display admin error message if PHP version is older than 5.3.2.
		 * Otherwise execute the main plugin class.
		 */
		if ( version_compare( phpversion(), '5.3.2', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'old_php_admin_error_notice' ) );
		} else {
			// Set plugin constants.
			$this->set_plugin_constants();

			// Composer autoloader.
			require_once KADENCE_IMPORTER_PATH . 'vendor/autoload.php';
			require_once KADENCE_IMPORTER_PATH . 'kadencethemes/theme-setups.php';

			// Instantiate the main plugin class *Singleton*.
			$pt_one_click_demo_import = OCDI\OneClickDemoImport::get_instance();
		}
	}


	/**
	 * Display an admin error notice when PHP is older the version 5.3.2.
	 * Hook it to the 'admin_notices' action.
	 */
	public function old_php_admin_error_notice() {
		$message = __( 'The Kadence Importer plugin requires at least PHP 5.3.2 to run properly. Please contact your hosting company and ask them to update the PHP version of your site to at least PHP 5.3.2. We strongly encourge you to update to 7.0', 'kadence-importer' );

		printf( '<div class="notice notice-error"><p>%1$s</p></div>', wp_kses_post( $message ) );
	}


	/**
	 * Set plugin constants.
	 *
	 * Path/URL to root of this plugin, with trailing slash and plugin version.
	 */
	private function set_plugin_constants() {
		// Path/URL to root of this plugin, with trailing slash.
		if ( ! defined( 'KADENCE_IMPORTER_PATH' ) ) {
			define( 'KADENCE_IMPORTER_PATH', plugin_dir_path( __FILE__ ) );
		}
		if ( ! defined( 'KADENCE_IMPORTER_URL' ) ) {
			define( 'KADENCE_IMPORTER_URL', trailingslashit(plugin_dir_url( __FILE__ ) ));
		}
		if ( ! defined( 'PT_OCDI_PATH' ) ) {
			define( 'PT_OCDI_PATH', plugin_dir_path( __FILE__ ) );
		}
		if ( ! defined( 'PT_OCDI_URL' ) ) {
			define( 'PT_OCDI_URL', plugin_dir_url( __FILE__ ) );
		}
		// Action hook to set the plugin version constant.
		add_action( 'admin_init', array( $this, 'set_plugin_version_constant' ) );
	}


	/**
	 * Set plugin version constant -> PT_OCDI_VERSION.
	 */
	public function set_plugin_version_constant() {
		if ( ! defined( 'KADENCE_IMPORTER_VERSION' ) ) {
			$plugin_data = get_plugin_data( __FILE__ );
			define( 'KADENCE_IMPORTER_VERSION', $plugin_data['Version'] );
		}
		if ( ! defined( 'PT_OCDI_VERSION' ) ) {
			define( 'PT_OCDI_VERSION', '2.2.1');
		}
	}
}

// Instantiate the plugin class.
$kadence_importer = new Kadence_Importer();

require_once('wp-updates-plugin.php');

$kad_importer_updater = new PluginUpdateChecker_2_0 ('https://kernl.us/api/v1/updates/56f44151700cab945bcca01d/',__FILE__, 'kadence-importer', 1);
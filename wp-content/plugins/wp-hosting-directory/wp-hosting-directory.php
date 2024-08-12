<?php
/**
 * WordPress Hosting Directory
 *
 * @package           WP_Hosting_Directory
 * @author            Hosting Team
 * @copyright         2023 Hosting Team
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       WordPress Hosting Directory
 * Plugin URI:        https://make.wordpress.org/hosting/
 * Description:       A plugin to manage and display a directory of WordPress hosting providers.
 * Version:           1.0.1
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Hosting Team
 * Author URI:        https://make.wordpress.org/hosting/
 * Text Domain:       wp-hosting-directory
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WP_HOSTING_DIRECTORY_VERSION', '1.0.0' );
define( 'WP_HOSTING_DIRECTORY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_HOSTING_DIRECTORY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WP_HOSTING_DIRECTORY_PLUGIN_DIR . 'includes/class-wp-hosting-directory.php';

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function run_wp_hosting_directory() {
    if ( ! class_exists( 'WP_Hosting_Directory' ) ) {
        error_log( 'Class WP_Hosting_Directory does not exist' );
        return;
    }
    $plugin = new WP_Hosting_Directory();
    if ( ! is_object( $plugin ) ) {
        error_log( 'Failed to create WP_Hosting_Directory object' );
        return;
    }
    if ( method_exists( $plugin, 'run' ) ) {
        $plugin->run();
    } else {
        error_log( 'Method "run" does not exist in WP_Hosting_Directory class' );
    }
    add_action( 'plugins_loaded', 'run_wp_hosting_directory' );

}

add_action( 'plugins_loaded', 'run_wp_hosting_directory' );

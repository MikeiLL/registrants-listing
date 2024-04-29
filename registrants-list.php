<?php
/**
 * Plugin Name: mZ Mindbody Authentication
 *
 * Description: Child plugin for mZoo Mindbody Interface, which adds support of OAuth login via Mindbody.
 *
 * @package MzRegistrantsListing
 *
 * @wordpress-plugin
 * Version:         1.0.1
 * Stable tag:      1.0.1
 * Author:          mZoo.org
 * Author URI:      http://www.mZoo.org/
 * Plugin URI:      http://www.mzoo.org/
 * License:         GPL-2.0+
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:     mz-mbo-auth
 * Domain Path:     /languages
 */

namespace MZoo\MzRegistrantsListing;
use MZoo\MzRegistrantsListing as NS;

/*
 * Define Constants
 */

define( __NAMESPACE__ . '\NS', __NAMESPACE__ . '\\' );

define( NS . 'MZ', 'MZoo\MzMindbody' );

define( NS . 'PLUGIN_NAME', 'mz-mbo-auth' );

define( NS . 'PLUGIN_VERSION', '1.0.1' );

define( NS . 'PLUGIN_NAME_DIR', plugin_dir_path( __FILE__ ) );

define( NS . 'PLUGIN_NAME_URL', plugin_dir_url( __FILE__ ) );

define( NS . 'PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

define( NS . 'MINIMUM_PHP_VERSION', 7.1 );

define( NS . 'INIT_LEVEL', 20 );

/**
 * Check the minimum PHP version.
 */
if ( version_compare( PHP_VERSION, MINIMUM_PHP_VERSION, '<' ) ) {
    add_action( 'admin_notices', NS . 'minimum_php_version' );
    add_action( 'admin_init', __NAMESPACE__ . '\deactivate_plugins', INIT_LEVEL );
} else {


  function mindbody_oauth_uninstall(){
    // Clear Admin Options
    delete_option('mzmbo_oauth_options');
  }

    register_uninstall_hook( __FILE__, __NAMESPACE__ . '\mindbody_oauth_uninstall' );

    add_action( 'plugins_loaded', __NAMESPACE__ . '\mindbody_auth_has_mindbody_api', INIT_LEVEL );

}



/**
 * Deactivation and message when initialization fails.
 *
 * @param string $error        Error message to output.
 * @since 2.1.1
 * @return void.
 */
function activation_failed( $error ) {
    if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
        ?>
            <div class="notice notice-error is-dismissible"><p><strong>
                <?php echo esc_html( $error ); ?>
            </strong></p></div>
        <?php
    }
}

/**
 * Deactivate plugins.
 *
 * @since 2.1.1
 * @return void.
 */
function deactivate_plugins() {
    \deactivate_plugins( plugin_basename( __FILE__ ) );
    if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
        ?>
            <div class="notice notice-success is-dismissible"><p>
                <?php esc_html_e( 'Mindbody Authentication plugin has been deactivated.', 'mz-mbo-auth' ); ?>
            </p></div>
        <?php
    }
}

/**
 * Notice of php version error.
 *
 * @since 2.1.1
 * @return void.
 */
function minimum_php_version() {
    activation_failed( __( 'MZ MBO Access requires PHP version', 'mz-mbo-auth' ) . sprintf( ' %1.1f.', MINIMUM_PHP_VERSION ) );
}

/**
 * Insure that parent plugin, is active or deactivate plugin.
 */
function mindbody_auth_has_mindbody_api() {
    if ( ! class_exists( MZ . '\Core\MzMindbodyApi' ) ) {
        activation_failed( __( 'Mindbody Authentication requires MZ Mindbody Api.', 'mz-mbo-auth' ) );
        add_action( 'admin_init', __NAMESPACE__ . '\deactivate_plugins', INIT_LEVEL );
    } else {
    // Load the plugin.
    require_once 'session.php';
    require_once 'admin-settings.php';
    require_once 'mindbody.php';
    require_once 'request.php';
    require_once 'rest.php';
    require_once 'enqueue.php';
    }
}

function enqueue(){
    //wp_enqueue_style('string $handle', mixed $src, array $deps, mixed $ver, string $meida );
    wp_enqueue_style('mindbody_auth_style', plugin_dir_url( __FILE__ ) . '/style.css', [], NS . 'PLUGIN_VERSION', 'all' );
    //wp_enqueue_style('string $handle', mixed $src, array $deps, mixed $ver, bol $in_footer );
}
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\enqueue');

?>

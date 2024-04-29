<?php
/**
 * Plugin Name: mZ MBO Registrants List
 *
 * Description: Child plugin for mZoo Mindbody Interface to show minimized calendar with optional registrants by click..
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

define( NS . 'MINIMUM_PHP_VERSION', 8.1 );

define( NS . 'INIT_LEVEL', 20 );

/**
 * Check the minimum PHP version.
 */
if ( version_compare( PHP_VERSION, MINIMUM_PHP_VERSION, '<' ) ) {
    add_action( 'admin_notices', NS . 'minimum_php_version' );
    add_action( 'admin_init', __NAMESPACE__ . '\deactivate_plugins', INIT_LEVEL );
} else {
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
    activation_failed( __( 'Registrants List requires PHP version', 'mz-mbo-auth' ) . sprintf( ' %1.1f.', MINIMUM_PHP_VERSION ) );
}

/**
 * Insure that parent plugin, is active or deactivate plugin.
 */
function mindbody_auth_has_mindbody_api() {
    if ( ! class_exists( MZ . '\Core\MzMindbodyApi' ) ) {
        activation_failed( __( 'Registrants List requires MZ Mindbody Api.', 'mz-mbo-auth' ) );
        add_action( 'admin_init', __NAMESPACE__ . '\deactivate_plugins', INIT_LEVEL );
    } else {
    // Load the plugin.
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

add_shortcode('mz_registrants_list', function(){
    $schedule_object = new \MZoo\MzMindbody\Schedule\RetrieveSchedule(  );

    // Call the API and if fails, return error message.
    if ( false === $schedule_object->get_mbo_results() ) {
        echo '<div>' . __( 'Error returning schedule from MBO for display.', 'mz-mindbody-api' ) . '</div>';
    }

    $template_loader       = new \MZoo\MzMindbody\Core\TemplateLoader();
    $template_data['time_format'] = $schedule_object->time_format;
    $template_data['date_format'] = $schedule_object->date_format;
    $horizontal_schedule = $schedule_object->sortClassesByDateThenTime();
    $template_data = array(
        'time_format'          => $schedule_object->time_format,
        'date_format'          => $schedule_object->date_format,
        'locations_dictionary' => $schedule_object->locations_dictionary,
        'horizontal_schedule'  => $horizontal_schedule,
    );
    $template_loader->set_template_data( $template_data );
    ob_start();
    $template_loader->get_template_part( 'registrants_listing' );
    /* echo "<pre>";
    print_r($horizontal_schedule);
    echo "</pre>"; */
    ?>
    <div id="mz_registrants_list">
        <h1>Registrants List</h1>
        <p>Here is a list of registrants</p>
    </div>
    <?php
    return ob_get_clean();
});

?>

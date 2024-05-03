<?php
/**
 * Plugin Name: mZ MBO Registrants List
 *
 * Description: Child plugin for mZoo Mindbody Interface to show minimized calendar with optional registrants by click..
 *
 * @package MzRegistrantsListing
 *
 * @wordpress-plugin
 * Version:         1.0.0
 * Stable tag:      1.0.0
 * Author:          mZoo.org
 * Author URI:      http://www.mZoo.org/
 * Plugin URI:      http://www.mzoo.org/
 * License:         GPL-2.0+
 * License URI:     http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:     mz-mbo-registrants-list
 * Domain Path:     /languages
 */

namespace MZoo\MzRegistrantsListing;
use MZoo\MzRegistrantsListing as NS;

/*
 * Define Constants
 */

define( __NAMESPACE__ . '\NS', __NAMESPACE__ . '\\' );

define( NS . 'MZ', 'MZoo\MzMindbody' );

define( NS . 'PLUGIN_NAME', 'mz-mbo-registrants-list' );

define( NS . 'PLUGIN_VERSION', '1.0.0' );

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
    add_action( 'plugins_loaded', __NAMESPACE__ . '\mindbody_registrants_has_mindbody_api', INIT_LEVEL );
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
                <?php esc_html_e( 'Mindbody Authentication plugin has been deactivated.', 'mz-mbo-registrants-list' ); ?>
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
    activation_failed( __( 'Registrants List requires PHP version', 'mz-mbo-registrants-list' ) . sprintf( ' %1.1f.', MINIMUM_PHP_VERSION ) );
}

/**
 * Insure that parent plugin, is active or deactivate plugin.
 */
function mindbody_registrants_has_mindbody_api() {
    if ( ! class_exists( MZ . '\Core\MzMindbodyApi' ) ) {
        activation_failed( __( 'Registrants List requires MZ Mindbody Api.', 'mz-mbo-registrants-list' ) );
        add_action( 'admin_init', __NAMESPACE__ . '\deactivate_plugins', INIT_LEVEL );
    } else {
    // Load the plugin.
    require_once 'rest.php';
    require_once 'enqueue.php';
    }
}


add_shortcode('mz_registrants_list', function( $shortcode_atts ) {

    $atts = shortcode_atts(
        array(
            'type'      => 'week',
            'locations' => [1],
            'session_types'         => '',
            'duration'  => '+13 day',
        ),
        $shortcode_atts
    );
    // If set, turn Session/Class Types into an Array and call it session_types.
    if ( '' !== $atts['session_types'] ) {
        if ( ! is_array( $atts['session_types'] ) ) { // if not already an array.
            $atts['session_types'] = explode( ',', $atts['session_types'] );
        }
        // TODO: is this sometimes done reduntantly?
        foreach ( $atts['session_types'] as $key => $type ) :
            $atts['session_types'][ $key ] = trim( $type );
        endforeach;
    }

    $schedule_object = new \MZoo\MzMindbody\Schedule\RetrieveSchedule( $atts );

    // Call the API and if fails, return error message.
    if ( false === $schedule_object->get_mbo_results() ) {
        echo '<div>' . __( 'Error returning schedule from MBO for display.', 'mz-mindbody-api' ) . '</div>';
    }
    $horizontal_schedule = $schedule_object->sortClassesByDateThenTime();

    ob_start();
    ?>
    <div id="mz_registrants_listing">
        <div class="bw-widget__header"><h3 class="bw-header__title">Registrants List</h3></div>
    <?php foreach ( $horizontal_schedule as $day => $classes ) : ?>
        <details>
            <summary class="bw-widget__date">
                <?php echo gmdate( $schedule_object->date_format, strtotime( $day ) ); ?>
            </summary>

            <?php if ( ! empty( $classes ) ) : ?>
                <ul>
                <?php foreach ( $classes as $k => $class ) : ?>
                    <li>
                        <details class="show_registrants" data-classid=<?php echo $class->ID; ?>>
                            <summary class="bw-session__info">
                                <span class="bw-session__time">
                                    <?php echo gmdate( $schedule_object->time_format, strtotime( $class->start_datetime ) ); ?>
                                    <?php echo $class->class_name; ?>
                                </span>
                                <span class="bw-session__staff">
                                    <?php echo $class->staff_name;?>
                                </span>
                            </summary>
                        </details>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <?php esc_html_e( 'No classes to display.', 'mz-mindbody-api' ); ?>
            <?php endif; // if $classes or else block. ?>
        </details>

    <?php endforeach; ?>
</div>
    <?php
    return ob_get_clean();
});

?>

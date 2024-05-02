<?php
/**
 * Enqueue scripts and styles.
 *
 * This file contains request handling for MZ Mindbody
 *
 * @package MzRegistrantsListing
 */

namespace MZoo\MzRegistrantsListing;

use MZoo\MzMindbody as NS;


// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_scripts' );


/**
 * Enqueue scripts and styles.
 *
 * @since 1.0.0
 */
function enqueue_scripts() {
  wp_enqueue_style('mindbody_registrants_style', plugin_dir_url( __FILE__ ) . '/style.css', [], NS . 'PLUGIN_VERSION', 'all' );
  wp_register_script( 'mz_registrants_list', PLUGIN_NAME_URL . 'script.js', array('jquery'), PLUGIN_VERSION, true );
  wp_enqueue_script( 'mz_registrants_list' );

  $translated_strings = \MZoo\MzMindbody\MZMBO()->i18n->get();
  $protocol = isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://';

  $params = array(
    'ajaxurl'                => admin_url( 'admin-ajax.php', $protocol ),
    'display_schedule_nonce' => wp_create_nonce( 'mz_display_schedule' ),
    'siteID'               => \MZoo\MzMindbody\Core\MzMindbodyApi::$basic_options['mz_mindbody_siteID'],
    'nonce'                => wp_create_nonce( 'mz_mbo_api' ),
  );
  wp_localize_script( 'mz_registrants_list', 'mz_registrants_list', $params );
}

?>

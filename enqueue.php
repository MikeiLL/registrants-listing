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
add_filter('script_loader_tag', __NAMESPACE__ . '\add_type_attribute' , 10, 3);

// Load our script as a module, so we can use import.
function add_type_attribute($tag, $handle, $src) {
  // if not our script, do nothing and return original $tag
  if ( 'mz_registrants-list' !== $handle ) {
      return $tag;
  }
  // change the script tag by adding type="module" and return it.
  $tag = '<script type="module" id="' . $handle . '-js" src="' . esc_url( $src ) . '"></script>';
  return $tag;
}

/**
 * Enqueue scripts and styles.
 *
 * @since 1.0.0
 */
function enqueue_scripts() {

  wp_register_script( 'mz_registrants_list', PLUGIN_NAME_URL . 'script.js', array('jquery', 'mz_display_schedule_script'), PLUGIN_VERSION, true );
  wp_enqueue_script( 'mz_registrants_list' );

  $translated_strings = \MZoo\MzMindbody\MZMBO()->i18n->get();

  $params = array(
    'siteID'               => \MZoo\MzMindbody\Core\MzMindbodyApi::$basic_options['mz_mindbody_siteID'],
    'nonce'                => wp_create_nonce( 'mz_mbo_api' ),
  );
  wp_localize_script( 'mz_registrants_list', 'mz_registrants_list', $params );
}

?>

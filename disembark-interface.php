<?php

/**
 *
 * @link              https://austinginder.com
 * @since             1.0.0
 * @package           Disembark Interface
 *
 * @wordpress-plugin
 * Plugin Name:       Disembark Interface
 * Plugin URI:        https://disembark.host
 * Description:       Interface for Disembark backup service
 * Version:           1.0.0
 * Author:            Austin Ginder
 * Author URI:        https://austinginder.com
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       disembark-interface
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
new DisembarkInterface\Run();
new DisembarkInterface\Updater();
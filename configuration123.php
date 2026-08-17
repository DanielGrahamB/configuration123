<?php
/**
 * Plugin Name:       Configuration123
 * Plugin URI:        https://boazdanielgraham.ca/projects/configuration-123/
 * Description:       Centralises site identity, owner details, designer attribution, services, and social profiles for reusable WordPress builds.
 * Version:           1.2.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Boaz Daniel Graham
 * Author URI:        https://boazdanielgraham.ca/projects/configuration-123/
 * GitHub URI:        https://github.com/DanielGrahamB/configuration123
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       configuration123
 * Domain Path:       /languages
 * Update URI:        false
 *
 * @package Configuration123
 */

declare(strict_types=1);

namespace Configuration123;

defined( 'ABSPATH' ) || exit;

define( 'CONFIGURATION123_VERSION', '1.2.0' );
define( 'CONFIGURATION123_FILE', __FILE__ );
define( 'CONFIGURATION123_PATH', plugin_dir_path( __FILE__ ) );
define( 'CONFIGURATION123_URL', plugin_dir_url( __FILE__ ) );

require_once CONFIGURATION123_PATH . 'includes/class-defaults.php';
require_once CONFIGURATION123_PATH . 'includes/functions.php';
require_once CONFIGURATION123_PATH . 'includes/class-translations.php';
require_once CONFIGURATION123_PATH . 'includes/class-settings.php';
require_once CONFIGURATION123_PATH . 'includes/class-frontend.php';
require_once CONFIGURATION123_PATH . 'includes/class-plugin.php';

/**
 * Add defaults without replacing existing plugin data.
 */
function activate(): void {
	if ( false === get_option( Defaults::OPTION_NAME, false ) ) {
		add_option( Defaults::OPTION_NAME, Defaults::get() );
	}
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\activate' );

/**
 * Start the plugin after all active plugins are available.
 */
function run(): void {
	( new Plugin() )->register_hooks();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\run' );

/**
 * Retrieve a Configuration123 value in themes or companion plugins.
 *
 * @param string $field   Registered field key.
 * @param mixed  $default Fallback value.
 * @return mixed
 */
function get_value( string $field, mixed $default = '' ): mixed {
	$options = get_option( Defaults::OPTION_NAME, Defaults::get() );

	if ( ! is_array( $options ) || ! array_key_exists( $field, $options ) ) {
		return $default;
	}

	return apply_filters( 'configuration123_value', $options[ $field ], $field, $options );
}

/**
 * Determine whether a registered field is eligible for frontend output.
 *
 * @param string $field Registered field key.
 */
function is_public_field( string $field ): bool {
	if ( in_array( $field, array( 'site_name', 'site_tagline', 'site_description' ), true ) ) {
		return true;
	}

	$options = wp_parse_args( (array) get_option( Defaults::OPTION_NAME, array() ), Defaults::get() );

	return in_array( $field, (array) $options['public_owner_fields'], true )
		|| in_array( $field, (array) $options['public_designer_fields'], true );
}

/**
 * Retrieve a field only when it was selected for public display.
 *
 * @param string $field   Registered field key.
 * @param mixed  $default Fallback for private or missing fields.
 * @return mixed
 */
function get_public_value( string $field, mixed $default = '' ): mixed {
	return is_public_field( $field ) ? get_value( $field, $default ) : $default;
}

/**
 * Global convenience function for custom themes.
 *
 * @param string $field   Registered field key.
 * @param mixed  $default Fallback value.
 * @return mixed
 */
function template_value( string $field, mixed $default = '' ): mixed {
	return get_value( $field, $default );
}

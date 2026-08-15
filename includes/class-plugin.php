<?php
/**
 * Plugin coordinator.
 *
 * @package Configuration123
 */

declare(strict_types=1);

namespace Configuration123;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the independent admin and frontend services together.
 */
final class Plugin {

	/**
	 * Register all plugin hooks.
	 */
	public function register_hooks(): void {
		load_plugin_textdomain( 'configuration123', false, dirname( plugin_basename( CONFIGURATION123_FILE ) ) . '/languages' );

		( new Settings() )->register_hooks();
		( new Frontend() )->register_hooks();
	}
}

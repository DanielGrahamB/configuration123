<?php
/**
 * Remove plugin-owned data when Configuration123 is deleted.
 *
 * Native WordPress site name and tagline are intentionally preserved.
 *
 * @package Configuration123
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'configuration123_settings' );
delete_option( 'configuration123_machine_translations' );

<?php
/**
 * Global template helpers.
 *
 * @package Configuration123
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'configuration123_get' ) ) {
	/**
	 * Retrieve a Configuration123 field from a custom theme.
	 *
	 * @param string $field   Registered field key.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	function configuration123_get( string $field, mixed $default = '' ): mixed {
		return \Configuration123\get_value( $field, $default );
	}
}

if ( ! function_exists( 'configuration123_is_public' ) ) {
	/**
	 * Check whether a field was explicitly selected for public display.
	 *
	 * Site identity values are always public because WordPress already exposes
	 * them through the document title and Site Title block.
	 *
	 * @param string $field Registered field key.
	 */
	function configuration123_is_public( string $field ): bool {
		return \Configuration123\is_public_field( $field );
	}
}

if ( ! function_exists( 'configuration123_get_public' ) ) {
	/**
	 * Retrieve a value only when its owner selected it for public display.
	 *
	 * @param string $field   Registered field key.
	 * @param mixed  $default Fallback returned for private or missing fields.
	 * @return mixed
	 */
	function configuration123_get_public( string $field, mixed $default = '' ): mixed {
		return \Configuration123\get_public_value( $field, $default );
	}
}

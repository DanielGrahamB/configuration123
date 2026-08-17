<?php
/**
 * Bundled and optional machine-generated translations.
 *
 * @package Configuration123
 */

declare(strict_types=1);

namespace Configuration123;

use PO;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Adds cached Google Cloud translations without making requests on page loads.
 */
final class Translations {

	private const OPTION_NAME     = 'configuration123_machine_translations';
	private const GENERATE_ACTION = 'configuration123_generate_translation';
	private const DELETE_ACTION   = 'configuration123_delete_translation';
	private const EDITOR_HANDLE   = 'configuration123-display-editor-script';

	/**
	 * Register translation filters and protected administrator actions.
	 */
	public function register_hooks(): void {
		add_filter( 'gettext_configuration123', array( $this, 'translate' ), 10, 3 );
		add_filter( 'gettext_with_context_configuration123', array( $this, 'translate_with_context' ), 10, 4 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'add_editor_translations' ), 20 );
		add_action( 'admin_post_' . self::GENERATE_ACTION, array( $this, 'generate_from_google' ) );
		add_action( 'admin_post_' . self::DELETE_ACTION, array( $this, 'delete_generated_pack' ) );
	}

	/**
	 * Supply a cached machine translation when no bundled catalog translated it.
	 *
	 * @param string $translation Current WordPress translation.
	 * @param string $text        Original English text.
	 * @param string $domain      Text domain.
	 */
	public function translate( string $translation, string $text, string $domain ): string {
		if ( 'configuration123' !== $domain || $translation !== $text ) {
			return $translation;
		}

		$messages = self::messages_for_locale( determine_locale() );

		return isset( $messages[ $text ] ) ? (string) $messages[ $text ] : $translation;
	}

	/**
	 * Supply cached translations to gettext calls that include context.
	 *
	 * @param string $translation Current WordPress translation.
	 * @param string $text        Original English text.
	 * @param string $context     Translation context.
	 * @param string $domain      Text domain.
	 */
	public function translate_with_context( string $translation, string $text, string $context, string $domain ): string {
		unset( $context );

		return $this->translate( $translation, $text, $domain );
	}

	/**
	 * Make the same cached messages available to the JavaScript block editor.
	 */
	public function add_editor_translations(): void {
		$messages = self::messages_for_locale( determine_locale() );
		if ( empty( $messages ) || ! wp_script_is( self::EDITOR_HANDLE, 'registered' ) ) {
			return;
		}

		$locale_data = array(
			'' => array(
				'domain'       => 'configuration123',
				'lang'         => determine_locale(),
				'plural-forms' => 'nplurals=2; plural=(n != 1);',
			),
		);
		foreach ( $messages as $source => $translation ) {
			$locale_data[ $source ] = array( $translation );
		}

		wp_add_inline_script(
			self::EDITOR_HANDLE,
			'wp.i18n.setLocaleData(' . wp_json_encode( $locale_data, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ) . ', "configuration123");',
			'before'
		);
	}

	/**
	 * Generate and cache a complete pack for the requested WordPress locale.
	 */
	public function generate_from_google(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to generate translations.', 'configuration123' ) );
		}

		check_admin_referer( self::GENERATE_ACTION );
		$locale = self::sanitize_locale( wp_unslash( $_POST['locale'] ?? '' ) );
		$key    = self::google_api_key();

		if ( '' === $locale || '' === $key ) {
			self::set_notice( 'error', __( 'A valid locale and Google Cloud Translation API key are required.', 'configuration123' ) );
			self::redirect_to_settings();
		}

		$result = $this->request_google_translations( $locale, $key );
		if ( is_wp_error( $result ) ) {
			self::set_notice( 'error', $result->get_error_message() );
			self::redirect_to_settings();
		}

		$packs            = self::all_packs();
		$packs[ $locale ] = array(
			'generated_at' => time(),
			'language'     => self::google_language_code( $locale ),
			'source_hash'  => hash_file( 'sha256', CONFIGURATION123_PATH . 'languages/configuration123.pot' ) ?: '',
			'messages'     => $result,
		);
		update_option( self::OPTION_NAME, $packs, false );

		self::set_notice( 'success', __( 'The automatic language pack was generated and cached successfully.', 'configuration123' ) );
		self::redirect_to_settings();
	}

	/**
	 * Remove one generated pack without affecting bundled translations.
	 */
	public function delete_generated_pack(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to remove translations.', 'configuration123' ) );
		}

		check_admin_referer( self::DELETE_ACTION );
		$locale = self::sanitize_locale( wp_unslash( $_POST['locale'] ?? '' ) );
		$packs  = self::all_packs();
		unset( $packs[ $locale ] );
		update_option( self::OPTION_NAME, $packs, false );

		self::set_notice( 'success', __( 'The generated language pack was removed.', 'configuration123' ) );
		self::redirect_to_settings();
	}

	/**
	 * Current interface locale used by WordPress for this administrator.
	 */
	public static function current_locale(): string {
		return self::sanitize_locale( determine_locale() );
	}

	/**
	 * Whether a human-maintained catalog ships with this plugin.
	 */
	public static function has_bundled_pack( string $locale ): bool {
		return is_readable( CONFIGURATION123_PATH . 'languages/configuration123-' . self::sanitize_locale( $locale ) . '.mo' );
	}

	/**
	 * Whether a cached Google-generated pack exists for a locale.
	 */
	public static function generated_pack( string $locale ): array {
		$packs = self::all_packs();

		return isset( $packs[ $locale ] ) && is_array( $packs[ $locale ] ) ? $packs[ $locale ] : array();
	}

	/**
	 * Whether Google Basic API credentials have been supplied by the server.
	 */
	public static function has_google_api_key(): bool {
		return '' !== self::google_api_key();
	}

	/**
	 * Admin-post action used by the settings screen.
	 */
	public static function generate_action(): string {
		return self::GENERATE_ACTION;
	}

	/**
	 * Admin-post action used to remove a generated pack.
	 */
	public static function delete_action(): string {
		return self::DELETE_ACTION;
	}

	/**
	 * Retrieve and clear the current administrator's one-time notice.
	 */
	public static function consume_notice(): array {
		$key    = 'configuration123_translation_notice_' . get_current_user_id();
		$notice = get_transient( $key );
		delete_transient( $key );

		return is_array( $notice ) ? $notice : array();
	}

	/**
	 * Send the POT source messages to Google Cloud Translation Basic in batches.
	 *
	 * @return array<string, string>|WP_Error
	 */
	private function request_google_translations( string $locale, string $key ): array|WP_Error {
		$sources = $this->source_messages();
		if ( is_wp_error( $sources ) ) {
			return $sources;
		}

		$translated = array();
		$endpoint   = 'https://translation.googleapis.com/language/translate/v2';

		foreach ( array_chunk( $sources, 100 ) as $batch ) {
			$response = wp_remote_post(
				$endpoint,
				array(
					'timeout' => 30,
					'headers' => array(
						'Content-Type'   => 'application/json; charset=utf-8',
						'x-goog-api-key' => $key,
					),
					'body'    => wp_json_encode(
						array(
							'q'      => array_values( $batch ),
							'source' => 'en',
							'target' => self::google_language_code( $locale ),
							'format' => 'text',
						)
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return new WP_Error( 'configuration123_google_request', __( 'Google Cloud Translation could not be reached. Please try again.', 'configuration123' ) );
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( 200 !== wp_remote_retrieve_response_code( $response ) || ! is_array( $body ) ) {
				return new WP_Error( 'configuration123_google_response', __( 'Google Cloud Translation rejected the request. Check the API key, billing, restrictions, and target language.', 'configuration123' ) );
			}

			$items = $body['data']['translations'] ?? array();
			if ( ! is_array( $items ) || count( $items ) !== count( $batch ) ) {
				return new WP_Error( 'configuration123_google_incomplete', __( 'Google Cloud Translation returned an incomplete language pack.', 'configuration123' ) );
			}

			foreach ( array_values( $batch ) as $index => $source ) {
				$value = html_entity_decode( (string) ( $items[ $index ]['translatedText'] ?? '' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				if ( '' !== trim( $value ) ) {
					$translated[ $source ] = sanitize_text_field( $value );
				}
			}
		}

		return $translated;
	}

	/**
	 * Read unique source messages from the versioned WordPress POT catalog.
	 *
	 * @return array<int, string>|WP_Error
	 */
	private function source_messages(): array|WP_Error {
		$pot_file = CONFIGURATION123_PATH . 'languages/configuration123.pot';
		if ( ! is_readable( $pot_file ) ) {
			return new WP_Error( 'configuration123_missing_pot', __( 'The Configuration123 source language catalog is missing.', 'configuration123' ) );
		}

		require_once ABSPATH . WPINC . '/pomo/po.php';
		$catalog = new PO();
		if ( ! $catalog->import_from_file( $pot_file ) ) {
			return new WP_Error( 'configuration123_invalid_pot', __( 'The Configuration123 source language catalog could not be read.', 'configuration123' ) );
		}

		$protected = array(
			'Configuration123',
			'https://boazdanielgraham.ca/',
			'Boaz Daniel Graham',
			'WhatsApp',
			'LinkedIn',
			'Facebook',
			'Instagram',
			'GitHub',
			'X',
		);
		$sources   = array();

		foreach ( $catalog->entries as $entry ) {
			$source = trim( (string) $entry->singular );
			if ( '' !== $source && ! in_array( $source, $protected, true ) ) {
				$sources[ $source ] = $source;
			}
		}

		return array_values( $sources );
	}

	/**
	 * Retrieve machine messages for a locale.
	 *
	 * @return array<string, string>
	 */
	private static function messages_for_locale( string $locale ): array {
		$pack = self::generated_pack( self::sanitize_locale( $locale ) );

		return isset( $pack['messages'] ) && is_array( $pack['messages'] ) ? $pack['messages'] : array();
	}

	/**
	 * All generated locale packs.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function all_packs(): array {
		$packs = get_option( self::OPTION_NAME, array() );

		return is_array( $packs ) ? $packs : array();
	}

	/**
	 * Read the API key from wp-config.php or a hosting integration filter.
	 */
	private static function google_api_key(): string {
		$key = defined( 'CONFIGURATION123_GOOGLE_TRANSLATE_API_KEY' ) ? (string) CONFIGURATION123_GOOGLE_TRANSLATE_API_KEY : '';

		return trim( (string) apply_filters( 'configuration123_google_translate_api_key', $key ) );
	}

	/**
	 * Convert a WordPress locale into a Google Basic target language code.
	 */
	private static function google_language_code( string $locale ): string {
		$locale  = self::sanitize_locale( $locale );
		$special = array(
			'zh_CN' => 'zh-CN',
			'zh_HK' => 'zh-TW',
			'zh_TW' => 'zh-TW',
		);
		$code    = $special[ $locale ] ?? strtolower( explode( '_', $locale )[0] );

		return (string) apply_filters( 'configuration123_google_language_code', $code, $locale );
	}

	/**
	 * Keep locale values limited to WordPress-compatible characters.
	 */
	private static function sanitize_locale( mixed $locale ): string {
		return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $locale ) ?? '';
	}

	/**
	 * Store a short, private notice for the settings screen.
	 */
	private static function set_notice( string $type, string $message ): void {
		set_transient(
			'configuration123_translation_notice_' . get_current_user_id(),
			array(
				'type'    => 'success' === $type ? 'success' : 'error',
				'message' => $message,
			),
			60
		);
	}

	/**
	 * Return to the protected Configuration123 settings page.
	 */
	private static function redirect_to_settings(): never {
		wp_safe_redirect( admin_url( 'admin.php?page=configuration123' ) );
		exit;
	}
}

<?php
/**
 * Public display helpers and structured identity data.
 *
 * @package Configuration123
 */

declare(strict_types=1);

namespace Configuration123;

defined( 'ABSPATH' ) || exit;

/**
 * Registers optional, theme-independent ways to display public values.
 */
final class Frontend {

	/**
	 * Register public hooks.
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_head', array( $this, 'render_schema' ), 30 );
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ) );
	}

	/**
	 * Register plugin-owned Gutenberg blocks for theme-independent placement.
	 */
	public function register_blocks(): void {
		$block_path = CONFIGURATION123_PATH . 'blocks/display';

		if ( is_readable( $block_path . '/block.json' ) ) {
			register_block_type( $block_path );
		}
	}

	/**
	 * Add a dedicated Configuration123 block-inserter category.
	 *
	 * @param array<int, array<string, mixed>> $categories Existing block categories.
	 * @return array<int, array<string, mixed>>
	 */
	public function register_block_category( array $categories ): array {
		foreach ( $categories as $category ) {
			if ( 'configuration123' === ( $category['slug'] ?? '' ) ) {
				return $categories;
			}
		}

		array_unshift(
			$categories,
			array(
				'slug'  => 'configuration123',
				'title' => __( 'Configuration123', 'configuration123' ),
				'icon'  => 'admin-settings',
			)
		);

		return $categories;
	}

	/**
	 * Register the small public shortcode API.
	 */
	public function register_shortcodes(): void {
		add_shortcode( 'configuration123', array( $this, 'field_shortcode' ) );
		add_shortcode( 'configuration123_profile', array( $this, 'profile_shortcode' ) );
		add_shortcode( 'configuration123_services', array( $this, 'services_shortcode' ) );
		add_shortcode( 'configuration123_socials', array( $this, 'socials_shortcode' ) );
		add_shortcode( 'configuration123_location', array( $this, 'location_shortcode' ) );
		add_shortcode( 'configuration123_contact', array( $this, 'contact_shortcode' ) );
		add_shortcode( 'configuration123_owner_card', array( $this, 'owner_card_shortcode' ) );
		add_shortcode( 'configuration123_attribution', array( $this, 'attribution_shortcode' ) );
		add_shortcode( 'configuration123_copyright', array( $this, 'copyright_shortcode' ) );
	}

	/**
	 * Load minimal presentation styles without imposing typography or colours.
	 */
	public function enqueue_styles(): void {
		$asset = CONFIGURATION123_PATH . 'public/assets/public.css';
		wp_enqueue_style(
			'configuration123-public',
			CONFIGURATION123_URL . 'public/assets/public.css',
			array(),
			is_readable( $asset ) ? (string) filemtime( $asset ) : CONFIGURATION123_VERSION
		);
	}

	/**
	 * Render one allowed public field.
	 *
	 * @param array<string, mixed>|string $attributes Shortcode attributes.
	 * @return string
	 */
	public function field_shortcode( array|string $attributes = array() ): string {
		$attributes = shortcode_atts(
			array(
				'field' => 'site_name',
			),
			is_array( $attributes ) ? $attributes : array(),
			'configuration123'
		);

		$field = sanitize_key( (string) $attributes['field'] );
		if ( ! array_key_exists( $field, Defaults::shortcode_fields() ) || ! is_public_field( $field ) ) {
			return '';
		}

		$value = get_value( $field );
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return esc_html( (string) $value );
	}

	/**
	 * Render a selected client or designer definition list.
	 *
	 * @param array<string, mixed>|string $attributes Shortcode attributes.
	 * @return string
	 */
	public function profile_shortcode( array|string $attributes = array() ): string {
		$attributes = shortcode_atts(
			array(
				'type'        => 'owner',
				'show_labels' => 'true',
			),
			is_array( $attributes ) ? $attributes : array(),
			'configuration123_profile'
		);

		$type        = 'designer' === sanitize_key( (string) $attributes['type'] ) ? 'designer' : 'owner';
		$show_labels = filter_var( $attributes['show_labels'], FILTER_VALIDATE_BOOLEAN );
		$fields      = 'designer' === $type ? Defaults::designer_public_fields() : Defaults::owner_public_fields();
		$rows        = array();

		foreach ( $fields as $field => $label ) {
			if ( ! is_public_field( $field ) || str_ends_with( $field, '_url' ) || 'designer_services' === $field ) {
				continue;
			}

			$value = (string) get_value( $field );
			if ( '' === $value ) {
				continue;
			}

			$rows[] = '<div class="configuration123-profile__row">' . ( $show_labels ? '<dt>' . esc_html( $label ) . '</dt>' : '' ) . '<dd>' . esc_html( $value ) . '</dd></div>';
		}

		if ( empty( $rows ) ) {
			return '';
		}

		return '<dl class="configuration123-profile configuration123-profile--' . esc_attr( $type ) . '">' . implode( '', $rows ) . '</dl>';
	}

	/**
	 * Render public designer services as a semantic list.
	 *
	 * @return string
	 */
	public function services_shortcode(): string {
		if ( ! is_public_field( 'designer_services' ) ) {
			return '';
		}

		$services = preg_split( '/\r\n|\r|\n/', (string) get_value( 'designer_services' ) );
		$services = array_values( array_filter( array_map( 'trim', is_array( $services ) ? $services : array() ) ) );

		if ( empty( $services ) ) {
			return '';
		}

		$items = array_map(
			static fn( string $service ): string => '<li>' . esc_html( $service ) . '</li>',
			$services
		);

		return '<ul class="configuration123-services">' . implode( '', $items ) . '</ul>';
	}

	/**
	 * Render available, public social profile links.
	 *
	 * @return string
	 */
	public function socials_shortcode(): string {
		$socials = array(
			'linkedin_url'  => __( 'LinkedIn', 'configuration123' ),
			'facebook_url'  => __( 'Facebook', 'configuration123' ),
			'instagram_url' => __( 'Instagram', 'configuration123' ),
			'github_url'    => __( 'GitHub', 'configuration123' ),
			'x_url'         => __( 'X', 'configuration123' ),
		);
		$links = array();

		foreach ( $socials as $field => $label ) {
			$url = (string) get_value( $field );
			if ( ! is_public_field( $field ) || '' === $url ) {
				continue;
			}

			$links[] = '<li><a href="' . esc_url( $url ) . '" rel="me noopener noreferrer" target="_blank">' . esc_html( $label ) . '</a></li>';
		}

		return empty( $links ) ? '' : '<ul class="configuration123-socials">' . implode( '', $links ) . '</ul>';
	}

	/**
	 * Render selected address parts as one compact location line.
	 */
	public function location_shortcode(): string {
		$parts = array();
		foreach ( array( 'street_address', 'city', 'region', 'postal_code', 'country' ) as $field ) {
			$value = trim( (string) get_public_value( $field ) );
			if ( '' !== $value ) {
				$parts[] = $value;
			}
		}

		return empty( $parts ) ? '' : '<span class="configuration123-location">' . esc_html( implode( ' · ', array_unique( $parts ) ) ) . '</span>';
	}

	/**
	 * Render selected public contact methods with actionable links.
	 *
	 * @param array<string, mixed>|string $attributes Shortcode attributes.
	 */
	public function contact_shortcode( array|string $attributes = array() ): string {
		$attributes = shortcode_atts(
			array( 'compact' => 'false' ),
			is_array( $attributes ) ? $attributes : array(),
			'configuration123_contact'
		);
		$compact = filter_var( $attributes['compact'], FILTER_VALIDATE_BOOLEAN );
		$items   = array();
		$email   = sanitize_email( (string) get_public_value( 'owner_email' ) );
		$phone   = trim( (string) get_public_value( 'owner_phone' ) );
		$whats   = trim( (string) get_public_value( 'owner_whatsapp' ) );
		$hours   = trim( (string) get_public_value( 'business_hours' ) );

		if ( '' !== $phone ) {
			$digits  = preg_replace( '/[^0-9+]/', '', $phone ) ?? '';
			$items[] = '<li><a href="tel:' . esc_attr( $digits ) . '"><span>' . esc_html__( 'Phone', 'configuration123' ) . '</span>' . esc_html( $phone ) . '</a></li>';
		}
		if ( '' !== $whats ) {
			$digits  = preg_replace( '/[^0-9]/', '', $whats ) ?? '';
			$items[] = '<li><a href="https://wa.me/' . esc_attr( $digits ) . '" target="_blank" rel="noopener noreferrer"><span>' . esc_html__( 'WhatsApp', 'configuration123' ) . '</span>' . esc_html( $whats ) . '</a></li>';
		}
		if ( '' !== $email ) {
			$items[] = '<li><a href="mailto:' . esc_attr( $email ) . '"><span>' . esc_html__( 'Email', 'configuration123' ) . '</span>' . esc_html( $email ) . '</a></li>';
		}
		if ( '' !== $hours ) {
			$items[] = '<li><span class="configuration123-contact__text"><span>' . esc_html__( 'Hours', 'configuration123' ) . '</span>' . esc_html( $hours ) . '</span></li>';
		}

		if ( empty( $items ) ) {
			return '';
		}

		$class = $compact ? ' configuration123-contact--compact' : '';
		return '<ul class="configuration123-contact' . esc_attr( $class ) . '">' . implode( '', $items ) . '</ul>';
	}

	/**
	 * Render a compact business identity, owner, location, and contact card.
	 *
	 * @param array<string, mixed>|string $attributes Shortcode attributes.
	 */
	public function owner_card_shortcode( array|string $attributes = array() ): string {
		$attributes = shortcode_atts(
			array( 'compact' => 'false' ),
			is_array( $attributes ) ? $attributes : array(),
			'configuration123_owner_card'
		);
		$compact      = filter_var( $attributes['compact'], FILTER_VALIDATE_BOOLEAN );
		$organization = trim( (string) get_public_value( 'organization_name' ) );
		$owner        = trim( (string) get_public_value( 'owner_name' ) );
		$role         = trim( (string) get_public_value( 'owner_role' ) );
		$location     = $this->location_shortcode();
		$contact      = $this->contact_shortcode( array( 'compact' => $compact ? 'true' : 'false' ) );

		if ( '' === $organization && '' === $owner && '' === $location && '' === $contact ) {
			return '';
		}

		$class  = $compact ? ' configuration123-owner-card--compact' : '';
		$output = '<address class="configuration123-owner-card' . esc_attr( $class ) . '">';
		if ( '' !== $organization ) {
			$output .= '<strong class="configuration123-owner-card__organization">' . esc_html( $organization ) . '</strong>';
		}
		if ( '' !== $owner ) {
			$output .= '<span class="configuration123-owner-card__owner">' . esc_html__( 'Contact:', 'configuration123' ) . ' ' . esc_html( $owner );
			if ( '' !== $role ) {
				$output .= ' · ' . esc_html( $role );
			}
			$output .= '</span>';
		}
		if ( '' !== $location ) {
			$output .= '<span class="configuration123-owner-card__location">' . $location . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated and escaped by location_shortcode().
		}

		return $output . $contact . '</address>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated and escaped by contact_shortcode().
	}

	/**
	 * Render selected designer details as discreet site attribution.
	 */
	public function attribution_shortcode(): string {
		$name = trim( (string) get_public_value( 'designer_name' ) );
		$role = trim( (string) get_public_value( 'designer_role' ) );
		$url  = esc_url( (string) get_public_value( 'designer_website' ) );

		if ( '' === $name && '' === $role ) {
			return '';
		}

		$identity = '' !== $name ? esc_html( $name ) : esc_html__( 'Site designer', 'configuration123' );
		if ( '' !== $url ) {
			$identity = '<a href="' . $url . '" target="_blank" rel="author noopener noreferrer">' . $identity . '</a>';
		}

		$output = '<span class="configuration123-attribution"><span>' . esc_html__( 'Designed by', 'configuration123' ) . ' ' . $identity . '</span>';
		if ( '' !== $role ) {
			$output .= '<span class="configuration123-attribution__role">' . esc_html( $role ) . '</span>';
		}

		return $output . '</span>';
	}

	/**
	 * Render a current-year copyright line using the selected public identity.
	 */
	public function copyright_shortcode(): string {
		$name = trim( (string) get_public_value( 'organization_name' ) );
		if ( '' === $name ) {
			$name = (string) get_value( 'site_name', get_bloginfo( 'name' ) );
		}

		return '<span class="configuration123-copyright">© ' . esc_html( wp_date( 'Y' ) . ' ' . $name ) . '. ' . esc_html__( 'All rights reserved.', 'configuration123' ) . '</span>';
	}

	/**
	 * Output one compact Person or Organization JSON-LD record.
	 */
	public function render_schema(): void {
		$options = $this->options();
		if ( empty( $options['enable_schema'] ) ) {
			return;
		}

		$is_person = 'person' === $options['site_type'];
		$name      = $is_person ? (string) $options['owner_name'] : (string) $options['organization_name'];
		if ( '' === $name ) {
			$name = (string) $options['site_name'];
		}

		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => $is_person ? 'Person' : 'Organization',
			'name'        => $name,
			'url'         => home_url( '/' ),
			'description' => (string) ( $options['site_description'] ?: $options['site_tagline'] ),
		);

		if ( is_public_field( 'owner_email' ) && '' !== $options['owner_email'] ) {
			$schema['email'] = (string) $options['owner_email'];
		}
		if ( is_public_field( 'owner_phone' ) && '' !== $options['owner_phone'] ) {
			$schema['telephone'] = (string) $options['owner_phone'];
		}

		$address_map = array(
			'street_address' => 'streetAddress',
			'city'           => 'addressLocality',
			'region'         => 'addressRegion',
			'postal_code'    => 'postalCode',
			'country'        => 'addressCountry',
		);
		$address = array( '@type' => 'PostalAddress' );
		foreach ( $address_map as $field => $schema_key ) {
			if ( is_public_field( $field ) && '' !== $options[ $field ] ) {
				$address[ $schema_key ] = (string) $options[ $field ];
			}
		}
		if ( count( $address ) > 1 ) {
			$schema['address'] = $address;
		}

		$same_as = array();
		foreach ( array( 'linkedin_url', 'facebook_url', 'instagram_url', 'github_url', 'x_url' ) as $field ) {
			if ( is_public_field( $field ) && '' !== $options[ $field ] ) {
				$same_as[] = (string) $options[ $field ];
			}
		}
		if ( ! empty( $same_as ) ) {
			$schema['sameAs'] = $same_as;
		}

		printf(
			'<script type="application/ld+json" id="configuration123-schema">%s</script>' . "\n",
			wp_json_encode( array_filter( $schema ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/**
	 * Return options merged with the current defaults.
	 *
	 * @return array<string, mixed>
	 */
	private function options(): array {
		$options = get_option( Defaults::OPTION_NAME, array() );

		return wp_parse_args( is_array( $options ) ? $options : array(), Defaults::get() );
	}
}

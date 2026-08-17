<?php
/**
 * Plugin defaults and field definitions.
 *
 * @package Configuration123
 */

declare(strict_types=1);

namespace Configuration123;

defined( 'ABSPATH' ) || exit;

/**
 * Defines the stable data shape shared by admin and frontend code.
 */
final class Defaults {

	public const OPTION_NAME = 'configuration123_settings';

	/**
	 * Get default values for a newly activated site.
	 *
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		return array(
			'site_name'            => (string) get_option( 'blogname', '' ),
			'site_tagline'         => (string) get_option( 'blogdescription', '' ),
			'organization_name'    => (string) get_option( 'blogname', '' ),
			'site_type'            => 'organization',
			'site_description'     => '',
			'owner_name'           => '',
			'owner_role'           => '',
			'owner_email'          => '',
			'owner_phone'          => '',
			'owner_whatsapp'       => '',
			'street_address'       => '',
			'city'                 => '',
			'region'               => '',
			'postal_code'          => '',
			'country'              => 'Cameroon',
			'business_hours'       => '',
			'designer_name'        => 'Boaz Daniel Graham',
			'designer_practice'    => 'Boaz Daniel Graham',
			'designer_role'        => 'Independent security researcher & web systems builder',
			'designer_email'       => '',
			'designer_phone'       => '',
			'designer_website'     => 'https://boazdanielgraham.ca/',
			'designer_location'    => 'Montréal · Cameroon · Remote',
			'designer_services'    => implode(
				"\n",
				array(
					'Security auditing and incident response',
					'Custom WordPress themes and blocks',
					'React and Next.js applications',
					'PHP and Laravel systems',
					'Web hosting and infrastructure review',
					'AI-assisted business solutions',
				)
			),
			'linkedin_url'         => '',
			'facebook_url'         => '',
			'instagram_url'        => '',
			'github_url'           => '',
			'x_url'                => '',
			'public_owner_fields'  => array( 'organization_name', 'owner_phone', 'owner_whatsapp', 'city', 'region', 'country' ),
			'public_designer_fields' => array( 'designer_name', 'designer_role', 'designer_website', 'designer_services' ),
			'enable_schema'        => true,
		);
	}

	/**
	 * Owner fields allowed for public output.
	 *
	 * @return array<string, string>
	 */
	public static function owner_public_fields(): array {
		return array(
			'organization_name' => __( 'Business or organisation name', 'configuration123' ),
			'owner_name'        => __( 'Owner name', 'configuration123' ),
			'owner_role'        => __( 'Owner role', 'configuration123' ),
			'owner_email'       => __( 'Owner email', 'configuration123' ),
			'owner_phone'       => __( 'Owner phone', 'configuration123' ),
			'owner_whatsapp'    => __( 'WhatsApp', 'configuration123' ),
			'street_address'    => __( 'Street address', 'configuration123' ),
			'city'              => __( 'City', 'configuration123' ),
			'region'            => __( 'Region', 'configuration123' ),
			'postal_code'       => __( 'Postal code', 'configuration123' ),
			'country'           => __( 'Country', 'configuration123' ),
			'business_hours'    => __( 'Business hours', 'configuration123' ),
		);
	}

	/**
	 * Designer fields allowed for public output.
	 *
	 * @return array<string, string>
	 */
	public static function designer_public_fields(): array {
		return array(
			'designer_name'     => __( 'Designer name', 'configuration123' ),
			'designer_practice' => __( 'Practice name', 'configuration123' ),
			'designer_role'     => __( 'Professional description', 'configuration123' ),
			'designer_email'    => __( 'Designer email', 'configuration123' ),
			'designer_phone'    => __( 'Designer phone', 'configuration123' ),
			'designer_website'  => __( 'Designer website', 'configuration123' ),
			'designer_location' => __( 'Designer location', 'configuration123' ),
			'designer_services' => __( 'Designer services', 'configuration123' ),
			'linkedin_url'      => __( 'LinkedIn', 'configuration123' ),
			'facebook_url'      => __( 'Facebook', 'configuration123' ),
			'instagram_url'     => __( 'Instagram', 'configuration123' ),
			'github_url'        => __( 'GitHub', 'configuration123' ),
			'x_url'             => __( 'X / Twitter', 'configuration123' ),
		);
	}

	/**
	 * All scalar fields available through the field shortcode.
	 *
	 * @return array<string, string>
	 */
	public static function shortcode_fields(): array {
		return array_merge(
			array(
				'site_name'        => __( 'Site name', 'configuration123' ),
				'site_tagline'     => __( 'Site tagline', 'configuration123' ),
				'site_description' => __( 'Site description', 'configuration123' ),
			),
			self::owner_public_fields(),
			self::designer_public_fields()
		);
	}
}

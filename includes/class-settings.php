<?php
/**
 * Admin settings and site identity synchronisation.
 *
 * @package Configuration123
 */

declare(strict_types=1);

namespace Configuration123;

use WP_Admin_Bar;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the protected admin interface and option sanitization.
 */
final class Settings {

	private const OPTION_GROUP = 'configuration123_group';
	private const PAGE_SLUG    = 'configuration123';

	/**
	 * Register WordPress hooks.
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_link' ), 85 );
		add_filter( 'plugin_action_links_' . plugin_basename( CONFIGURATION123_FILE ), array( $this, 'add_plugin_action_link' ) );
	}

	/**
	 * Add a top-level settings destination.
	 */
	public function add_menu_page(): void {
		add_menu_page(
			__( 'Configuration123', 'configuration123' ),
			__( 'Configuration123', 'configuration123' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			'dashicons-admin-settings',
			59
		);
	}

	/**
	 * Register the single versioned option through WordPress Settings API.
	 */
	public function register_setting(): void {
		register_setting(
			self::OPTION_GROUP,
			Defaults::OPTION_NAME,
			array(
				'type'              => 'array',
				'default'           => Defaults::get(),
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);
	}

	/**
	 * Sanitize every accepted field and synchronize WordPress-native identity.
	 *
	 * @param mixed $input Raw Settings API value.
	 * @return array<string, mixed>
	 */
	public function sanitize( mixed $input ): array {
		$stored = $this->options();

		if ( ! current_user_can( 'manage_options' ) || ! is_array( $input ) ) {
			return $stored;
		}

		$sanitized = Defaults::get();
		$text_keys = array(
			'site_name',
			'site_tagline',
			'organization_name',
			'owner_name',
			'owner_role',
			'owner_phone',
			'owner_whatsapp',
			'street_address',
			'city',
			'region',
			'postal_code',
			'country',
			'business_hours',
			'designer_name',
			'designer_practice',
			'designer_role',
			'designer_phone',
			'designer_location',
		);

		foreach ( $text_keys as $key ) {
			$sanitized[ $key ] = sanitize_text_field( (string) ( $input[ $key ] ?? '' ) );
		}

		$sanitized['site_description']  = sanitize_textarea_field( (string) ( $input['site_description'] ?? '' ) );
		$sanitized['designer_services'] = sanitize_textarea_field( (string) ( $input['designer_services'] ?? '' ) );
		$sanitized['owner_email']       = sanitize_email( (string) ( $input['owner_email'] ?? '' ) );
		$sanitized['designer_email']    = sanitize_email( (string) ( $input['designer_email'] ?? '' ) );

		$url_keys = array( 'designer_website', 'linkedin_url', 'facebook_url', 'instagram_url', 'github_url', 'x_url' );
		foreach ( $url_keys as $key ) {
			$sanitized[ $key ] = esc_url_raw( (string) ( $input[ $key ] ?? '' ) );
		}

		$allowed_types          = array( 'organization', 'person' );
		$requested_type         = sanitize_key( (string) ( $input['site_type'] ?? 'organization' ) );
		$sanitized['site_type'] = in_array( $requested_type, $allowed_types, true ) ? $requested_type : 'organization';

		$owner_fields = array_keys( Defaults::owner_public_fields() );
		$sanitized['public_owner_fields'] = array_values(
			array_intersect(
				$owner_fields,
				array_map( 'sanitize_key', (array) ( $input['public_owner_fields'] ?? array() ) )
			)
		);

		$designer_fields = array_keys( Defaults::designer_public_fields() );
		$sanitized['public_designer_fields'] = array_values(
			array_intersect(
				$designer_fields,
				array_map( 'sanitize_key', (array) ( $input['public_designer_fields'] ?? array() ) )
			)
		);

		$sanitized['enable_schema'] = ! empty( $input['enable_schema'] );

		if ( '' === $sanitized['site_name'] ) {
			$sanitized['site_name'] = (string) ( $stored['site_name'] ?? get_option( 'blogname', '' ) );
			add_settings_error(
				Defaults::OPTION_NAME,
				'configuration123_site_name_required',
				__( 'The site name cannot be empty. The previous name was kept.', 'configuration123' ),
				'error'
			);
		}

		update_option( 'blogname', $sanitized['site_name'] );
		update_option( 'blogdescription', $sanitized['site_tagline'] );

		do_action( 'configuration123_settings_saved', $sanitized, $stored );

		return $sanitized;
	}

	/**
	 * Load the admin stylesheet only on this plugin page.
	 *
	 * @param string $hook_suffix Current admin screen hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		$asset = CONFIGURATION123_PATH . 'admin/assets/admin.css';
		wp_enqueue_style(
			'configuration123-admin',
			CONFIGURATION123_URL . 'admin/assets/admin.css',
			array(),
			is_readable( $asset ) ? (string) filemtime( $asset ) : CONFIGURATION123_VERSION
		);
	}

	/**
	 * Render the complete settings screen.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage these settings.', 'configuration123' ) );
		}

		$options = $this->options();
		?>
		<div class="wrap configuration123-wrap">
			<header class="configuration123-hero">
				<div>
					<p class="configuration123-kicker"><?php esc_html_e( 'Reusable site identity', 'configuration123' ); ?></p>
					<h1><?php esc_html_e( 'Configuration123', 'configuration123' ); ?></h1>
					<p><?php esc_html_e( 'One protected place for the client, the site, and the professional who built it.', 'configuration123' ); ?></p>
				</div>
				<div class="configuration123-sync-status">
					<span aria-hidden="true"></span>
					<strong><?php esc_html_e( 'WordPress identity sync is active', 'configuration123' ); ?></strong>
					<small><?php esc_html_e( 'Site name and tagline update native WordPress settings when saved.', 'configuration123' ); ?></small>
				</div>
			</header>

			<?php settings_errors( Defaults::OPTION_NAME ); ?>

			<form action="options.php" method="post" class="configuration123-form">
				<?php settings_fields( self::OPTION_GROUP ); ?>

				<section class="configuration123-card">
					<div class="configuration123-card-heading"><span>01</span><div><h2><?php esc_html_e( 'Site identity', 'configuration123' ); ?></h2><p><?php esc_html_e( 'These first two fields drive WordPress’s native Site Title and Tagline everywhere the active theme uses them.', 'configuration123' ); ?></p></div></div>
					<div class="configuration123-fields configuration123-fields--two">
						<?php
						$this->text_field( 'site_name', __( 'Site name', 'configuration123' ), $options, true, __( 'Example: the new name of your uncle’s business.', 'configuration123' ) );
						$this->text_field( 'site_tagline', __( 'Site tagline', 'configuration123' ), $options, false, __( 'A short description used by themes and search metadata.', 'configuration123' ) );
						$this->text_field( 'organization_name', __( 'Business or organisation name', 'configuration123' ), $options, false, __( 'May match the site name, but can be the legal or operating name.', 'configuration123' ) );
						$this->select_field( 'site_type', __( 'Owner type', 'configuration123' ), $options, array( 'organization' => __( 'Organisation or business', 'configuration123' ), 'person' => __( 'Individual or freelancer', 'configuration123' ) ) );
						$this->textarea_field( 'site_description', __( 'Site description', 'configuration123' ), $options, __( 'A concise summary of the business and what the website provides.', 'configuration123' ), 'configuration123-field--wide' );
						?>
					</div>
				</section>

				<section class="configuration123-card">
					<div class="configuration123-card-heading"><span>02</span><div><h2><?php esc_html_e( 'Site owner', 'configuration123' ); ?></h2><p><?php esc_html_e( 'Client information remains private unless you select it in Public display below.', 'configuration123' ); ?></p></div></div>
					<div class="configuration123-fields configuration123-fields--two">
						<?php
						$this->text_field( 'owner_name', __( 'Owner name', 'configuration123' ), $options );
						$this->text_field( 'owner_role', __( 'Owner role', 'configuration123' ), $options );
						$this->text_field( 'owner_email', __( 'Owner email', 'configuration123' ), $options, false, '', '', 'email' );
						$this->text_field( 'owner_phone', __( 'Owner phone', 'configuration123' ), $options, false, '', '', 'tel' );
						$this->text_field( 'owner_whatsapp', __( 'WhatsApp', 'configuration123' ), $options, false, __( 'Use international format when possible.', 'configuration123' ) );
						$this->text_field( 'business_hours', __( 'Business hours', 'configuration123' ), $options );
						$this->text_field( 'street_address', __( 'Street address', 'configuration123' ), $options, false, '', 'configuration123-field--wide' );
						$this->text_field( 'city', __( 'City', 'configuration123' ), $options );
						$this->text_field( 'region', __( 'Region', 'configuration123' ), $options );
						$this->text_field( 'postal_code', __( 'Postal code', 'configuration123' ), $options );
						$this->text_field( 'country', __( 'Country', 'configuration123' ), $options );
						?>
					</div>
				</section>

				<section class="configuration123-card configuration123-card--designer">
					<div class="configuration123-card-heading"><span>03</span><div><h2><?php esc_html_e( 'Designer profile', 'configuration123' ); ?></h2><p><?php esc_html_e( 'Reusable defaults for your attribution, services, contact details, and professional profiles.', 'configuration123' ); ?></p></div></div>
					<div class="configuration123-fields configuration123-fields--two">
						<?php
						$this->text_field( 'designer_name', __( 'Designer name', 'configuration123' ), $options );
						$this->text_field( 'designer_practice', __( 'Practice name', 'configuration123' ), $options );
						$this->text_field( 'designer_role', __( 'Professional description', 'configuration123' ), $options, false, '', 'configuration123-field--wide' );
						$this->text_field( 'designer_email', __( 'Designer email', 'configuration123' ), $options, false, '', '', 'email' );
						$this->text_field( 'designer_phone', __( 'Designer phone', 'configuration123' ), $options, false, '', '', 'tel' );
						$this->text_field( 'designer_website', __( 'Designer website', 'configuration123' ), $options, false, '', '', 'url' );
						$this->text_field( 'designer_location', __( 'Working locations', 'configuration123' ), $options );
						$this->textarea_field( 'designer_services', __( 'Services', 'configuration123' ), $options, __( 'Enter one service per line.', 'configuration123' ), 'configuration123-field--wide' );
						$this->text_field( 'linkedin_url', __( 'LinkedIn URL', 'configuration123' ), $options, false, '', '', 'url' );
						$this->text_field( 'facebook_url', __( 'Facebook URL', 'configuration123' ), $options, false, '', '', 'url' );
						$this->text_field( 'instagram_url', __( 'Instagram URL', 'configuration123' ), $options, false, '', '', 'url' );
						$this->text_field( 'github_url', __( 'GitHub URL', 'configuration123' ), $options, false, '', '', 'url' );
						$this->text_field( 'x_url', __( 'X / Twitter URL', 'configuration123' ), $options, false, '', '', 'url' );
						?>
					</div>
				</section>

				<section class="configuration123-card">
					<div class="configuration123-card-heading"><span>04</span><div><h2><?php esc_html_e( 'Public display', 'configuration123' ); ?></h2><p><?php esc_html_e( 'Only selected fields are eligible for profile shortcodes and structured data.', 'configuration123' ); ?></p></div></div>
					<div class="configuration123-visibility-grid">
						<?php $this->checkbox_group( 'public_owner_fields', __( 'Client fields allowed publicly', 'configuration123' ), Defaults::owner_public_fields(), (array) $options['public_owner_fields'] ); ?>
						<?php $this->checkbox_group( 'public_designer_fields', __( 'Designer fields allowed publicly', 'configuration123' ), Defaults::designer_public_fields(), (array) $options['public_designer_fields'] ); ?>
					</div>
					<label class="configuration123-toggle">
						<input type="checkbox" name="<?php echo esc_attr( Defaults::OPTION_NAME ); ?>[enable_schema]" value="1" <?php checked( ! empty( $options['enable_schema'] ) ); ?>>
						<span><strong><?php esc_html_e( 'Add basic structured identity data', 'configuration123' ); ?></strong><small><?php esc_html_e( 'Outputs a lightweight Person or Organization JSON-LD record using only public fields.', 'configuration123' ); ?></small></span>
					</label>
				</section>

				<section class="configuration123-card configuration123-shortcodes">
					<div class="configuration123-card-heading"><span>05</span><div><h2><?php esc_html_e( 'Display anywhere', 'configuration123' ); ?></h2><p><?php esc_html_e( 'Paste these into a Shortcode block, page, post, widget, or template.', 'configuration123' ); ?></p></div></div>
					<div class="configuration123-code-grid">
						<div><code>[configuration123 field="owner_phone"]</code><small><?php esc_html_e( 'One public field', 'configuration123' ); ?></small></div>
						<div><code>[configuration123_owner_card]</code><small><?php esc_html_e( 'Client identity, location, and contact', 'configuration123' ); ?></small></div>
						<div><code>[configuration123_location]</code><small><?php esc_html_e( 'Selected address line', 'configuration123' ); ?></small></div>
						<div><code>[configuration123_contact]</code><small><?php esc_html_e( 'Actionable contact methods', 'configuration123' ); ?></small></div>
						<div><code>[configuration123_profile type="owner"]</code><small><?php esc_html_e( 'Selected client details', 'configuration123' ); ?></small></div>
						<div><code>[configuration123_profile type="designer"]</code><small><?php esc_html_e( 'Selected designer details', 'configuration123' ); ?></small></div>
						<div><code>[configuration123_attribution]</code><small><?php esc_html_e( 'Discreet designer attribution', 'configuration123' ); ?></small></div>
						<div><code>[configuration123_services]</code><small><?php esc_html_e( 'Designer services list', 'configuration123' ); ?></small></div>
						<div><code>[configuration123_socials]</code><small><?php esc_html_e( 'Available social links', 'configuration123' ); ?></small></div>
					</div>
				</section>

				<div class="configuration123-submit">
					<div><strong><?php esc_html_e( 'Ready to synchronize?', 'configuration123' ); ?></strong><span><?php esc_html_e( 'Saving immediately updates the native site name and tagline.', 'configuration123' ); ?></span></div>
					<?php submit_button( __( 'Save Configuration', 'configuration123' ), 'primary', 'submit', false ); ?>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Add a compact site-owner dashboard summary.
	 */
	public function add_dashboard_widget(): void {
		if ( current_user_can( 'manage_options' ) ) {
			wp_add_dashboard_widget(
				'configuration123_identity_widget',
				__( 'Site identity', 'configuration123' ),
				array( $this, 'render_dashboard_widget' )
			);
		}
	}

	/**
	 * Render the private dashboard summary.
	 */
	public function render_dashboard_widget(): void {
		$options = $this->options();
		$location = implode( ', ', array_filter( array( $options['city'], $options['region'], $options['country'] ) ) );
		?>
		<p><strong><?php echo esc_html( (string) $options['organization_name'] ); ?></strong><br><?php echo esc_html( (string) $options['site_tagline'] ); ?></p>
		<?php if ( ! empty( $options['owner_name'] ) ) : ?><p><?php echo esc_html( (string) $options['owner_name'] ); ?><?php echo ! empty( $options['owner_role'] ) ? ' · ' . esc_html( (string) $options['owner_role'] ) : ''; ?></p><?php endif; ?>
		<?php if ( '' !== $location ) : ?><p><?php echo esc_html( $location ); ?></p><?php endif; ?>
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ); ?>"><?php esc_html_e( 'Edit Configuration123', 'configuration123' ); ?></a></p>
		<?php
	}

	/**
	 * Add quick access to the toolbar for administrators.
	 *
	 * @param WP_Admin_Bar $admin_bar Current toolbar instance.
	 */
	public function add_admin_bar_link( WP_Admin_Bar $admin_bar ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$admin_bar->add_node(
			array(
				'id'    => 'configuration123',
				'title' => __( 'Configuration123', 'configuration123' ),
				'href'  => admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
			)
		);
	}

	/**
	 * Add Settings link on the Plugins screen.
	 *
	 * @param array<int, string> $links Existing action links.
	 * @return array<int, string>
	 */
	public function add_plugin_action_link( array $links ): array {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ) . '">' . esc_html__( 'Settings', 'configuration123' ) . '</a>'
		);

		return $links;
	}

	/**
	 * Return stored options merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	private function options(): array {
		$options = get_option( Defaults::OPTION_NAME, array() );

		return wp_parse_args( is_array( $options ) ? $options : array(), Defaults::get() );
	}

	/**
	 * Render a text-like input.
	 *
	 * @param string               $key         Option key.
	 * @param string               $label       Visible label.
	 * @param array<string, mixed> $options     Current options.
	 * @param bool                 $required    Whether required.
	 * @param string               $description Help text.
	 * @param string               $class_name  Additional wrapper class.
	 * @param string               $type        HTML input type.
	 */
	private function text_field( string $key, string $label, array $options, bool $required = false, string $description = '', string $class_name = '', string $type = 'text' ): void {
		?>
		<label class="configuration123-field <?php echo esc_attr( $class_name ); ?>" for="configuration123-<?php echo esc_attr( $key ); ?>">
			<span><?php echo esc_html( $label ); ?><?php echo $required ? ' <b aria-hidden="true">*</b>' : ''; ?></span>
			<input id="configuration123-<?php echo esc_attr( $key ); ?>" type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( Defaults::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) ( $options[ $key ] ?? '' ) ); ?>" <?php echo $required ? 'required' : ''; ?>>
			<?php if ( '' !== $description ) : ?><small><?php echo esc_html( $description ); ?></small><?php endif; ?>
		</label>
		<?php
	}

	/**
	 * Render a textarea.
	 *
	 * @param string               $key         Option key.
	 * @param string               $label       Visible label.
	 * @param array<string, mixed> $options     Current options.
	 * @param string               $description Help text.
	 * @param string               $class_name  Additional wrapper class.
	 */
	private function textarea_field( string $key, string $label, array $options, string $description = '', string $class_name = '' ): void {
		?>
		<label class="configuration123-field <?php echo esc_attr( $class_name ); ?>" for="configuration123-<?php echo esc_attr( $key ); ?>">
			<span><?php echo esc_html( $label ); ?></span>
			<textarea id="configuration123-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( Defaults::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>]" rows="5"><?php echo esc_textarea( (string) ( $options[ $key ] ?? '' ) ); ?></textarea>
			<?php if ( '' !== $description ) : ?><small><?php echo esc_html( $description ); ?></small><?php endif; ?>
		</label>
		<?php
	}

	/**
	 * Render a select.
	 *
	 * @param string               $key     Option key.
	 * @param string               $label   Visible label.
	 * @param array<string, mixed> $options Current options.
	 * @param array<string, string> $choices Available choices.
	 */
	private function select_field( string $key, string $label, array $options, array $choices ): void {
		?>
		<label class="configuration123-field" for="configuration123-<?php echo esc_attr( $key ); ?>">
			<span><?php echo esc_html( $label ); ?></span>
			<select id="configuration123-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( Defaults::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>]">
				<?php foreach ( $choices as $value => $choice_label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( (string) ( $options[ $key ] ?? '' ), $value ); ?>><?php echo esc_html( $choice_label ); ?></option><?php endforeach; ?>
			</select>
		</label>
		<?php
	}

	/**
	 * Render a visibility checkbox group.
	 *
	 * @param string                $key      Option key.
	 * @param string                $heading  Group heading.
	 * @param array<string, string> $choices  Available fields.
	 * @param array<int, string>    $selected Selected field keys.
	 */
	private function checkbox_group( string $key, string $heading, array $choices, array $selected ): void {
		?>
		<fieldset class="configuration123-checkbox-group">
			<legend><?php echo esc_html( $heading ); ?></legend>
			<?php foreach ( $choices as $field => $label ) : ?>
				<label><input type="checkbox" name="<?php echo esc_attr( Defaults::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>][]" value="<?php echo esc_attr( $field ); ?>" <?php checked( in_array( $field, $selected, true ) ); ?>><span><?php echo esc_html( $label ); ?></span></label>
			<?php endforeach; ?>
		</fieldset>
		<?php
	}
}

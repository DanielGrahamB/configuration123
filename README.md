# Configuration123

A reusable WordPress identity plugin for client sites. It keeps the site, its owner, and the site designer in one protected settings screen without coupling those details to a theme.

## Features

- Synchronizes **Site name** with WordPress `blogname`.
- Synchronizes **Site tagline** with WordPress `blogdescription`.
- Stores private client ownership, contact, address, and business-hour details.
- Includes a reusable Boaz Daniel Graham designer profile and service defaults.
- Controls which owner and designer fields are allowed on the public site.
- Adds field, profile, service, and social shortcodes.
- Adds a plugin-owned dynamic Gutenberg block that works independently of the active theme.
- Follows the WordPress interface locale in PHP, frontend output, and the Gutenberg editor.
- Includes maintained French (France and Canada) catalogs and an opt-in Google Cloud generator for other languages.
- Adds optional Person or Organization JSON-LD using public data only.
- Adds an administrator dashboard summary and toolbar shortcut.

## Shortcodes

```text
[configuration123 field="owner_phone"]
[configuration123_owner_card]
[configuration123_location]
[configuration123_contact]
[configuration123_profile type="owner"]
[configuration123_profile type="designer"]
[configuration123_attribution]
[configuration123_copyright]
[configuration123_services]
[configuration123_socials]
```

Fields not selected under **Public display** return no frontend output.

## Gutenberg block

Insert **Configuration123 Display** in a page, post, template, header, or footer. Its sidebar control can display the live site identity, owner or designer profile, owner card, contacts, location, services, social profiles, designer attribution, or copyright.

The block metadata is intentionally site-independent. Site names and other values are read from the current WordPress database whenever the block renders, so saved Configuration123 changes appear without editing theme files.

## Languages

English is the source language. Human-maintained `fr_FR` and `fr_CA` catalogs ship in `languages/`, alongside the versioned POT template used to add more reviewed translations.

For another WordPress locale, Configuration123 can generate a machine-translated interface pack through Cloud Translation Basic and cache it in that site's database. No translation request runs during ordinary admin, editor, or frontend page loads. Supply the server-side API key in `wp-config.php` and use **Configuration123 → Languages**:

```php
define( 'CONFIGURATION123_GOOGLE_TRANSLATE_API_KEY', 'your-server-api-key' );
```

Restrict the key to the Cloud Translation API and the hosting server where possible. Google Cloud billing and quotas remain the site operator's responsibility. Generated packs can be refreshed or removed from the settings screen.

## Theme API

```php
$phone = configuration123_get( 'owner_phone' );
```

Escape values for their final output context in the consuming theme.

## Deployment

Track the `configuration123` folder in its own Git repository or alongside a private reusable plugin collection. Plugin files can move through Git; each site’s saved identity remains in that site’s WordPress database.

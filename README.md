# Configuration123

A reusable WordPress identity plugin for client sites. It keeps the site, its owner, and the site designer in one protected settings screen without coupling those details to a theme.

## Version 1 features

- Synchronizes **Site name** with WordPress `blogname`.
- Synchronizes **Site tagline** with WordPress `blogdescription`.
- Stores private client ownership, contact, address, and business-hour details.
- Includes a reusable Boaz Daniel Graham designer profile and service defaults.
- Controls which owner and designer fields are allowed on the public site.
- Adds field, profile, service, and social shortcodes.
- Adds optional Person or Organization JSON-LD using public data only.
- Adds an administrator dashboard summary and toolbar shortcut.

## Shortcodes

```text
[configuration123 field="owner_phone"]
[configuration123_profile type="owner"]
[configuration123_profile type="designer"]
[configuration123_services]
[configuration123_socials]
```

Fields not selected under **Public display** return no frontend output.

## Theme API

```php
$phone = configuration123_get( 'owner_phone' );
```

Escape values for their final output context in the consuming theme.

## Deployment

Track the `configuration123` folder in its own Git repository or alongside a private reusable plugin collection. Plugin files can move through Git; each site’s saved identity remains in that site’s WordPress database.

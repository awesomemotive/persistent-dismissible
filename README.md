# Persistent Dismissible

A class for encapsulating the logic required to maintain a relationship between the database, a dismissible UI element (with an optional lifespan), and a user's desire to dismiss that UI element.

Think of this like a WordPress Transient, but without in-memory cache support, and that uses the `wp_usermeta` database table instead of `wp_options`.

We invented this class to centralize and minimize the code required to execute multiple different calls and checks to the User Meta and User Options APIs inside of WordPress Core.

We use this as the API to check if we should show persistent admin-area notices to logged-in WordPress users. Dismissals can be set to expire after some number of seconds. They are global by default in a multisite environment, but can be per-site. Custom values can be saved if necessary.

## Setup

Include in your plugin or theme however you feel is best.

Use with a PHP use statement:

```php
use Sandhills\Utils\Persistent_Dismissible as PD;
```

## Set

```php
PD::set( [
	'id' => 'sh_dismissible_promotion_xyz'
] );
```

## Get

```php
$is_dismissed = PD::get( [
	'id' => 'sh_dismissible_promotion_xyz'
] );
```

## Delete

```php
PD::delete( [
	'id' => 'sh_dismissible_promotion_xyz'
] );
```

## Arguments

```php
/**
 * @param array|string $args {
 *     Array or string of arguments to identify the persistent dismissible.
 *
 *     @type string      $id       Required. ID of the persistent dismissible.
 *     @type string      $user_id  Optional. User ID. Default to current user ID.
 *     @type int|string  $value    Optional. Value to store. Default to true.
 *     @type int|string  $life     Optional. Lifespan. Default to 0 (infinite)
 *     @type bool        $global   Optional. Multisite, all sites. Default true.
 * }
 */
```

----

This organization was created by (and is managed by) <a href="https://sandhillsdev.com">Sandhills Development, LLC</a>, where we aim to craft superior experiences through ingenuity, with <a href="https://sandhillsdev.com/commitments/">deep commitment</a> to (and appreciation for) the human element.

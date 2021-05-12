# Persistent Dismissible

Per-user WordPress Transients saved to user-meta without in-memory cache support

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

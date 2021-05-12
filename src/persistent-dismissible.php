<?php
/**
 * Sandhills Development Persistent Dismissible Utility
 *
 * @package SandhillsDev
 * @subpackage Utilities
 */
namespace Sandhills\Utils;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * This class_exists() check avoids a fatal error if this class exists in more
 * than one included plugin/theme, and should not be removed.
 */
if ( ! class_exists( 'Persistent_Dismissible' ) ) :

/**
 * Class for encapsulating the logic required to maintain a relationship between
 * the database, a dismissible UI element with an optional lifespan, and a
 * user's desire to dismiss that UI element.
 *
 * Think of this like a WordPress Transient, but without in-memory cache support
 * and that uses the `wp_usermeta` database table instead.
 *
 * @version 1.0.0
 */
class Persistent_Dismissible {

	/**
	 * Get the value of a persistent dismissible.
	 *
	 * @since 1.0.0
	 * @param array $args See parse_args().
	 * @return mixed User meta value on success, false on failure.
	 */
	public static function get( $args = array() ) {

		// Parse arguments.
		$r = self::parse_args( $args );

		// Bail if no unique ID.
		if ( ! self::check_args( $r ) ) {
			return false;
		}

		// Get prefixed option names.
		$prefix           = self::get_prefix( $r );
		$prefixed_id      = $prefix . $r['id'];
		$prefixed_timeout = $prefix . self::get_timeout_key( $r );

		// Get return value & timeout.
		$retval  = get_user_option( $prefixed_id,      $r['user_id'], $r['global'] );
		$timeout = get_user_option( $prefixed_timeout, $r['user_id'], $r['global'] );

		// If expired, delete it. This needs to be inside get() because we are
		// not relying on WP Cron for garbage collection. This mirrors behavior
		// found inside of WordPress core.
		if ( ( false !== $timeout ) && ( $timeout < time() ) ) {
			delete_user_option( $r['user_id'], $r['id'], $r['global'] );
			delete_user_option( $r['user_id'], $timeout, $r['global'] );
			$retval = false;
		}

		// Return the value.
		return $retval;
	}

	/**
	 * Set the value of a persistent dismissible.
	 *
	 * @since 1.0.0
	 * @param array $args See parse_args().
	 * @return int|bool User meta ID if the option didn't exist, true on
	 *                  successful update, false on failure.
	 */
	public static function set( $args = array() ) {

		// Parse arguments.
		$r = self::parse_args( $args );

		// Bail if no unique ID.
		if ( ! self::check_args( $r ) ) {
			return false;
		}

		// Calculate lifespan, and get prefixed option names.
		$lifespan         = time() + absint( $r['life'] );
		$timeout          = self::get_timeout_key( $r );
		$prefix           = self::get_prefix( $r );
		$prefixed_id      = $prefix . $r['id'];
		$prefixed_timeout = $prefix . $timeout;

		// No dismissible data, so add it.
		if ( false === get_user_meta( $r['user_id'], $prefixed_id, true ) ) {

			// Add lifespan.
			if ( ! empty( $r['life'] ) ) {
				add_user_meta( $r['user_id'], $prefixed_timeout, $lifespan, true );
			}

			// Add dismissible data.
			$retval = add_user_meta( $r['user_id'], $prefixed_id, $r['value'], true );

		// Dismissible data found in database.
		} else {

			// Plan to update.
			$update = true;

			// Dismissible to update has new lifespan.
			if ( ! empty( $r['life'] ) ) {

				// If lifespan is requested but the dismissible has no timeout,
				// delete them both and re-create them, to avoid race conditions.
				if ( false === get_user_meta( $r['user_id'], $prefixed_timeout, true ) ) {
					delete_user_option( $r['user_id'], $r['id'], $r['global'] );
					add_user_meta( $r['user_id'], $prefixed_timeout, $lifespan, true );
					$retval = add_user_meta( $r['user_id'], $prefixed_id, $r['value'], true );
					$update = false;

				// Update the lifespan.
				} else {
					update_user_option( $r['user_id'], $timeout, $lifespan, $r['global'] );
				}
			}

			// Update the dismissible value.
			if ( ! empty( $update ) ) {
				$retval = update_user_option( $r['user_id'], $r['id'], $r['value'], $r['global'] );
			}
		}

		// Return the value.
		return $retval;
	}

	/**
	 * Delete a persistent dismissible.
	 *
	 * @since 1.0.0
	 * @param array $args See parse_args().
	 * @return bool True on success, false on failure.
	 */
	public static function delete( $args = array() ) {

		// Parse arguments.
		$r = self::parse_args( $args );

		// Bail if no unique ID.
		if ( ! self::check_args( $r ) ) {
			return false;
		}

		// Create the timeout key.
		$timeout = self::get_timeout_key( $r );

		// Delete.
		delete_user_option( $r['user_id'], $r['id'], $r['global'] );
		delete_user_option( $r['user_id'], $timeout, $r['global'] );

		// Success.
		return true;
	}

	/**
	 * Parse array of key/value arguments.
	 *
	 * Used by get(), set(), and delete(), to ensure default arguments are set.
	 *
	 * @since 1.0.0
	 * @param array|string $args {
	 *     Array or string of arguments to identify the persistent dismissible.
	 *
	 *     @type string      $id       Required. ID of the persistent dismissible.
	 *     @type string      $user_id  Optional. User ID. Default to current user ID.
	 *     @type int|string  $value    Optional. Value to store. Default to true.
	 *     @type int|string  $life     Optional. Lifespan. Default to 0 (infinite)
	 *     @type bool        $global   Optional. Multisite, all sites. Default true.
	 * }
	 * @return array
	 */
	private static function parse_args( $args = array() ) {
		return wp_parse_args( $args, array(
			'id'      => '',
			'user_id' => get_current_user_id(),
			'value'   => true,
			'life'    => 0,
			'global'  => true,
		) );
	}

	/**
	 * Check that required arguments exist.
	 *
	 * @since 1.0.0
	 * @param array $args See parse_args().
	 * @return bool True on success, false on failure.
	 */
	private static function check_args( $args = array() ) {
		return ! empty( $args['id'] ) && ! empty( $args['user_id'] );
	}

	/**
	 * Get the string used to identify the key used to store the timeout.
	 *
	 * @since 1.0.0
	 * @param array $args See parse_args().
	 * @return string '_expires' appended to the ID.
	 */
	private static function get_timeout_key( $args = array() ) {
		return sanitize_key( $args['id'] ) . '_expires';
	}

	/**
	 * Get the string used to prefix user meta for non-global dismissibles.
	 *
	 * @since 1.0.0
	 * @global WPDB $wpdb
	 * @param array $args See parse_args().
	 * @return string Maybe includes the blog prefix.
	 */
	private static function get_prefix( $args = array() ) {
		global $wpdb;

		// Default value
		$retval = '';

		// Maybe append the blog prefix for non-global dismissibles
		if ( empty( $args['global'] ) ) {
			$retval = $wpdb->get_blog_prefix();
		}

		// Return
		return $retval;
	}
}

endif;

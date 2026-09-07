<?php
/**
 * Minimal local environment-file loader for Empire King.
 *
 * @package Empire_King
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function empire_king_load_local_env() {
	static $loaded = false;

	if ( $loaded ) {
		return;
	}

	$loaded   = true;
	$env_file = dirname( __DIR__, 2 ) . '/.env';

	if ( ! is_readable( $env_file ) ) {
		return;
	}

	$lines = file( $env_file, FILE_IGNORE_NEW_LINES );
	if ( false === $lines ) {
		return;
	}

	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( '' === $line || '#' === substr( $line, 0, 1 ) || false === strpos( $line, '=' ) ) {
			continue;
		}

		list( $key, $value ) = explode( '=', $line, 2 );
		$key                 = trim( $key );
		$value               = trim( $value );

		if ( ! preg_match( '/^[A-Za-z_][A-Za-z0-9_]*$/', $key ) || false !== getenv( $key ) ) {
			continue;
		}

		if ( strlen( $value ) >= 2 && ( '"' === $value[0] || "'" === $value[0] ) && $value[0] === substr( $value, -1 ) ) {
			$value = substr( $value, 1, -1 );
		}

		putenv( $key . '=' . $value );
		$_ENV[ $key ] = $value;
	}
}
empire_king_load_local_env();

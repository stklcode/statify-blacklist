<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Statify_Blacklist
 */

/**
 * Simulating the ABSPATH constant.
 *
 * @var boolean ABSPATH
 */
const ABSPATH = false;

/*
 * Include class files.
 */
require_once __DIR__ . '/../inc/class-statifyblacklist.php';
require_once __DIR__ . '/../inc/class-statifyblacklist-admin.php';
require_once __DIR__ . '/../inc/class-statifyblacklist-settings.php';
require_once __DIR__ . '/../inc/class-statifyblacklist-system.php';

// Include Composer autoloader.
require_once __DIR__ . '/../vendor/autoload.php';



/** @ignore */
function invoke_static( $class, $method_name, $parameters = array() ) {
	$reflection = new \ReflectionClass( $class );
	$method     = $reflection->getMethod( $method_name );
	$method->setAccessible( true );

	return $method->invokeArgs( null, $parameters );
}

// Some mocked WP functions.
$mock_options   = array();
$mock_multisite = false;
$settings_error = array();
$settings       = array();

/** @ignore */
function is_multisite() {
	global $mock_multisite;

	return $mock_multisite;
}

/** @ignore */
function wp_parse_args( $args, $defaults = '' ) {
	if ( is_object( $args ) ) {
		$r = get_object_vars( $args );
	} elseif ( is_array( $args ) ) {
		$r =& $args;
	} else {
		parse_str( $args, $r );
	}

	if ( is_array( $defaults ) ) {
		return array_merge( $defaults, $r );
	}

	return $r;
}

/** @ignore */
function get_option( $option, $default = false ) {
	global $mock_options;

	return isset( $mock_options[ $option ] ) ? $mock_options[ $option ] : $default;
}

/** @ignore */
function update_option( $option, $value, $autoload = null ) {
	global $mock_options;
	$mock_options[ $option ] = $value;
}

/** @ignore */
function wp_get_raw_referer() {
	return isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
}

function wp_parse_url( $value ) {
	return parse_url( $value );
}

/** @ignore */
function wp_unslash( $value ) {
	return is_string( $value ) ? stripslashes( $value ) : $value;
}

/** @ignore */
function __( $text, $domain = 'default' ) {
	return $text;
}

/** @ignore */
function add_settings_error( $setting, $code, $message, $type = 'error' ) {
	global $settings_error;
	$settings_error[] = array( $setting, $code, $message, $type );
}

/** @ignore */
function register_setting( $option_group, $option_name, $args = array() ) {
	global $settings;
	$settings[ $option_name ] = array(
		'group'    => $option_group,
		'args'     => $args,
		'sections' => array(),
	);
}

/** @ignore */
function add_settings_section( $id, $title, $callback, $page, $args = array() ) {
	global $settings;
	$settings[ $page ]['sections'][ $id ] = array(
		'title'    => $title,
		'callback' => $callback,
		'args'     => $args,
		'fields'   => array(),
	);
}

/** @ignore */
function add_settings_field( $id, $title, $callback, $page, $section = 'default', $args = array() ) {
	global $settings;
	$settings[ $page ]['sections'][ $section ]['fields'][ $id ] = array(
		'title'    => $title,
		'callback' => $callback,
		'args'     => $args,
	);
}

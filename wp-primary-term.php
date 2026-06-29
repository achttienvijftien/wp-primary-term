<?php
/**
 * Plugin Name: WP Primary term
 * Plugin URI: https://www.1815.nl
 * Description: Primary term selector for WordPress taxonomies.
 * Version: 1.0.1
 * Author: 1815
 * Author URI: https://www.1815.nl
 * Text Domain: wp-primary-term
 *
 * @package AchttienVijftien\Plugin\WPPrimaryTerm
 **/

namespace AchttienVijftien\Plugin\WPPrimaryTerm;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

// Load Composer autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

Bootstrap::boot();

require_once __DIR__ . '/functions.php';

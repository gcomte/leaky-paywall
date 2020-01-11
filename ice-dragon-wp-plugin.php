<?php
/**
 * Main PHP file used to for initial calls to zeen101's Leak Paywall classes and functions.
 *
 * @package zeen101's Leak Paywall
 * @since 1.0.0
 */
 
/*
Plugin Name: Ice Dragon
Plugin URI: https://github.com/ln-ice-dragon/ice-dragon-wp-plugin
Description: The first Bitcoin paywall for Wordpress based on the Lightning Network. Uses <a href="https://ice-dragon.ch" target="_blank">Ice Dragon</a>. Based on zeen101's <a href="https://github.com/zeen101/leaky-paywall" target="_blank">Leaky Paywall</a>.
Author: Puzzle ITC
Version: 0.0.1
Author URI: https://puzzle.ch/lightning
Tags: paywall, bitcoin, satoshis, lightning, lightning network, ice dragon, metered, pay wall, content monetization, metered access, metered pay wall, paid content
Text Domain: leaky-paywall
Domain Path: /i18n
*/

define( 'ICE_DRAGON_PAYWALL_NAME', 		'Ice Dragon Plugin for WordPress' );
define( 'ICE_DRAGON_PAYWALL_SLUG', 		'ice-dragon-paywall' );
define( 'LPAYWALL_VERSION',	'4.13.5' );
define( 'LPAYWALL_DB_VERSION',	'1.0.4' );
define( 'ICE_DRAGON_VERSION',	'0.0.1' );
define( 'ICE_DRAGON_PAYWALL_URL',		plugin_dir_url( __FILE__ ) );
define( 'ICE_DRAGON_PAYWALL_PATH', 		plugin_dir_path( __FILE__ ) );
define( 'ICE_DRAGON_PAYWALL_BASENAME',	plugin_basename( __FILE__ ) );
define( 'ICE_DRAGON_PAYWALL_REL_DIR',	dirname( ICE_DRAGON_PAYWALL_BASENAME ) );

/**
 * Instantiate Pigeon Pack class, require helper files
 *
 * @since 1.0.0
 */
function idra_plugins_loaded() {
	
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	require_once( 'class.php' );

	// Instantiate the Pigeon Pack class
	if ( class_exists( 'Ice_Dragon_Paywall' ) ) {
		
		global $ice_dragon_paywall;
		$ice_dragon_paywall = new Ice_Dragon_Paywall();
		
		require_once( 'functions.php' );
		require_once( 'metaboxes.php' );

		// error tracking
		include( ICE_DRAGON_PAYWALL_PATH . 'include/error-tracking.php' );

		// helper classes
		include( ICE_DRAGON_PAYWALL_PATH . 'include/class-restrictions.php' );

		// Internationalization
		load_plugin_textdomain( 'leaky-paywall', false, ICE_DRAGON_PAYWALL_REL_DIR . '/i18n/' );

		// Register Dragonsnest as Rest API endpoint
        require_once(ICE_DRAGON_PAYWALL_PATH . 'include/class-dragons-nest.php');
        $dragonsNest = new DragonsNest();
        $dragonsNest->registerRestAPI();
	}

}
add_action( 'plugins_loaded', 'idra_plugins_loaded', 4815162342 ); //wait for the plugins to be loaded before init

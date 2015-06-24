<?php
/*
Plugin Name: Wordpress SuperSonic with CloudFlare
Plugin URI: https://wordpress.org/plugins/supersonic/
Description: Takes Wordpress to Supersonic speed with CloudFlare
Version: 1.2.8
Author: Grzegorz Rola
Author URI: http://www.wp-supersonic.com
Text Domain: wpss
*/ 

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

include_once(dirname(__FILE__).'/inc/actions.php');
include_once(dirname(__FILE__).'/inc/countries.php');
include_once(dirname(__FILE__).'/inc/security.php');
include_once(dirname(__FILE__).'/inc/comments.php');
include_once(dirname(__FILE__).'/inc/config.php');
include_once(dirname(__FILE__).'/inc/statistics.php');
include_once(dirname(__FILE__).'/inc/crontab.php');
include_once(dirname(__FILE__).'/inc/eventlog.php');
include_once(dirname(__FILE__).'/inc/class_cloudflare.php');


$wpss_db_version = '1.2.3';


function wpss_install() {
	global $wpdb;
	global $wpss_db_version;

	$charset = 'CHARACTER SET UTF8';

	$table_name = $wpdb->prefix . 'wpss_links';

	$sql = "CREATE TABLE $table_name (
		url varchar(255) DEFAULT '' NOT NULL,
		type varchar(20) DEFAULT 'other' NOT NULL,
		type2 varchar(20) DEFAULT 'other' NOT NULL,
		id BIGINT(20) UNSIGNED default 0,
		PRIMARY KEY id (url),
		KEY type (type),
		KEY type2 (type,type2)
	) $charset";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	$table_name = $wpdb->prefix . 'wpss_clear';

	$sql = "CREATE TABLE $table_name (
		priority int(5) UNSIGNED default 0,
		url varchar(255) DEFAULT '' NOT NULL,
		PRIMARY KEY id (url),
		KEY priority (priority)
	) $charset";

	dbDelta( $sql );

	$table_name = $wpdb->prefix . 'wpss_log';

	$sql = "CREATE TABLE $table_name (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		event int(5) unsigned NOT NULL,
		time int(10) unsigned NOT NULL,
		ip varchar(50) NOT NULL,
		ip_country varchar(3) NOT NULL,
		info varchar(255), 
		PRIMARY KEY id (id),
		KEY event (event)
	) $charset";

	dbDelta( $sql );

	update_option( 'wpss_db_version', $wpss_db_version );
	if (!wp_next_scheduled('wpss_clear')) {
		wp_schedule_event( time(), 'hourly', 'wpss_clear' );
	}
	if (!wp_next_scheduled('wpss_log_clear')) {
		wp_schedule_event( time(), 'hourly', 'wpss_log_clear' );
	}	
	add_option( 'wpss_stats', array(), '', 'no' );
	//
}
register_activation_hook( __FILE__, 'wpss_install' );

function wpss_deinstall() {
	wp_clear_scheduled_hook('wpss_clear');
	wp_clear_scheduled_hook('wpss_log_clear');
	delete_option("wpss_stats");
}
register_deactivation_hook( __FILE__, 'wpss_deinstall' );


function wpss_plugins_loaded() {
	global $wpss_db_version;
	if ($wpss_db_version != get_option('wpss_db_version')) {
		wpss_install();
	}
}
add_action( 'plugins_loaded', 'wpss_plugins_loaded' );
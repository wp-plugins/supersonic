<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


function wpss_clear_f($max_count = 99) {
	global $wpdb;
	$count_row = 0;
	$settings = get_option( "wpss_settings" );
	$cf = new cloudflare_api($settings['cloudflare_login'], $settings['cloudflare_api_key']);
	$sql = 'select url from '.$wpdb->prefix.'wpss_clear order by priority';
	$rows = $wpdb->get_results($sql);
	foreach ($rows as $row) {
		if ($count_row > $max_count) {
			wp_schedule_single_event( time()+60, 'wpss_clear' );
			return;
		}
		$count_row++;
		$url = $row->url;
		if (strpos($url,'/') === 0) {
			$url = site_url().$url;
		}
		if (trim($url) == '') {
			$url = site_url();
		}
		$ret = $cf->zone_file_purge($settings['cloudflare_domain'],$url);		
		if ($ret->result != 'success') {
			wp_schedule_single_event( time()+60, 'wpss_clear' );
			wpss_log(20,'Purge failed: '.$ret->msg.' for &quot;'.$url.'&quot;');
			return;
		}
		$wpdb->delete($wpdb->prefix.'wpss_clear',array('url' => $row->url));
	}
}
add_action('wpss_clear','wpss_clear_f');


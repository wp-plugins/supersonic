<?php

// Exit if accessed directly
if (!defined( 'ABSPATH')) exit;

//$_SERVER['HTTP_CF_IPCOUNTRY']

function wpss_login_init() {	
	if (!isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
		return;
	}
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$settings = get_option('wpss_settings');
		if ($settings['security']['login_protection'] == 'deny') {
			$ip_country = $_SERVER['HTTP_CF_IPCOUNTRY'];
			if (!empty($settings['security']['login_countries']) && in_array($ip_country,$settings['security']['login_countries'])) {
				header('HTTP/1.0 403 Forbidden');
				echo 'You are forbidden! Protected by Wordpress SuperSonic.';
				wpss_log(1);
				die();
			}
		}
		if ($settings['security']['login_protection'] == 'allow') {
			$ip_country = $_SERVER['HTTP_CF_IPCOUNTRY'];
			if (!empty($settings['security']['login_countries']) && !in_array($ip_country,$settings['security']['login_countries'])) {
				wpss_log(1);
				header('HTTP/1.0 403 Forbidden');
				echo 'You are forbidden! Protected by Wordpress SuperSonic.';
				die();
			}
		}
	}
}
add_action( 'login_init', 'wpss_login_init' );

function wpss_pre_comment_on_post($post_id) {
	if (!isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
		return;
	}
	$settings = get_option('wpss_settings');
	if ($settings['security']['comment_protection'] == 'deny') {
		$ip_country = $_SERVER['HTTP_CF_IPCOUNTRY'];
		if (!empty($settings['security']['comment_countries']) && in_array($ip_country,$settings['security']['comment_countries'])) {
			header('HTTP/1.0 403 Forbidden');
			echo 'You are forbidden! Protected by Wordpress SuperSonic.';
			wpss_log(2);
			die();
		}
	}
	if ($settings['security']['comment_protection'] == 'allow') {
		$ip_country = $_SERVER['HTTP_CF_IPCOUNTRY'];
		if (!empty($settings['security']['comment_countries']) && !in_array($ip_country,$settings['security']['comment_countries'])) {
			header('HTTP/1.0 403 Forbidden');
			echo 'You are forbidden! Protected by Wordpress SuperSonic.';
			wpss_log(2);
			die();
		}
	}
}
add_action('pre_comment_on_post','wpss_pre_comment_on_post');

function wpss_wp_xmlrpc_server_class($class_name) {
	if (!isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
		return $class_name;
	}
	$settings = get_option('wpss_settings');
	if ($settings['security']['xmlrpc_protection'] == 'deny') {
		$ip_country = $_SERVER['HTTP_CF_IPCOUNTRY'];
		if (!empty($settings['security']['xmlrpc_countries']) && in_array($ip_country,$settings['security']['xmlrpc_countries'])) {
			header('HTTP/1.0 403 Forbidden');
			echo 'You are forbidden! Protected by Wordpress SuperSonic.';
			wpss_log(3);
			die();
		}
	}
	if ($settings['security']['xmlrpc_protection'] == 'allow') {
		$ip_country = $_SERVER['HTTP_CF_IPCOUNTRY'];
		if (!empty($settings['security']['xmlrpc_countries']) && !in_array($ip_country,$settings['security']['xmlrpc_countries'])) {
			header('HTTP/1.0 403 Forbidden');
			echo 'You are forbidden! Protected by Wordpress SuperSonic.';
			wpss_log(3);
			die();
		}
	}
	return $class_name;
}
add_filter( 'wp_xmlrpc_server_class', 'wpss_wp_xmlrpc_server_class');

function wpss_login_failed($username) {
	$settings = get_option('wpss_settings');
	if (!isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
		wpss_log(5, 'Username: '.$username.', without CloudFlare, host: '.$_SERVER['HTTP_HOST']);
	}
	else {
		wpss_log(5, 'Username: '.$username);
	}
	if ($settings['security']['bruteforce_protection'] == '1') {
		$ip = $_SERVER["REMOTE_ADDR"];
		$current_time = current_time('timestamp');
		$bruteforce = get_option('wpss_bruteforce');
		if ($bruteforce === false) {
			$bruteforce = array();
		}
		if (isset($bruteforce[$ip])) {
			$ip_data = $bruteforce[$ip];
			if ($ip_data['time'] < ($current_time-intval($settings['security']['bruteforce_reset'])*60)) {
				$ip_data = array();
			}
		}
		else {
			$ip_data = array();
		}
		if (empty($ip_data)) {
			$ip_data['time'] = $current_time;
			$ip_data['count'] = 1;
		}
		else {
			$ip_data['time'] = $current_time;
			$ip_data['count'] = intval($ip_data['count'])+1;			
		}
		if ($ip_data['count'] > intval($settings['security']['bruteforce_attempts'])) {
			$cf = new cloudflare_api($settings['cloudflare_login'], $settings['cloudflare_api_key']);			
			$ret = $cf->ban($ip);			
			if ($ret->status == 'success') {
				unset($bruteforce[$ip]);
				wpss_log(6, 'Username: '.$username);
			}
			else {
				wpss_log(6, 'CloudFlare error: '.$ret->msg.'<br>Username: '.$username);
			}
		}
		else {
			$bruteforce[$ip] = $ip_data;
		}
		update_option('wpss_bruteforce',$bruteforce);
	}
}
add_action( 'wp_login_failed','wpss_login_failed'); 


function wpss_login() {
	$bruteforce = get_option('wpss_bruteforce');
	$ip = $_SERVER["REMOTE_ADDR"];
	if ($bruteforce && isset($bruteforce[$ip])) {
		unset($bruteforce[$ip]);
		update_option('wpss_bruteforce',$bruteforce);
	}	
}
add_action('wp_login', 'wpss_login');


function wpss_login_message() {
	$settings = get_option('wpss_settings');
	if ($settings['security']['bruteforce_user_info']) {
		$attempts = intval($settings['security']['bruteforce_attempts']);
		$bruteforce = get_option('wpss_bruteforce');
		$ip = $_SERVER["REMOTE_ADDR"];
		$current_time = current_time('timestamp');
		if ($bruteforce) {
			if (isset($bruteforce[$ip])) {
				if (intval($bruteforce[$ip]['time']) > ($current_time-intval($settings['security']['bruteforce_reset'])*60)) {
					$attempts = $attempts-intval($bruteforce[$ip]['count']);
				}
			}
		}		
		$message = 'Remaining login attemps: '.$attempts;
		return $message;
	}
	else {
		return '';
	}
}
add_filter('login_message', 'wpss_login_message');
<?php

// Exit if accessed directly
if (!defined( 'ABSPATH')) exit;


function wpss_init() {
	if (isset($_SERVER["HTTP_CF_CONNECTING_IP"]) && isset($_SERVER["REMOTE_ADDR"]) && $_SERVER["HTTP_CF_CONNECTING_IP"] != $_SERVER["REMOTE_ADDR"]) {
		$_SERVER["REMOTE_ADDR"] = $_SERVER["HTTP_CF_CONNECTING_IP"];
		$_SERVER["REMOTE_HOST"] = $_SERVER["HTTP_CF_CONNECTING_IP"];
	}
	if (!headers_sent()) {
		header('X-WPSS-Powered-By: WP SuperSonic');
	} 		
}
add_action('init', 'wpss_init',1);

if( !function_exists('apache_request_headers') ) {
	///
	function apache_request_headers() {
  	$arh = array();
	  $rx_http = '/\AHTTP_/';
  	foreach($_SERVER as $key => $val) {
    	if( preg_match($rx_http, $key) ) {
      	$arh_key = preg_replace($rx_http, '', $key);
	      $rx_matches = array();
  	    // do some nasty string manipulations to restore the original letter case
    	  // this should work in most cases
      	$rx_matches = explode('_', $arh_key);
	      if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
  	      foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
    	    $arh_key = implode('-', $rx_matches);
      	}
	      $arh[$arh_key] = $val;
  	  }
	  }
  	return( $arh );
	}
}


function wpss_footer() {	
	/* 
	error_log(serialize(apache_request_headers()));
	foreach ($_SERVER as $k => $v) {
		error_log($k.' = '.$v);
	}
	foreach ($_REQUEST as $k => $v) {
		error_log($k.' = '.$v);
	}
	error_log($_SERVER['HTTP_CF_IPCOUNTRY']);
	/* */
	$settings = get_option('wpss_settings');
	if ($settings['cloudflare_api_key'] && $_SERVER['HTTP_CF_RAY']) {
		global $wpdb, $wp_query;
		$type = 'other';
		$type2 = 'other';
		$id = 0;	
		$url = $_SERVER["REQUEST_URI"];
		$proto = $_SERVER['HTTP_X_FORWARDED_PROTO'];		
		$host = $_SERVER['HTTP_HOST'];
		$url2 = $proto.'://'.$host;
		if ($url2 == site_url()) {
			$url2 = $_SERVER["REQUEST_URI"];
		}
		else {
			$url2 .= $_SERVER["REQUEST_URI"];
		}
		$url = $url2;
		$table_name = $wpdb->prefix . 'wpss_links';
		$sql = "select * from ".$table_name." where url = '$url'";
		$row = $wpdb->get_row($sql);
		if ($row == null) {
			if (is_home()) {
				$type = 'home';
				$type2 = 'home';
			}
			if (is_singular()) {
				$type = 'singular';
				if (is_singular('post')) {
					$type2 = 'post';
				}
				if (is_singular('product')) {
					$type2 = 'product';
				}
				if (is_singular('page')) {
					$type2 = 'page';
				}
				$queried_object = $wp_query->get_queried_object();
				$id = $queried_object->ID;
				$type2 = $queried_object->post_type;
			}
			if (is_feed()) {
				$type = 'feed';		
			}
			if (is_date()) {
				$type = 'date';
				if (is_day()) {
					$type2 = 'day';
					$id = get_the_time('Ymd');
				}
				else if (is_month()) {
					$type2 = 'month';
					$id = get_the_time('Ym');
				}
				else if (is_year()) {
					$type2 = 'year';
					$id = get_the_time('Y');
				}
			}
			if (is_tax()) {
				$type = 'tax';
				$queried_object = $wp_query->get_queried_object();
				$id = $queried_object->term_id;
				$type2 = $queried_object->taxonomy;
			}
			if (is_tag()) {
				$type = 'tax';
				$type2 = 'tag';
				$queried_object = $wp_query->get_queried_object();
				$id = $queried_object->term_id;
			}
			if (is_category()) {
				$type = 'tax';
				$type2 = 'category';
				$queried_object = $wp_query->get_queried_object();
				$id = $queried_object->term_id;
			}
			if (is_author()) {
				$type = 'author';
				$queried_object = $wp_query->get_queried_object();
				$id = $queried_object->author_id;
			}
			if (is_search()) {
				$type = 'search';
			}
			//
			$wpdb->insert($table_name,array('url' => $url, 'type' => $type, 'type2' => $type2, 'id' => $id));
		}
	}
}
//add_action('wp_footer','wpss_footer');
add_action('template_redirect','wpss_footer',1);
add_action('do_feed_rss2','wpss_footer',1);


function wpss_save_post($post_id, $post, $update) {
	if (wp_is_post_revision( $post_id )) return;
	$post = get_post($post_id);	
	//
	wpss_update($post);
}
add_action( 'save_post', 'wpss_save_post', 10, 3);

function wpss_save_comment($comment_id,$comment_approved) {
	$comment = get_comment($comment_id);
	$post = get_post($comment->comment_post_ID);
	wpss_update($post, 1);
}
add_action('wp_set_comment_status','wpss_save_comment',10,2);
add_action('edit_comment','wpss_save_comment',10,2);

function wpss_comment_post($comment_id,$comment_approved) {	
	if ($comment_approved == '1') {
		$comment = get_comment($comment_id);
		$post = get_post($comment->comment_post_ID);
		wpss_update($post, 1);
	}
}
add_action('comment_post','wpss_comment_post',10,2);


function wpss_determine_current_user($user_ID) {
	if (!is_admin()) {
		$settings = get_option('wpss_settings');
		$donotlogout_s = $settings['donotlogout'];
		$donotlogout = explode("\n",$donotlogout_s);
		foreach ($donotlogout as $url) {
			$url = trim($url);
			if (fnmatch($url,$_SERVER["REQUEST_URI"])) {
				return $user_ID;
			}
		}
		if ($_GET['preview'] == 'true') {
			return $user_ID;
		}
		return false;
	}
	else {
		return $user_ID;
	}
}
add_filter('determine_current_user','wpss_determine_current_user',1000);

function wpss_wp_get_current_commenter($commenter) {
	$commenter['comment_author'] = '';
	$commenter['comment_author_email'] = '';
	$commenter['comment_author_url'] = '';
	return $commenter;
}
add_filter('wp_get_current_commenter','wpss_wp_get_current_commenter',100,1);

function wpss_update($post, $comment = 0 ) {
	error_log('wpss_update '.$post->ID);
	global $wpdb;
	$settings = get_option( "wpss_settings" );
	$count_rows = 0;
	$post_type = $post->post_type;
	//
	if (($comment == 0 && $settings['refresh'][$post_type.'_this']) || ($comment && $settings['comments']['comment_this'])) {
		error_log('wpss_update t1');
		$sql = 'select url from '.$wpdb->prefix.'wpss_links where type = \'singular\' and id = '.$post->ID;
		$rows = $wpdb->get_results($sql);
		foreach ($rows as $row) {
			error_log('wpss_update '.$row->url);
			$wpdb->insert($wpdb->prefix."wpss_clear",array('url' => $row->url, 'priority' => 1));
			$count_rows++;
		}
	}
	if (($comment == 0 && $settings['refresh'][$post_type.'_home']) || ($comment && $settings['comments']['comment_home'])) {
		$sql = 'select url from '.$wpdb->prefix.'wpss_links where type = \'home\'';
		$rows = $wpdb->get_results($sql);
		foreach ($rows as $row) {
			$wpdb->insert($wpdb->prefix."wpss_clear",array('url' => $row->url, 'priority' => 2));
			$count_rows++;
		}
	}
	if (($comment == 0 && $settings['refresh'][$post_type.'_tax']) || ($comment && $settings['comments']['comment_tax'])) {
		$taxonomies = get_taxonomies('','names'); 
		$terms = wp_get_post_terms($post->ID,$taxonomies);
		foreach ($terms as $term) {
			while ($term) {				
				$sql = 'select url from '.$wpdb->prefix.'wpss_links where type = \'tax\' and id = '.$term->term_id;
				$rows = $wpdb->get_results($sql);
				foreach ($rows as $row) {
					$wpdb->insert($wpdb->prefix."wpss_clear",array('url' => $row->url, 'priority' => 3));
					$count_rows++;
				}
				if ($term->parent) {
					$term = get_term($term->parent,$term->taxonomy);
				}
				else {
					$term = 0;
				}
			}
		}
	}
	if (($comment == 0 && $settings['refresh'][$post_type.'_author']) || ($comment && $settings['comments']['comment_author'])) {
		$sql = 'select url from '.$wpdb->prefix.'wpss_links where type = \'author\' and id = '.$post->post_author;
		$rows = $wpdb->get_results($sql);
		foreach ($rows as $row) {
			$wpdb->insert($wpdb->prefix."wpss_clear",array('url' => $row->url, 'priority' => 4));
			$count_rows++;
		}		
	}
	if (($comment == 0 && $settings['refresh'][$post_type.'_date']) || ($comment && $settings['comments']['comment_date'])) {
		$id_in = get_the_time('Y',$post).','.get_the_time('Ym',$post).','.get_the_time('Ymd',$post);		
		$sql = 'select url from '.$wpdb->prefix.'wpss_links where type = \'author\' and id in ('.$id_in.')';
		$rows = $wpdb->get_results($sql);
		foreach ($rows as $row) {
			$wpdb->insert($wpdb->prefix."wpss_clear",array('url' => $row->url, 'priority' => 5));
			$count_rows++;
		}		
	}
	if (($comment == 0 && $settings['refresh'][$post_type.'_other']) || ($comment && $settings['comments']['comment_other'])) {
		$sql = 'select url from '.$wpdb->prefix.'wpss_links where type = \'other\'';
		$rows = $wpdb->get_results($sql);
		foreach ($rows as $row) {
			$wpdb->insert($wpdb->prefix."wpss_clear",array('url' => $row->url, 'priority' => 100));
			$count_rows++;
		}
	}
	if (($comment == 0 && $settings['refresh'][$post_type.'_add_clear']) || ($comment && $settings['comments']['comment_add_clear'])) {
		$add_clear = explode("\n",$settings['refresh'][$post_type.'_add_clear']);
		foreach ($add_clear as $url) {
			$url = trim($url);
			$url = str_replace('*','%',$url);
			$sql = 'select url from '.$wpdb->prefix.'wpss_links where url like \''.$url.'\'';
			$rows = $wpdb->get_results($sql);
			foreach ($rows as $row) {
				$wpdb->insert($wpdb->prefix."wpss_clear",array('url' => $row->url, 'priority' => 100));
				$count_rows++;
			}
		}
	}
	if ($settings['add_clear']) {
		$add_clear = explode("\n",$settings['add_clear']);
		foreach ($add_clear as $url) {
			$url = str_replace('*','%',$url);
			$sql = 'select url from '.$wpdb->prefix.'wpss_links where url like \''.$url.'\'';
			$rows = $wpdb->get_results($sql);
			foreach ($rows as $row) {
				$wpdb->insert($wpdb->prefix."wpss_clear",array('url' => $row->url, 'priority' => 100));
				$count_rows++;
			}
		}
	}
	if ($count_rows) {
		$sql = 'delete from '.$wpdb->prefix.'wpss_links where url in (select url from '.$wpdb->prefix.'wpss_clear)';
		$wpdb->query($sql);		
	}
	wpss_clear_f(5);
}

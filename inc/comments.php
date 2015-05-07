<?php

// Exit if accessed directly
if (!defined( 'ABSPATH')) exit;

function wpss_comment_post_country( $comment_id ) {
	if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
		add_comment_meta( $comment_id, 'ipcountry', $_SERVER['HTTP_CF_IPCOUNTRY'] );
	}
}
add_action( 'comment_post', 'wpss_comment_post_country' );


function wpss_comment_columns( $columns ) {
	$columns['ipcountry'] = __( 'SuperSonic' );
	return $columns;
}
add_filter( 'manage_edit-comments_columns', 'wpss_comment_columns' );

function wpss_comment_column( $column, $comment_ID ) {
	if ( 'ipcountry' == $column ) {
		if ( $meta = get_comment_meta( $comment_ID, $column , true ) ) {
			global $wpss_countries;
			$comment = get_comment($comment_ID);
			$ip = $comment->comment_author_IP;
			$ip_country = $meta;
			$settings = get_option('wpss_settings');
			$color = '#008800';
			if ($settings['security']['comment_protection'] == 'deny') {
				if (in_array($meta,$settings['security']['comment_countries'])) {
					$color = '#880000';
				}
			}
			if ($settings['security']['comment_protection'] == 'allow') {
				if (!in_array($meta,$settings['security']['comment_countries'])) {
					$color = '#880000';
				}
			}
			echo '<div style="color:'.$color.';">'.$wpss_countries[$meta].'<br/><img style="margin-top:5px;" src="'.plugins_url('/supersonic/flags/').strtolower($meta).'.png"/><span id="spinner-'.$comment_ID.'" class="spinner"></span></div>';
			echo '<div class="row-actions"><strong>IP</strong>: <span class="delete">';
			echo '<a title="Ban IP in CloudFlare" href="javascript:void(0);" onclick="wpss_ip_action(\''.$ip.'\',\''.$ip_country.'\',\'ban\',\''. wp_create_nonce( 'wpss_ip_nonce' ).'\','.$comment_ID.');" class="delete">BAN</a></span>';
			echo '<span> | <a title="White list IP in CloudFlare" href="javascript:void(0);" onclick="wpss_ip_action(\''.$ip.'\',\''.$ip_country.'\',\'wl\',\''. wp_create_nonce( 'wpss_ip_nonce' ).'\','.$comment_ID.');" class="delete">WL</a></span>';
			echo '<span> | <a title="Remove IP from CloudFlare lists" href="javascript:void(0);" onclick="wpss_ip_action(\''.$ip.'\',\''.$ip_country.'\',\'nul\',\''. wp_create_nonce( 'wpss_ip_nonce' ).'\','.$comment_ID.');" class="delete">NUL</a></span>';
			echo '<br/>';
/*			
			if ($settings['security']['comment_protection'] == 'deny') {
				if (in_array($meta,$settings['security']['comment_countries'])) {
					echo '<span class="delete"> <a href="JavaScript:void(0);" class="delete">Unblock Country</a></span>';
				}
				else {
					echo '<span class="delete"> <a href="JavaScript:void(0);" class="delete">Block Country</a></span>';
				}
			}
*/			
			echo '</div>';
			echo '<div id="message-'.$comment_ID.'"></div>';
		}
	}
}
add_filter( 'manage_comments_custom_column', 'wpss_comment_column', 10, 2 );


function wpss_comments_head() {
  echo '<style>
    .column-ipcountry {
      width:170px;
    }
  </style>';
  ?>
  <script type="text/javascript">
  	function wpss_ip_action(ip,ip_country,mode,nonce,ip_id,scr) {
  		//console.log(1);
  		scr = scr || 'comment';
  		jQuery('#spinner-'+ip_id).css('display','inline');
      jQuery.ajax({
 	       type : "post",
   	     dataType : "json",
     	   url : "<?php echo admin_url('admin-ajax.php'); ?>",
       	 data : {action: 'wpss_ip', ip: ip, ip_country: ip_country, mode: mode, nonce: nonce, scr : scr},
         success: function(ret) {
         		//console.log(ret);
   	     		if (typeof ret == 'object') {
   	     			if (ret.msg != '') {
 	     					jQuery('#message-'+ip_id).html(ret.msg);
 	     				}
   	     			if (ret.result != '') {
 	     					jQuery('#message-'+ip_id).html(ret.result);
 	     				}
   	     		}   	     		
   	     		else {
   	     			jQuery('#message-'+ip_id).html('Something wrong.');
   	     		}
       	 },
       	 error: function (request, status, error) {
       	 		//console.log(request);
       	 		jQuery('#message-'+ip_id).html(request.statusText);
       	 		setTimeout(function(){jQuery('#message-'+ip_id).html('');},5000);
    		 },
         complete: function() {
 	       		jQuery('#spinner-'+ip_id).css('display','none');
 	       		setTimeout(function(){jQuery('#message-'+ip_id).html('');},5000);
   	     }
     	})
     	return false;     		
  	}
  </script>
  <?php
}
add_action('admin_head', 'wpss_comments_head');

function wpss_set_comment_status($id, $status) {
	if ($status == 'spam') {
		$comment = get_comment($id);
		if ($comment) {
			$settings = get_option( "wpss_settings" );
			$cf = new cloudflare_api($settings['cloudflare_login'], $settings['cloudflare_api_key']);			
			$ret = $cf->spam($comment->comment_author, $comment->comment_author_email, $comment->comment_author_IP, substr($comment->comment_content, 0, 200));
			if ($ret->result == 'success') {
				$ip_country = get_comment_meta( $id, 'ipcountry' , true );
				wpss_log(10,'',$comment->comment_author_IP,$ip_country);
			}
			else {
				wpss_log(10,'CloudFlare error: '.$ret->msg.serialize($ret),$comment->comment_author_IP,$ip_country);
			}
		}
	}
}
//add_action('wp_set_comment_status', 'wpss_set_comment_status', 1, 2);

add_action("wp_ajax_wpss_ip", "wpss_ip_ajax");
function wpss_ip_ajax() {
	if ( !wp_verify_nonce( $_REQUEST['nonce'], "wpss_ip_nonce")) {
		exit("No naughty business please.");
	}
	$ip = $_REQUEST['ip'];   
	$ip_country = $_REQUEST['ip_country'];   
	$mode = $_REQUEST['mode'];   
	$scr = $_REQUEST['scr'];   
	//
	$settings = get_option( "wpss_settings" );
	$default_stats = $period;
	$stats = get_option("wpss_stats_".$default_stats);
	//
	$cf = new cloudflare_api($settings['cloudflare_login'], $settings['cloudflare_api_key']);			
	if ($mode == 'ban') {
		$ret = $cf->ban($ip);
		$event = ($scr == 'commment'?4:14);
		if ($ret->result != 'success') {
			wpss_log($event,'CloudFlare error: '.$ret->msg,$ip,$ip_country);
		}
		else {
			wpss_log($event,'',$ip,$ip_country);
		}		
	}
	if ($mode == 'wl') {
		$ret = $cf->wl($ip);
		$event = ($scr == 'commment'?7:17);
		if ($ret->result != 'success') {
			wpss_log($event,'CloudFlare error: '.$ret->msg,$ip,$ip_country);
		}
		else {
			wpss_log($event,'',$ip,$ip_country);
		}		
	}
	if ($mode == 'nul') {
		$ret = $cf->nul($ip);
		$event = ($scr == 'commment'?8:18);
		if ($ret->result != 'success') {
			wpss_log($event,'CloudFlare error: '.$ret->msg,$ip,$ip_country);
		}
		else {
			wpss_log($event,'',$ip,$ip_country);
		}		
	}
	echo json_encode($ret);
	die();
}
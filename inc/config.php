<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function wpss_admin_menu() {
	add_menu_page(__('SuperSonic', 'wpss'), __('SuperSonic', 'wpss'), 'manage_options', 'wpss', 'wpss_config_handler');
}
add_action('admin_menu', 'wpss_admin_menu');

function wpss_save_config() {
	$settings = get_option( "wpss_settings" );
	if (!$settings) {
		$settings = array();
	}
	if ( isset ( $_GET['tab'] ) ) {
  	$tab = $_GET['tab'];
  } 
  else {
  	$tab = 'cloudflare';
  }
  if ($tab == 'cloudflare') {
  	$settings['cloudflare_login'] = $_POST['wpss_cloudflare_login'];
  	$settings['cloudflare_api_key'] = $_POST['wpss_cloudflare_api_key'];
  	$settings['cloudflare_domain'] = $_POST['wpss_cloudflare_domain'];
  }
  if ($tab == 'options') {
  	$settings['donotlogout'] = $_POST['wpss_donotlogout'];
  	$settings['add_clear'] = $_POST['wpss_add_clear'];
  	$settings['event_log'] = 0;
  	if ($_POST['wpss_event_log'] == '1') {
  		$settings['event_log'] = $_POST['wpss_event_log'];
  	}
  	$settings['event_log_clear'] = $_POST['wpss_event_log_clear'];
  }
  if ($tab == 'post_types') {
  	$settings['refresh'] = $_POST['refresh'];
  	$settings['comments'] = $_POST['comments'];
  }
  if ($tab == 'comments') {
  	$settings['comments'] = $_POST['comments'];
  }
  if ($tab == 'security') {
  	$settings['security']['bruteforce_protection'] = $_POST['bruteforce_protection'];
  	$settings['security']['bruteforce_attempts'] = $_POST['bruteforce_attempts'];
  	$settings['security']['bruteforce_reset'] = $_POST['bruteforce_reset'];
  	$settings['security']['bruteforce_user_info'] = 0;
  	if ($_POST['bruteforce_user_info'] == '1') {
  		$settings['security']['bruteforce_user_info'] = $_POST['bruteforce_user_info'];
  	}
  	$settings['security']['bruteforce_admin_email'] = 0;
  	if ($_POST['bruteforce_admin_email'] == '1') {
  		$settings['security']['bruteforce_admin_email'] = $_POST['bruteforce_admin_email'];
  	}
  	//
  	$settings['security']['login_protection'] = $_POST['login_protection'];
  	$settings['security']['login_countries'] = $_POST['login_countries'];
  	$settings['security']['comment_protection'] = $_POST['comment_protection'];
  	$settings['security']['comment_countries'] = $_POST['comment_countries'];
  	$settings['security']['xmlrpc_protection'] = $_POST['xmlrpc_protection'];
  	$settings['security']['xmlrpc_countries'] = $_POST['xmlrpc_countries'];
  }
  $settings['update_time'] = current_time('timestamp');
  $updated = update_option( "wpss_settings", $settings );
}

function wpss_config_handler() {	
  echo '<div class="wrap">';
  echo '<div id="icon-settings" class="icon32"><br></div>';
  echo '<h2>Wordpress SuperSonic with CloudFlare</h2>';
  //echo '<em>Takes Wordpress to Supersonic speed with CloudFlare</em><br/><br/>';
	if ( $_POST["wpss-config-submit"] == 'Y' ) {
		//echo 1;
  	check_admin_referer("wpss-config");
   	wpss_save_config();
   	$url_parameters = isset($_GET['tab'])? 'tab='.$_GET['tab'] : 'updated=true';
   	//wp_redirect(admin_url('admin.php?page=wpss&'.$url_parameters));
   	//exit;
   	?>
		<div class="updated">
        <p><?php _e( 'Settings updated!', 'wpss' ); ?></p>
    </div>   	
    <?
  }	
  else if ($_GET['testcf']) {
  	$settings = get_option( "wpss_settings" );
		$cf = new cloudflare_api($settings['cloudflare_login'], $settings['cloudflare_api_key']);  	
		$url = site_url().'/?testcf';
		$ret = $cf->zone_file_purge($settings['cloudflare_domain'],$url);		
		if ($ret->result != 'success') {
			$msg = '';
			if (is_object($ret)) {
				$msg = '<b>'.$ret->msg.'</b>';
			}
	   	?>
			<div class="error">
    	    <p><?php _e( 'CloudFlare test not passed! '.$msg, 'wpss' ); ?></p>
    	</div>   	
    	<?
		}
		else {
	   	?>
			<div class="updated">
    	    <p><?php _e( 'CloudFlare test passed.', 'wpss' ); ?></p>
    	</div>   	
    	<?
		}		
  }
  else if ($_GET['tab'] == 'tools') {
  	$settings = get_option( "wpss_settings" );  	
  	$tools_action = $_POST['tools_action'];
  	//echo $tools_action;
  	if ($tools_action) {
  		if ($tools_action == 'url_list') {
  			$wpss_list_clear = $_POST['wpss_list_clear'];
  			$links = explode("\n",$wpss_list_clear);
  			global $wpdb;
  			$count_rows = 0;
  			foreach ($links as $link) {
  				$count_rows++;
	  			$wpdb->insert($wpdb->prefix."wpss_clear",array('url' => $link, 'priority' => 1));
  			}
  			if ($count_rows) {
					$sql = 'delete from '.$wpdb->prefix.'wpss_links where url in (select url from '.$wpdb->prefix.'wpss_clear)';
					$wpdb->query($sql);
  				wp_schedule_single_event( time(), 'wpss_clear' );
   				?>
					<div class="updated">
   	  			<p><?php _e( 'Cached files will be purged in next wp-cron run.', 'wpss' ); ?></p>
   				</div>   	
   				<?php
   			}
  		}
  		if ($tools_action == 'ban_ip' || $tools_action == 'wl_ip' || $tools_action == 'nul_ip') {
  			$wpss_list_ip = $_POST['wpss_list_ip'];
  			$cf = new cloudflare_api($settings['cloudflare_login'], $settings['cloudflare_api_key']);			
  			$errors = '';
  			foreach ($wpss_list_ip as $ip) {
  				if ($errors == '') {
  					if ($tools_action = 'ban_ip') {
  						$ret = $cf->ban($ip);
  					}
  					if ($tools_action = 'wl_ip') {
  						$ret = $cf->wl($ip);
  					}
  					if ($tools_action = 'nul_ip') {
  						$ret = $cf->nul($ip);
  					}
						if ($ret->result != 'success') {
							$errors = $ret->msg;
						}
					}
  			}
  			if ($errors == '') {
  				?>
					<div class="updated">
   	  			<p><?php _e( 'Operation completed.', 'wpss' ); ?></p>
   				</div>   	
   				<?php
  			}
  			else {
  				?>
					<div class="error">
   	  			<p><?php echo _e( 'Operation failed:', 'wpss' ).' '.$errors; ?></p>
   				</div>   	
   				<?php
  			}
  		}
  	}
  	else if ($_GET['wpss_action'] == 'clear_all') {
  		$cf = new cloudflare_api($settings['cloudflare_login'], $settings['cloudflare_api_key']);
  		$ret = $cf->fpurge_ts($settings['cloudflare_domain']);		
			if ($ret->result != 'success') {
				$msg = '';
				if (is_object($ret)) {
					$msg = $ret->msg;
				}
	   		?>
				<div class="error">
    	  	  <p><?php _e( 'CloudFlare error: <b>'.$msg.'</b>', 'wpss' ); ?></p>
    		</div>   	
    		<?
			}
			else {
				global $wpdb;
				$table_name = $wpdb->prefix . 'wpss_links';
				$wpdb->query('TRUNCATE '.$table_name);
				$table_name = $wpdb->prefix . 'wpss_clear';
				$wpdb->query('TRUNCATE '.$table_name);
	   		?>
				<div class="updated">
    	  	  <p><?php _e( 'CloudFlare cache purged.', 'wpss' ); ?></p>
    		</div>   	
    		<?
			}		
  	}
  	else if ($_GET['wpss_action'] == 'clear_cached') {
			global $wpdb;
			$count_rows = 0;
			$sql = 'select url from '.$wpdb->prefix.'wpss_links';
			$rows = $wpdb->get_results($sql);
			foreach ($rows as $row) {
				$wpdb->insert($wpdb->prefix."wpss_clear",array('url' => $row->url, 'priority' => 1));				
				$count_rows++;
			}
			if ($count_rows) {
				$sql = 'delete from '.$wpdb->prefix.'wpss_links where url in (select url from '.$wpdb->prefix.'wpss_clear)';
				$wpdb->query($sql);
				wp_schedule_single_event( time(), 'wpss_clear' );
   			?>
				<div class="updated">
   	  		<p><?php _e( 'Cached files will be purged in next wp-cron run.', 'wpss' ); ?></p>
   			</div>   	
   			<?php
   		}
   		else {   			
   			?>
				<div class="updated">
   	  		<p><?php _e( 'There are no files to purge.', 'wpss' ); ?></p>
   			</div>   	
   			<?php
   		}
  	}
  }
	if ( isset ( $_GET['tab'] ) ) wpss_config_handler_tabs($_GET['tab']); else wpss_config_handler_tabs('cloudflare');	
	echo '</div>';
	echo '<!-- wrap -->';
}

function wpss_config_handler_tabs( $current = 'cloudflare' ) {
		$settings = get_option( "wpss_settings" );		
		if (!$settings) {
			$settings = array();
		}
		if (!isset($settings['security']['bruteforce_protection'])) {
			$settings['security']['bruteforce_protection'] = 0;
		}
		if (!isset($settings['security']['bruteforce_attempts'])) {
			$settings['security']['bruteforce_attempts'] = 10;
		}
		if (!isset($settings['security']['bruteforce_reset'])) {
			$settings['security']['bruteforce_reset'] = 300;
		}
    $tabs = array( 'cloudflare' => 'Cloudflare Settings'
    						 , 'options' => 'Options'
    						 , 'tools' => 'Tools'
    						 , 'post_types' => 'Cache Purge
    						 '/*, 'comments' => 'Comments'*/
    						 , 'security' => 'Security'
    						 , 'log' => 'Log'
    						 , 'statistics' => 'Statistics'
    						 , 'documentation' => 'Documentation'
    						 , 'donate' => 'Donate' );
    ?>
    <div id="poststuff">
    	<div id="post-body" class="metabox-holder">
    		<div id="post-body-content" style="">
    <?php
    echo '<h2 class="nav-tab-wrapper">';
    foreach( $tabs as $tab => $name ){
        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
        echo "<a class='nav-tab$class' href='?page=wpss&tab=$tab'>$name</a>";
    }
    echo '</h2>';
    $form_method = 'POST';
    if ($tab == 'log') {
    	$form_method = 'GET';
    }
    ?>
    <?php if ($tab != 'tools'	&& $tab != 'statistics' && $tab != 'documentation' && $tab != 'log' && $tab != 'donate') { ?>
    <form id="wpss_settings" method="<?php echo $form_method; ?>" action="<?php /* echo admin_url( 'admin.php?page=wpss&tab='.$tab ); */ ?>">
    <?php } ?>
			<?php
			wp_nonce_field( "wpss-config" );
			if ( isset ( $_GET['tab'] ) ) $tab = $_GET['tab']; else $tab = 'cloudflare';			
			if ($tab == 'cloudflare') {
				echo '<table class="form-table">';
				?>
        <tr>
        	<th><label for="wpss_cloudflare_login">Cloudflare login:</label></th>
            <td>
               <input style="width:340px;" id="wpss_cloudflare_login" name="wpss_cloudflare_login" type="email" value="<? echo $settings['cloudflare_login']; ?>" /><br/>
            </td>
        </tr>
        <tr>
        	<th><label for="wpss_cloudflare_api_key">Cloudflare API key:</label></th>
            <td>
               <input style="width:340px;" id="wpss_cloudflare_api_key" name="wpss_cloudflare_api_key" type="text" value="<? echo $settings['cloudflare_api_key']; ?>" /><br/>
               <span class="description">CloudFlare API key You can find <a href="https://www.cloudflare.com/my-account">here</a>.</span>               
            </td>
        </tr>
        <tr>
        	<th><label for="wpss_cloudflare_api_key">Cloudflare domain:</label></th>
            <td>
               <input style="width:340px;" id="wpss_cloudflare_domain" name="wpss_cloudflare_domain" type="text" value="<? echo $settings['cloudflare_domain']; ?>" /><br/>
               <span class="description">Domain must be added and activated on Your CloudFlare account.</span>                              
            </td>
        </tr>
        <tr>
        	<th></th>
        	<td><a href="<?php echo admin_url( 'admin.php?page=wpss&testcf=1' ); ?>" class="button">Test CloudFlare Connection</a></td>
        </tr>
        <?php				
				echo '</table>';
			}
			if ($tab == 'options') {
				echo '<table class="form-table">';
				?>
        <tr>
        	<th><label for="wpss_donotlogout">Do not logout:</label></th>
            <td>
            	<textarea style="width:340px;height:100px;" id="wpss_donotlogout" name="wpss_donotlogout" ><? echo $settings['donotlogout']; ?></textarea><br/>
            	<span class="description">
            		By default SuperSonic serves all Wordpress front end pages as for non logged in users. Here you can define paths for pages to disable this future.<br/>
            		You can use widcard *. Ex: /user-area*. One URL per line. Use only relative path to site URL, ex: /path.<br/>
            		<strong>In CloudFlare page rules You must define page rule for thise URLs with Custom caching <font color="red">Bypass cache</font>.</strong>
            	</span>                              
            </td>
        </tr>
        <tr>
        	<th><label for="wpss_add_clear">Additional URLs to clear:</label></th>
            <td>
            	<textarea style="width:340px;height:100px;" id="wpss_add_clear" name="wpss_add_clear" ><? echo $settings['add_clear']; ?></textarea><br/>
            	<span class="description">
            		Define URLs to clear from CloudFlare cache on every event (every post types or comments are created, edited, deleted.)
            		You can use widcard *. Ex: /sitemap*. One URL per line. Use only relative path to site URL, ex: /path.<br/>
            	</span>                              
            </td>
        </tr>
        <tr>
        	<th><label for="wpss_event_log">Enable event logging</label></th>
            <td>
            	<input id="wpss_event_log" name="wpss_event_log" type="checkbox" value="1" <?php echo ($settings['event_log']=='1')?'checked':''; ?>>
            	<span class="description">            		
            	</span>                              
            </td>
        </tr>
				<tr>
					<th>
						<label style="vertical-align:top;" for="wpss_event_log_clear">Delete log entries older than</label> 
					</th>
					<td>
						<input type="number" min="0" max="370" id="wpss_event_log_clear" name="wpss_event_log_clear" size="3" value="<?php echo $settings['event_log_clear']; ?>"/> days
						<br/>
						<span class="description">0 - disable deleting log entries</span>
					</td>
				</tr>
        <?php				
				echo '</table>';
			}
			if ($tab == 'tools') {
				echo '<table class="form-table">';
				?>
        <tr>
        	<th></th>
        	<td>
        		<a href="<?php echo admin_url( 'admin.php?page=wpss&tab=tools&wpss_action=clear_all' ); ?>" class="button">Purge CloudFlare cache</a><br/>
        		<span class="description">Purge all data from CloudFlare cache. It includes all static content.</span>
        	</td>
        </tr>
        <tr>
        	<th></th>
        	<td>
        		<a href="<?php echo admin_url( 'admin.php?page=wpss&tab=tools&wpss_action=clear_cached' ); ?>" class="button">Purge cached files</a><br/>
        		<span class="description">Purge all content send to users and registered by SuperSonic plugin since last purging.</span>
        	</td>
        </tr>
        <tr>
        	<th><label for="wpss_list_clear">List of URLs to clear:</label></th>
            <td>
            	<textarea style="width:340px;height:100px;" id="wpss_list_clear" name="wpss_list_clear" ></textarea><br/>
            	<span class="description">
            		Enter single URL or list of URLs to purge from CloudFlare cache. Each URL in new line.<br/>
            		<input type="button" name="url_list" class="button" value="Purge list" onclick="jQuery('#tools_action').val('url_list');this.form.submit();">
            	</span>                              
            </td>
        </tr>
        <tr>
        	<th><label for="wpss_list_ip">List of IP adresses:</label></th>
            <td>
            	<textarea style="width:340px;height:100px;" id="wpss_list_ip" name="wpss_list_ip" ></textarea><br/>
            	<span class="description">
            		Enter single IP or list of IPs to Ban, White list or remove from list. Each IP in new line.<br/>
            		<input type="button" name="ban_ip" class="button" value="Ban" onclick="jQuery('#tools_action').val('ban_ip');this.form.submit();">
            		<input type="button" name="wl_ip" class="button" value="White list" onclick="jQuery('#tools_action').val('wl_ip');this.form.submit();">
            		<input type="button" name="wl_ip" class="button" value="Nul" onclick="jQuery('#tools_action').val('nul_ip');this.form.submit();">
            	</span>                              
            </td>
        </tr>
        <?php				
				echo '</table>';				
				echo '<input type="hidden" id="tools_action" name="tools_action" value="">';
			}
			if ($tab == 'post_types') {
				$post_types = get_post_types( array( 'public' => true ), 'objects' ); 
				foreach ( $post_types as $post_type ) {
					$name = $post_type->name;					
					echo '<div class="metabox-holder"><div class="postbox "><div class="handlediv" title=""><br /></div><h3 class="hndle"><span>'.$post_type->labels->name.'</span></h3><div class="inside">';
					echo '<table class="form-table">';
					?>
					<tr>
						<th colspan="2">
							Specify the pages and feeds to purge from CloudFlare cache when <?php echo $name; ?> are created, edited or deleted<br/><br/>
							<?php 
								$item_name = $name.'_'.'this'; 
								$item_label = $post_type->labels->singular_name;
							?>
							<input id="<?php echo $item_name; ?>" name="refresh[<?php echo $item_name; ?>]" type="checkbox" value="1" <?php echo ($settings['refresh'][$item_name]=='1')?'checked':''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br/>
							<?php 
								$item_name = $name.'_home'; 
								$item_label = 'Home page';
							?>
							<input id="<?php echo $item_name; ?>" name="refresh[<?php echo $item_name; ?>]" type="checkbox" value="1" <?php echo ($settings['refresh'][$item_name]=='1')?'checked':''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br/>
							<?php 
								$item_name = $name.'_tax';
								$item_label = 'Taxonomy pages'; 
							?>
							<input id="<?php echo $item_name; ?>" name="refresh[<?php echo $item_name; ?>]" type="checkbox" value="1" <?php echo ($settings['refresh'][$item_name]=='1')?'checked':''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br/>
							<?php 
								$item_name = $name.'_author'; 
								$item_label = 'Author pages'; 
							?>
							<input id="<?php echo $item_name; ?>" name="refresh[<?php echo $item_name; ?>]" type="checkbox" value="1" <?php echo ($settings['refresh'][$item_name]=='1')?'checked':''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br/>
							<?php 
								$item_name = $name.'_date';
								$item_label = 'Date pages'; 
						 	?>
							<input id="<?php echo $item_name; ?>" name="refresh[<?php echo $item_name; ?>]" type="checkbox" value="1" <?php echo ($settings['refresh'][$item_name]=='1')?'checked':''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br/>
							<?php 
								$item_name = $name.'_search';
								$item_label = 'Search pages'; 
						 	?>
							<input id="<?php echo $item_name; ?>" name="refresh[<?php echo $item_name; ?>]" type="checkbox" value="1" <?php echo ($settings['refresh'][$item_name]=='1')?'checked':''; ?>> 
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br/>
							<?php 
								$item_name = $name.'_add_clear';
								$item_label = 'Additional pages'; 
						 	?>
						</th>
					</tr>
					<tr>
						<th>
							<label style="vertical-align:top;" for="<?php echo $item_name; ?>"><?php echo $item_label; ?> </label> 
						</th>
						<td>
							<textarea style="width:50%; height: 60px;" id="<?php echo $item_name; ?>" name="refresh[<?php echo $item_name; ?>]"><?php echo $settings['refresh'][$item_name]; ?></textarea><br/>
							<span class="description">Specify additional pages to purge from Cloudflare cache. You can use widcard *. Ex: /sitemap*.xml. One URL per line. Use only relative path to site URL, ex: /path.</span>
						</td>
					</tr>
					<?php
   				echo '</table>';
   				echo '</div></div></div>';
				}
				//print_r($settings);
			}
			if ($tab == 'comments' || $tab == 'post_types') {
				echo '<div class="metabox-holder"><div class="postbox "><div class="handlediv" title=""><br /></div><h3 class="hndle"><span>'.'Comments'.'</span></h3><div class="inside">';
				echo '<table class="form-table">';
				$name = 'comment';
				?>
				<tr>
					<th colspan="2">
						Specify the pages and feeds to purge from CloudFlare cache when comments are created, edited or deleted<br/><br/>
						<?php 
							$item_name = $name.'_'.'this'; 
							$item_label = "Post";
						?>
						<input id="<?php echo $item_name; ?>" name="comments[<?php echo $item_name; ?>]" type="checkbox" value="1" <?php echo ($settings['comments'][$item_name]=='1')?'checked':''; ?>>
						<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br/>
						<?php 
							$item_name = $name.'_home'; 
							$item_label = 'Home page';
						?>
						<input id="<?php echo $item_name; ?>" name="comments[<?php echo $item_name; ?>]" type="checkbox" value="1" <?php echo ($settings['comments'][$item_name]=='1')?'checked':''; ?>>
						<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br/>
						<?php 
							$item_name = $name.'_tax';
							$item_label = 'Taxonomy pages'; 
						?>
						<input id="<?php echo $item_name; ?>" name="comments[<?php echo $item_name; ?>]" type="checkbox" value="1" <?php echo ($settings['comments'][$item_name]=='1')?'checked':''; ?>>
						<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br/>
						<?php 
							$item_name = $name.'_author'; 
							$item_label = 'Author pages'; 
						?>
						<input id="<?php echo $item_name; ?>" name="comments[<?php echo $item_name; ?>]" type="checkbox" value="1" <?php echo ($settings['comments'][$item_name]=='1')?'checked':''; ?>>
						<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br/>
						<?php 
							$item_name = $name.'_date';
							$item_label = 'Date pages'; 
					 	?>
						<input id="<?php echo $item_name; ?>" name="comments[<?php echo $item_name; ?>]" type="checkbox" value="1" <?php echo ($settings['comments'][$item_name]=='1')?'checked':''; ?>>
						<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br/>
						<?php 
							$item_name = $name.'_search';
							$item_label = 'Search pages'; 
					 	?>
						<input id="<?php echo $item_name; ?>" name="comments[<?php echo $item_name; ?>]" type="checkbox" value="1" <?php echo ($settings['comments'][$item_name]=='1')?'checked':''; ?>> 
						<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br/>
						<?php 
							$item_name = $name.'_add_clear';
							$item_label = 'Additional pages'; 
					 	?>
					</th>
				</tr>
				<tr>
					<th>
						<label style="vertical-align:top;" for="<?php echo $item_name; ?>"><?php echo $item_label; ?> </label> 
					</th>
					<td>
						<textarea style="width:50%; height: 60px;" id="<?php echo $item_name; ?>" name="comments[<?php echo $item_name; ?>]"><?php echo $settings['comments'][$item_name]; ?></textarea><br/>
						<span class="description">Specify additional pages to purge from Cloudflare cache. You can use widcard *. Ex: /sitemap*.xml. One URL per line. Use only relative path to site URL, ex: /path.</span>
					</td>
				</tr>
				<?php
 				echo '</table>';
 				echo '</div></div></div>';
			}
			if ($tab == 'security') {
				//
				echo '<div class="metabox-holder"><div class="postbox "><div class="handlediv" title=""><br /></div><h3 class="hndle"><span>'.'Brute force protection'.'</span></h3><div class="inside">';
				echo '<table class="form-table">';
				//
				$protection_name = 'login';
				?>
				<tr>
					<th>
						<label style="vertical-align:top;" for="bruteforce_protection">Protection mode</label> 
					</th>
					<td>
						<select name="bruteforce_protection">
							<option value="0" <?php echo $settings['security']['bruteforce_protection']=='0'?'selected':''; ?>>Disabled</option>
							<option value="1" <?php echo $settings['security']['bruteforce_protection']=='1'?'selected':''; ?>>Enbled</option>
						</select>
						<br/>
						<span class="description"></span>
					</td>
				</tr>
				<tr>
					<th>
						<label style="vertical-align:top;" for="bruteforce_attempts">Allowed login attempts</label> 
					</th>
					<td>
						<input type="number" min="3" max="100" name="bruteforce_attempts" size="3" value="<?php echo $settings['security']['bruteforce_attempts']; ?>"/>
						<br/>
						<span class="description"></span>
					</td>
				</tr>
				<tr>
					<th>
						<label style="vertical-align:top;" for="bruteforce_reset">Reset time</label> 
					</th>
					<td>
						<input type="number" min="1" max="10080" name="bruteforce_reset" size="3" value="<?php echo $settings['security']['bruteforce_reset']; ?>"/> minutes
						<br/>
						<span class="description"></span>
					</td>
				</tr>
				<tr>
					<th>
						<label style="vertical-align:top;" for="bruteforce_user_info">Inform user about remaining login attempts</label> 
					</th>
					<td>
						<input type="checkbox" value="1" name="bruteforce_user_info"  <?php echo ($settings['security']['bruteforce_user_info']==1)?'checked':''; ?>/>
						<br/>
						<span class="description"></span>
					</td>
				</tr>
				<tr>
					<th>
						<label style="vertical-align:top;" for="bruteforce_admin_email">Send email to administrator when IP has been blocked</label> 
					</th>
					<td>
						<input type="checkbox" value="1" name="bruteforce_admin_email" <?php echo ($settings['security']['bruteforce_admin_email']==1)?'checked':''; ?>/>
						<br/>
						<span class="description"></span>
					</td>
				</tr>
				<?php
 				echo '</table>';
 				echo '</div></div></div>';
				//
				echo '<div class="metabox-holder"><div class="postbox "><div class="handlediv" title=""><br /></div><h3 class="hndle"><span>'.'Login protection'.'</span></h3><div class="inside">';
				echo '<table class="form-table">';
				//
				$protection_name = 'login';
				?>
				<tr>
					<th>
						<label style="vertical-align:top;" for="<?php echo $protection_name; ?>_mode">Protection mode</label> 
					</th>
					<td>
						<select name="<?php echo $protection_name; ?>_protection">
							<option value="disabled" <?php echo $settings['security'][$protection_name.'_protection']=='disabled'?'selected':''; ?>>Disabled</option>
							<option value="deny" <?php echo $settings['security'][$protection_name.'_protection']=='deny'?'selected':''; ?>>Deny selected countries</option>
						</select>
						<br/>
						<span class="description"></span>
					</td>
				</tr>
				<tr>
					<th>						
					</th>
					<td>
						<fieldset>
							<div style="display:inline-block; text-align:center;">
							<label for="<?php echo $protection_name; ?>-selectfrom">Available</label><br/>
							<select name="<?php echo $protection_name; ?>-selectfrom" id="<?php echo $protection_name; ?>-selectfrom" multiple size="10" style="min-width:250px; max-width:250px;">
							<?php
								global $wpss_countries;
								foreach ($wpss_countries as $code => $name) {
									if (!in_array($code,$settings['security'][$protection_name.'_countries'])) {
										echo '<option value="'.$code.'">'.$name.'</option>';
									}
								}
							?>
							</select>
							</div>
							<div style="display:inline-block; text-align:center;">
							<a href="JavaScript:void(0);" id="<?php echo $protection_name; ?>-btn-add" class="button">Add &raquo;</a><br/>
							<a href="JavaScript:void(0);" id="<?php echo $protection_name; ?>-btn-remove" class="button">&laquo; Remove</a>
							</div>
							<div style="display:inline-block; text-align:center;">
							<label for="<?php echo $protection_name; ?>-countries">Selected</label><br/>
							<select name="<?php echo $protection_name; ?>_countries[]" id="<?php echo $protection_name; ?>-countries" multiple size="10" style="min-width:250px; max-width:250px;">
							<?php
								foreach ($settings['security'][$protection_name.'_countries'] as $code) {
									echo '<option value="'.$code.'">'.$wpss_countries[$code].'</option>';
								}
							?>
							</select>
							</div>
						</fieldset>
						<script type="text/javascript">
							jQuery(document).ready(function() {
    						jQuery('#<?php echo $protection_name; ?>-btn-add').click(function(){
        					jQuery('#<?php echo $protection_name; ?>-selectfrom option:selected').each( function() {
                		jQuery('#<?php echo $protection_name; ?>-countries').append("<option value='"+jQuery(this).val()+"'>"+jQuery(this).text()+"</option>");
            				jQuery(this).remove();
        					});
    						});
    						jQuery('#<?php echo $protection_name; ?>-btn-remove').click(function(){
        					jQuery('#<?php echo $protection_name; ?>-countries option:selected').each( function() {
            				jQuery('#<?php echo $protection_name; ?>-selectfrom').append("<option value='"+jQuery(this).val()+"'>"+jQuery(this).text()+"</option>");
            				jQuery(this).remove();
        					});
    						});
							});
						</script>						
					</td>
				</tr>
				<?php
 				echo '</table>';
 				echo '</div></div></div>';
				echo '<div class="metabox-holder"><div class="postbox "><div class="handlediv" title=""><br /></div><h3 class="hndle"><span>'.'Comment protection'.'</span></h3><div class="inside">';
				echo '<table class="form-table">';
 				//
				$protection_name = 'comment';
				?>
				<tr>
					<th>
						<label style="vertical-align:top;" for="<?php echo $protection_name; ?>_mode">Protection mode</label> 
					</th>
					<td>
						<select name="<?php echo $protection_name; ?>_protection">
							<option value="disabled" <?php echo $settings['security'][$protection_name.'_protection']=='disabled'?'selected':''; ?>>Disabled</option>
							<option value="deny" <?php echo $settings['security'][$protection_name.'_protection']=='deny'?'selected':''; ?>>Deny selected countries</option>
						</select>
						<br/>
						<span class="description"></span>
					</td>
				</tr>
				<tr>
					<th>						
					</th>
					<td>
						<fieldset>
							<div style="display:inline-block; text-align:center;">
							<label for="<?php echo $protection_name; ?>-selectfrom">Available</label><br/>
							<select name="<?php echo $protection_name; ?>-selectfrom" id="<?php echo $protection_name; ?>-selectfrom" multiple size="10" style="min-width:250px; max-width:250px;">
							<?php
								global $wpss_countries;
								foreach ($wpss_countries as $code => $name) {
									if (!in_array($code,$settings['security'][$protection_name.'_countries'])) {
										echo '<option value="'.$code.'">'.$name.'</option>';
									}
								}
							?>
							</select>
							</div>
							<div style="display:inline-block; text-align:center;">
							<a href="JavaScript:void(0);" id="<?php echo $protection_name; ?>-btn-add" class="button">Add &raquo;</a><br/>
							<a href="JavaScript:void(0);" id="<?php echo $protection_name; ?>-btn-remove" class="button">&laquo; Remove</a>
							</div>
							<div style="display:inline-block; text-align:center;">
							<label for="<?php echo $protection_name; ?>-countries">Selected</label><br/>
							<select name="<?php echo $protection_name; ?>_countries[]" id="<?php echo $protection_name; ?>-countries" multiple size="10" style="min-width:250px; max-width:250px;">
							<?php
								foreach ($settings['security'][$protection_name.'_countries'] as $code) {
									echo '<option value="'.$code.'">'.$wpss_countries[$code].'</option>';
								}
							?>
							</select>
							</div>
						</fieldset>
						<script type="text/javascript">
							jQuery(document).ready(function() {
    						jQuery('#<?php echo $protection_name; ?>-btn-add').click(function(){
        					jQuery('#<?php echo $protection_name; ?>-selectfrom option:selected').each( function() {
                		jQuery('#<?php echo $protection_name; ?>-countries').append("<option value='"+jQuery(this).val()+"'>"+jQuery(this).text()+"</option>");
            				jQuery(this).remove();
        					});
    						});
    						jQuery('#<?php echo $protection_name; ?>-btn-remove').click(function(){
        					jQuery('#<?php echo $protection_name; ?>-countries option:selected').each( function() {
            				jQuery('#<?php echo $protection_name; ?>-selectfrom').append("<option value='"+jQuery(this).val()+"'>"+jQuery(this).text()+"</option>");
            				jQuery(this).remove();
        					});
    						});
							});
						</script>						
					</td>
				</tr>
				<?php
 				echo '</table>';
 				echo '</div></div></div>';
 				//
				echo '<div class="metabox-holder"><div class="postbox "><div class="handlediv" title=""><br /></div><h3 class="hndle"><span>'.'XML-RPC protection'.'</span></h3><div class="inside">';
				echo '<table class="form-table">';
				$protection_name = 'xmlrpc';
				?>
				<tr>
					<th>
						<label style="vertical-align:top;" for="<?php echo $protection_name; ?>_mode">Protection mode</label> 
					</th>
					<td>
						<select name="<?php echo $protection_name; ?>_protection">
							<option value="disabled" <?php echo $settings['security'][$protection_name.'_protection']=='disabled'?'selected':''; ?>>Disabled</option>
							<option value="deny" <?php echo $settings['security'][$protection_name.'_protection']=='deny'?'selected':''; ?>>Deny selected countries</option>
						</select>
						<br/>
						<span class="description"></span>
					</td>
				</tr>
				<tr>
					<th>						
					</th>
					<td>
						<fieldset>
							<div style="display:inline-block; text-align:center;">
							<label for="<?php echo $protection_name; ?>-selectfrom">Available</label><br/>
							<select name="<?php echo $protection_name; ?>-selectfrom" id="<?php echo $protection_name; ?>-selectfrom" multiple size="10" style="min-width:250px; max-width:250px;">
							<?php
								global $wpss_countries;
								foreach ($wpss_countries as $code => $name) {
									if (!in_array($code,$settings['security'][$protection_name.'_countries'])) {
										echo '<option value="'.$code.'">'.$name.'</option>';
									}
								}
							?>
							</select>
							</div>
							<div style="display:inline-block; text-align:center;">
							<a href="JavaScript:void(0);" id="<?php echo $protection_name; ?>-btn-add" class="button">Add &raquo;</a><br/>
							<a href="JavaScript:void(0);" id="<?php echo $protection_name; ?>-btn-remove" class="button">&laquo; Remove</a>
							</div>
							<div style="display:inline-block; text-align:center;">
							<label for="<?php echo $protection_name; ?>-countries">Selected</label><br/>
							<select name="<?php echo $protection_name; ?>_countries[]" id="<?php echo $protection_name; ?>-countries" multiple size="10" style="min-width:250px; max-width:250px;">
							<?php
								foreach ($settings['security'][$protection_name.'_countries'] as $code) {
									echo '<option value="'.$code.'">'.$wpss_countries[$code].'</option>';
								}
							?>
							</select>
							</div>
						</fieldset>
						<script type="text/javascript">
							jQuery(document).ready(function() {
    						jQuery('#<?php echo $protection_name; ?>-btn-add').click(function(){
        					jQuery('#<?php echo $protection_name; ?>-selectfrom option:selected').each( function() {
                		jQuery('#<?php echo $protection_name; ?>-countries').append("<option value='"+jQuery(this).val()+"'>"+jQuery(this).text()+"</option>");
            				jQuery(this).remove();
        					});
    						});
    						jQuery('#<?php echo $protection_name; ?>-btn-remove').click(function(){
        					jQuery('#<?php echo $protection_name; ?>-countries option:selected').each( function() {
            				jQuery('#<?php echo $protection_name; ?>-selectfrom').append("<option value='"+jQuery(this).val()+"'>"+jQuery(this).text()+"</option>");
            				jQuery(this).remove();
        					});
    						});
							});
						</script>						
					</td>
				</tr>
				<?php
 				echo '</table>';
 				echo '</div></div></div>';
 				?>
 				<script type="text/javascript">
 					jQuery(document).ready(function() {
 						jQuery('#wpss_settings').submit(function (event) {
 							jQuery('#login-countries option').prop('selected', true);
 							jQuery('#comment-countries option').prop('selected', true);
 							jQuery('#xmlrpc-countries option').prop('selected', true);
 						});
 					});
 				</script>
 				<?php
 				//print_r($settings);
			}
			if ($tab == 'log') {
				$table = new WPSS_Log_List_Table();
    		$table->prepare_items();
				$message = '';
    		if ('delete' === $table->current_action()) {
        	$message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'wpss'), count($_REQUEST['id'])) . '</p></div>';
    		}
    		?>
    		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
    		<input type="hidden" name="tab" value="log"/>
    		<?php
    		$table->display();
			}
			if ($tab == 'statistics') {
				?>
				<div class="metabox-holder"><div class="postbox"><div class="handlediv" title=""><br /></div><h3 class="hndle"><span>Statistics</span></h3><div class="inside">
					<table class="form-table">
						<tr>
							<td>
								<?php wpss_cf_statistics(); ?>
							</td>
						</tr>
 					</table>
 				</div></div></div>
				<?php
			}
			if ($tab == 'documentation') {
				?><style>.doc img {border:10px solid #ffffff; float:none;}</style><div class="doc">

<h2><strong>Requirements</strong></h2>
<ol>
	<li>You must have CloudFlare account.</li>
	<li>Your domain must be added to CloudFlare.</li>
</ol>
<h2></h2>
<h2><strong>Caching HTML content</strong></h2>
If you want to cache all content including posts, pages, categories, tags, etc. you must add at least three page rules to Cloudflare:
<ol>
	<li>URL pattern: /*.php*
Custom caching: Bypass cache</li>
	<li>URL pattern: /wp-admin*
Custom caching: Bypass cache</li>
	<li>URL pattern: /*
Custom caching: Cache everything
Edge cache expire TTL: 1 week
Browser cache expire TTL: 30 minutes</li>
</ol>
Page rules order is very important.
<h2><strong>CloudFlare Settings</strong></h2>
<img class="alignnone" src="http://www.wp-supersonic.com/wp-content/uploads/2015/04/supersonic_cloudflare_settings.png" alt="" width="830" height="459" />

&nbsp;
<h2><strong>Options</strong></h2>
<img class="alignnone" src="http://www.wp-supersonic.com/wp-content/uploads/2015/04/supersonic_options.png" alt="" width="819" height="729" />

&nbsp;
<h2>Tools</h2>
<img class="alignnone" src="http://www.wp-supersonic.com/wp-content/uploads/2015/04/supersonic_tools.png" alt="" width="900" height="627" />
<h2></h2>
<h2><strong>Cache Purge</strong></h2>
<img class="alignnone" src="http://www.wp-supersonic.com/wp-content/uploads/2015/04/supersonic_cache_purge.png" alt="" width="959" height="1796" />

&nbsp;
<h2>Security</h2>
<img class="alignnone" src="http://www.wp-supersonic.com/wp-content/uploads/2015/04/supersonic_security.png" alt="" width="958" height="1826" />

&nbsp;
<h2>Comments</h2>
<img class="alignnone" src="http://www.wp-supersonic.com/wp-content/uploads/2015/04/superconic_comments.png" alt="" width="1079" height="304" />

				</div><?php
			}
			if ($tab == 'donate') {
				?>
Wordpress SuperSonic with CloudFlare has required a great deal of time and effort to develop. If it's been useful to you then you can support this development by making a small donation. This will act as an incentive for me to carry on developing it, providing countless hours of support, and including any enhancements that are suggested.<br/><br/>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="DYTX6AJZP7V7C">
<input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal – The safer, easier way to pay online.">
<img alt="" border="0" src="https://www.paypalobjects.com/pl_PL/i/scr/pixel.gif" width="1" height="1">
</form>
				<?php
			}
			?>
			<p class="submit" style="clear: both;">
			<?php 
				if ($tab != 'tools'	&& $tab != 'statistics' && $tab != 'documentation' && $tab != 'log' && $tab != 'donate') {
			?>
  		<input type="submit" name="Submit"  class="button-primary" value="Update Settings" />
      <input type="hidden" name="wpss-config-submit" value="Y" />
   		</p>
			</form>
   		<?php 
   			}
   		?>
			</div>
		</div><!-- post-body -->		
		</div><!-- poststuff -->
		<div style="clear:both;"></div>
			<?php
}

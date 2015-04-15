<?php

function wpss_cf_statistics() {
	$settings = get_option( "wpss_settings" );
	$default_stats = "40";
	$stats = get_option("wpss_stats_".$default_stats);
	if (1==2 && ($stats === false || $stats['time']+600 < current_time('timestamp') || $settings['update_time'] > $stats['time'])) {
		$stats = array();
		$stats['time'] = current_time('timestamp');
		$cf = new cloudflare_api($settings['cloudflare_login'], $settings['cloudflare_api_key']);			
		$stats['stats'] = $cf->stats($settings['cloudflare_domain'],$default_stats);
		update_option("wpss_stats_".$default_stats,$stats,3600);
	}
	$nonce = wp_create_nonce("wpss_stats_nonce");
	?>
	<div style="float:left;">
	<label for="period">Period:</label>
	<select id="period" name="period" nonce="<?php echo $nonce; ?>">
		<option value="20">Past 30 days</option>
		<option value="30">Past 7 days</option>
		<option value="40" selected>Past day</option>
	</select></div><div><span class="spinner" style="float:left;"></span></div><div style="clear:both;"></div>
	<br/>
	<div id='wpss_stats_error' style="width:100%; color:red; font-weight: bold;">
	</div><br/>
	<div style="width:100%">
		<div id="pageviews" style="width:100%; max-width:300px; height:300px; float:left; display:none;"></div>
		<div id="uniques" style="width:100%; max-width:300px; height:300px; float:left; display:none;"></div>
		<div id="bandwidth" style="width:100%; max-width:300px; height:300px; float:left; display:none;"></div>
		<div style="clear:both;"></div>
	</div>
	<div style="clear:both;"></div>
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<script type="text/javascript">
		var stats = null;<?php /* echo json_encode($stats); */ ?>;
		//
		function get_charts() {
			jQuery('div#pageviews').html('');
			jQuery('div#uniques').html('');
			jQuery('div#bandwidth').html('');
			if (typeof(stats.stats.msg) == 'string') {
				jQuery('div#wpss_stats_error').html('API message: '+stats.stats.msg+'.');
			}
			else if ((typeof(stats.stats.error) == 'string')) {
				jQuery('div#wpss_stats_error').html('API message: '+stats.stats.error+'.');
			}
			else {
				jQuery('div#wpss_stats_error').html('');
				jQuery('#pageviews').css('display','block');
				jQuery('#uniques').css('display','block');
				jQuery('#bandwidth').css('display','block');
				drawChartPageViews();
				drawChartUniques();
				drawBandwidth();
			}
		}
		//
   	google.load("visualization", "1", {packages:["corechart"]});
  	google.setOnLoadCallback(start_charts);
		//
    function drawChartPageViews() {
      	var pageviews = stats.stats.response.result.objs[0].trafficBreakdown.pageviews;
      	//console.log(pageviews);
				var data = google.visualization.arrayToDataTable([
          ['Page Views', "Views"],
          ['Regular', pageviews.regular],
          ['Threat', pageviews.threat],
          ['Crawler', pageviews.crawler]
        ]);

        var options = {
          title: 'Page Views (Total)',
          legend: {position: 'top', maxLines: 3},
          titleTextStyle: {fontSize: 15},
          pieSliceText: 'value',
          width: 340
        };

        var chart = new google.visualization.PieChart(document.getElementById('pageviews'));

        chart.draw(data, options);
    }
    //
    function drawChartUniques() {
      	var pageviews = stats.stats.response.result.objs[0].trafficBreakdown.uniques;
				var data = google.visualization.arrayToDataTable([
          ['Page Views', "Views"],
          ['Regular', pageviews.regular],
          ['Threat', pageviews.threat],
          ['Crawler', pageviews.crawler]
        ]);

        var options = {
          title: 'Page Views (Unique)',
          legend: {position: 'top', maxLines: 3},
          titleTextStyle: {fontSize: 15},
          pieSliceText: 'value',
          width: 340
        };

        var chart = new google.visualization.PieChart(document.getElementById('uniques'));

        chart.draw(data, options);
    }
    //
    function drawBandwidth() {
      	var pageviews = stats.stats.response.result.objs[0].bandwidthServed;
				var data = google.visualization.arrayToDataTable([
          ['Bandwidth', "MB"],
          ['Cloudflare', pageviews.cloudflare],
          ['Server', pageviews.user]
        ]);

        var options = {
          title: 'Bandwidth',
          legend: {position: 'top', maxLines: 3},
          titleTextStyle: {fontSize: 15},
          pieSliceText: 'value',
          width: 340
        };

        var chart = new google.visualization.PieChart(document.getElementById('bandwidth'));

        chart.draw(data, options);
    }
   	function start_charts() {
	   	jQuery("#period").change();
	  }
		//
		jQuery(document).ready( function() {
   		jQuery("#period").change( function() {
   			jQuery(".spinner").css('display','inline');
				var period = jQuery("#period").val();
				var nonce = jQuery("#period").attr('nonce');
				jQuery('div#wpss_stats_error').html('');
	      jQuery.ajax({
  	       type : "post",
    	     dataType : "json",
      	   url : "<?php echo admin_url('admin-ajax.php'); ?>",
        	 data : {action: 'wpss_stat', period: period, nonce: nonce},
	         success: function(ret) {
  	       		stats = ret;
    	     		//console.log(stats);
							get_charts();
        	 },
	         complete: function() {
  	       		jQuery(".spinner").css('display','none');
    	     }
      	})   
	   	})
		})		
	</script>
	<?php
}

add_action("wp_ajax_wpss_stat", "wpss_stat_ajax");
function wpss_stat_ajax() {
	if ( !wp_verify_nonce( $_REQUEST['nonce'], "wpss_stats_nonce")) {
		exit("No naughty business please.");
	}
	$period = $_REQUEST['period'];   
	//
	$settings = get_option( "wpss_settings" );
	$default_stats = $period;
	$stats = get_option("wpss_stats",false);
	if ($stats === false) {
		add_option( 'wpss_stats', array(), '', 'no' );
	}
	$stats_period = array();
	if (isset($stats[$period])) {
		$stats_period = $stats[$period];
	}
	if ($stats_period === false || !is_array($stats_period['stats']) || (intval($stats_period['time'])+600) < current_time('timestamp') || $settings['update_time'] > $stats_period['time']) {
		$stats_period = array();
		$stats_period['time'] = current_time('timestamp');
		$cf = new cloudflare_api($settings['cloudflare_login'], $settings['cloudflare_api_key']);			
		$stats_period['stats'] = $cf->stats($settings['cloudflare_domain'],$default_stats);
		$stats[$period] = $stats_period;
		update_option('wpss_stats',$stats);
	}
	echo json_encode($stats_period);
	die();
}